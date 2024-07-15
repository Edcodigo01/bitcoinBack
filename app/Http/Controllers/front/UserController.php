<?php

namespace App\Http\Controllers\front;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use ImageIntervention;
use Mail;
use Illuminate\Support\Facades\Hash;
use Auth;
use App\Models\State;
use App\Models\City;
use App\Models\Country;

use App\Models\UserPlan;
use App\Models\Image;
use App\Models\Withdrawal;
use App\Models\Bankuser;
use App\Models\Walletuser;
use App\Models\Wallet;
use App\Models\Coins_wallet;
use App\Models\Coin;



use Carbon\Carbon;

class UserController extends Controller
{

    public function get_withdrawal(Request $request)
    {
        $withdrawal = Withdrawal::find($request->id);
        $withdrawal->code = "";

        $email = Auth::user()->email;
        return response()->json(['withdrawal' => $withdrawal, 'email' => $email]);
    }
    public function change_alias(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'alias' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $user = User::find($request->id);
        $user->alias = $request->alias;
        $user->save();

        return response()->json(['newAlias' => $user->alias, 'message' => 'Datos actualizados con éxito.']);
    }
    public function list_withdrawal_user(Request $request)
    {
        $withdrawals = Withdrawal::where('user_id', Auth::user()->id)->where('status', '!=', 'incompleto')->get();
        return response()->json(['withdrawals' => $withdrawals]);
    }

    public function get_withdrawal_user(Request $request)
    {
        $withdrawal = Withdrawal::find($request->id);
        $images = Image::where('withdrawal_id', $request->id)->where('type', 'pay_withdrawal')->get();
        $user = User::find($withdrawal->user_id);

        return response()->json(compact('withdrawal', 'images', 'user'));
    }

    public function complete_request_withdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'withdrawal_id' => 'required',
            'code' => 'required|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $withdrawal = Withdrawal::find($request->withdrawal_id);
        $user = Auth::user();

        if ($withdrawal->code != Null and $withdrawal->code == $request->code and $withdrawal->limit_time_code >= Carbon::now()->format('Y-m-d H:i:s')) {
            $withdrawal->code = Null;
            $withdrawal->date_request = Carbon::now()->format('Y-m-d H:i:s');
            $withdrawal->status = 'revision';
            $date = Carbon::now()->format('d-m-Y H:i:s');
            $email = env('EMAIL_ADMIN');

            if ($withdrawal->type == 'earnings') {
                $type = 'ganancias';
                if ($withdrawal->request >  $user->earnings_available) {
                    return response()->json(['result' => 'error-validation', 'errors' => json_encode(["code" => "Saldo insuficiente."])]);
                }
                $user->earnings_available = $user->earnings_available - $withdrawal->request;
                $user->earnings_procesing = $user->earnings_procesing + $withdrawal->request;
            } else if ($withdrawal->type == 'earnings-referralls') {
                $type = 'ganancias por referidos';
                if ($withdrawal->request >  $user->available_earnings_referralls) {
                    return response()->json(['result' => 'error-validation', 'errors' => json_encode(["code" => "Saldo insuficiente."])]);
                }
                $user->available_earnings_referralls = $user->available_earnings_referralls - $withdrawal->request;
                $user->available_earnings_referralls_procesing = $user->available_earnings_referralls_procesing + $withdrawal->request;
            } else {
                $type = 'inversión';
                if ($withdrawal->request >  $user->inversion_available) {
                    return response()->json(['result' => 'error-validation', 'errors' => json_encode(["code" => "Saldo insuficiente."])]);
                }
                $user->inversion_available = $user->inversion_available - $withdrawal->request;
                $user->inversion_procesing = $user->inversion_procesing + $withdrawal->request;
            }
            $user->save();

            $withdrawal->save();
            $data = ['data' => ['alias' => Auth::user()->alias, 'type' => $type, 'date' => $date]];
            Mail::send('mails.withdrawal_request_for_andmin', $data, function ($message) use ($email, $type) {
                $message->subject('Solicitud de retiro de ' . $type . 'por el usuario: ' . Auth::user()->alias . ' de ' . env('APP_NAME'));
                $message->to($email);
            });
        } else {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode(["code" => "El código es incorrecto o se ha vencido."])]);
        }
    }

    public function generate_code_withdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'withdrawal_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => $validator->errors()]);
        }

        $withdrawal = Withdrawal::find($request->withdrawal_id);
        $randomString = Str::random(10);
        $withdrawal->code = $randomString;
        $withdrawal->limit_time_code = Carbon::now()->addHours(2)->format('Y-m-d H:i:s');
        $withdrawal->save();

        if ($withdrawal->type == 'investment') {
            $type = 'inversión';
        } else {
            $type = 'ganancias';
        }
        $date = Carbon::now()->format('d-m-Y H:i:s');

        $email = Auth::user()->email;
        // $email = "eavc53189@gmail.com";

        $data = ['data' => ['email' => $email, 'code' => $withdrawal->code, 'type' => $type, 'date' => $date]];

        Mail::send('mails.code_withdrawal', $data, function($message) use($email, $withdrawal) {
            $message->subject('Código retiro de ' . $withdrawal->type . ' ' . env('APP_NAME'));
            $message->to($email);
        });

        return response()->json('ok');
    }

    public function available_for_withdrawal(Request $request)
    {
    
        if ($request->type == 'ganancias') {

            $withdrawalR = Withdrawal::where('user_id',Auth::user()->id)->where('type', 'earnings')->where('status', 'revision')->get();
            if ($withdrawalR->first()) {
                return response()->json('revision pendiente');
            }

        } else if ($request->type == 'ganancias-referidos') {

            $withdrawalR = Withdrawal::where('user_id',Auth::user()->id)->where('type', 'earnings-referralls')->where('status', 'revision')->get();
            if ($withdrawalR->first()) {
                return response()->json('revision pendiente');
            }

        }

        $user = Auth::user();
        $bankuser = Bankuser::where('user_id', Auth::user()->id)->first();
        $walletuser = Walletuser::where('user_id', Auth::user()->id)->first();
        $wallets = Wallet::orderBy('name','asc')->get();
        $coins = Coins_wallet::orderBy('name', 'asc')->get();
        return response()->json(compact('user','walletuser','wallets','coins'));
    }

    public function validate_data_withdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_withdraw' => 'required',
            'cant' => 'required',
            'wallet_id' => 'required',
            'coin_id' => 'required',
            'address' => 'required',
            'save_wallet' => 'required',
           
        ]);
        
        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => $validator->errors()]);
        }
        $cant =  str_replace(',', '.', $request->cant);
        $user = Auth::user();

        if ($request->type_withdraw == 'ganancias') {
            $withdrawal = Withdrawal::where('type', 'earnings')->where('status', 'imcompleto')->first();
            if (!$withdrawal) {
                $withdrawal = new Withdrawal;
            }

            if ($cant > $user->earnings_available) {
                return response()->json('No se puede realizar esta operación.');
            }

            $commissionPorcentage = env('COMMISSION');
            // return response()->json(['result'=>'error','commissionPorcentage'=>$commissionPorcentage]);
            $ganancias = $user->earnings_available;
            

            $comissionTotal = ($commissionPorcentage * $cant) / 100;
            $receives = $cant - $comissionTotal;
            $total = $cant;
            $withdrawal->type = 'earnings';

        } else if ($request->type_withdraw == 'inversion') {

            if ($cant > $user->inversion_available) {
                return response()->json('No se puede realizar esta operación.');
            }

            $withdrawal = Withdrawal::where('type', 'investment')->where('status', 'imcompleto')->first();
            if (!$withdrawal) {
                $withdrawal = new Withdrawal;
            }
            
            $total = $cant;
            $withdrawal->type = 'investment';
            $commissionPorcentage = 0;
            $comissionTotal = 0;
            $receives = $cant;
        } else if ($request->type_withdraw == 'ganancias-referidos') {

            if ($cant > $user->available_earnings_referralls) {
                return response()->json('No se puede realizar esta operación.');
            }

            $withdrawal = Withdrawal::where('type', 'investment')->where('status', 'imcompleto')->first();
            if (!$withdrawal) {
                $withdrawal = new Withdrawal;
            }

            $cant =  str_replace(',', '.', $cant);
            $total = $cant;
            $withdrawal->type = 'earnings-referralls';
            $commissionPorcentage = 0;
            $comissionTotal = 0;
            $receives = $cant;
        }

        $withdrawal->date_request = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        $withdrawal->request = $total;
        $withdrawal->commissionPorcentage = $commissionPorcentage;
        $withdrawal->commission = $comissionTotal;
        $withdrawal->withdraw = $receives;
        $withdrawal->user_id = Auth::user()->id;
        
        $withdrawal->wallet_id = $request->wallet_id; 
        $withdrawal->coin_id = $request->coin_id;
        $wallet_name = Wallet::find($request->wallet_id)->name;

        $Coins_wallet = Coins_wallet::find($request->coin_id);
        $wallet_coin = Coin::find($Coins_wallet->coin_id)->name;

        $withdrawal->wallet_name = $wallet_name;
        $withdrawal->wallet_coin = $wallet_coin;
        $withdrawal->wallet_address = $request->address;

        // $withdrawal->nameBank = $request->nameBank;
        // $withdrawal->holderBank = $request->holderBank;
        // $withdrawal->identificationBank = $request->identificationBank;
        // $withdrawal->typeBank = $request->typeBank;
        // $withdrawal->numberAccountBank = $request->numberAccountBank;
        $withdrawal->save();

        $walletuser = Walletuser::where('user_id', Auth::user()->id)->first();
        if ($request->save_wallet == 'Si') {

            if (!$walletuser) {
                $walletuser = new Walletuser;
            }

            $walletuser->coin_id = $request->coin_id;
            $walletuser->wallet_id = $request->wallet_id;
            $walletuser->address = $request->address;
            $walletuser->user_id = Auth::user()->id;
            $walletuser->save();
        } else {
            if ($walletuser) {
                $walletuser->delete();
            }
        }
        return response()->json($withdrawal);
    }

    public function get_balance()
    {
        $user = Auth::user();
        $states = State::all();
        $cities = City::all();
        $user_plans = UserPlan::where('status', 'activo')->where('user_id', Auth::user()->id)->get();
        $porcentage_month = 0;
        $profit_month = 0;
        $daynow = Carbon::now()->addDays(2)->dayName;

        return response()->json(compact('user', 'porcentage_month', 'profit_month', 'user_plans', 'daynow'));
    }

    public function next_day()
    {
        // \Artisan::call('earnings');

        $users = User::whereNotNull('email_verified_at')->where('status', 'enabled')->where('role', 'cliente')->get();
       
        foreach ($users as $user) {

            
            $userplans = UserPlan::where('user_id', $user->id)->where('status', 'activo')->get();
            // minimum_charge
            // processing_earnings
            // processed_earnings

            foreach ($userplans as $userplan) {
                // $user_plan
                $lastEarning = ($userplan->inversion * 2) - $userplan->processed_earnings;
                

                if ($lastEarning < $userplan->daily_gain) {

                    $userplan->processed_earnings += $lastEarning;
                    $userplan->processing_earnings += $lastEarning;
                    $user->earnings_to_date += $lastEarning;
                    $user->earnings_available += $userplan->processing_earnings;
                    $userplan->processing_earnings = 0;
                    $userplan->status = 'finalizado';
                // }else if ($userplan->processed_earnings >= $userplan->inversion) {
                //     $lastInversion = ($userplan->inversion * 2) - $userplan->processed_earnings;
                //     if($lastInversion < $userplan->daily_gain){
                //         $userplan->processed_inversion += $lastInversion;
                //         $userplan->processing_inversion += $lastInversion;
                //         $userplan->processed_earnings += $lastInversion;
                //         $user->earnings_to_date += $lastInversion;
                //         $user->inversion_available += $userplan->processing_inversion;
                //         $userplan->processing_inversion = 0;
                //         $userplan->status = 'finalizado';
                //     }else{
                //         $userplan->processed_inversion += $userplan->daily_gain;
                //         $userplan->processing_inversion += $userplan->daily_gain;
                //         $user->earnings_to_date += $userplan->daily_gain;
                //         $userplan->processed_earnings += $userplan->daily_gain;
                //         if($userplan->processing_inversion >= $userplan->minimum_charge){
                //             $user->inversion_available += $userplan->processing_inversion;
                //             $userplan->processing_inversion = 0;
                //         }
                //     }
                }else{
                    $userplan->processed_earnings += $userplan->daily_gain;
                    $userplan->processing_earnings += $userplan->daily_gain;
                    $user->earnings_to_date += $userplan->daily_gain;

                    if($userplan->processing_earnings >= $userplan->minimum_charge){
                        $user->earnings_available += $userplan->processing_earnings;
                        $userplan->processing_earnings = 0;
                    }
                    
                }

                $userplan->save();
                $user->save();
            }
        }
    }

    public function getAuth()
    {
        $user = Auth::user();
        $states = State::all();
        $cities = City::all();
        $countries = Country::all();

        $user_plan = UserPlan::where('status', 'activo')->where('user_id', Auth::user()->id)->first();
        $porcentage_month = 0;
        $profit_month = 0;

        if ($user_plan) {
            $porcentage_month = $user_plan->profit;
            $profit_month = $user_plan->minimum_charge;
        } else {
            $user_plan['name'] = 'Gratis';
        }

        $images_verification = Image::where('user_id', Auth::user()->id)->where('type', 'verification')->get();
        return response()->json(compact('user', 'states', 'cities', 'porcentage_month', 'profit_month', 'images_verification', 'user_plan', 'countries'));
    }

    public function prueba()
    {
        return "PRUEBA";
    }

    public function viewMail()
    {
        // return "XX";
        return view('mails.confirm_email');
    }

    public function pruebaMail()
    {
        $data = ['data' => ['email' => "eavc53189@gmail.com", 'token' => "wrewr"]];
        Mail::send('mails.confirm_email', $data, function ($message) {
            $message->subject('Confirma tu cuenta de ' . env('APP_NAME'));
            $message->to("eavc53189@gmail.com");
        });
        return "enviado";
    }

    public function recover_password_request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => $validator->errors()]);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode(["email" => "El correo no existe en nuestra base de datos."])]);
        }

        $token_password = str_replace('/', '', Hash::make(\Carbon\Carbon::now()->format("YmdHis") . Str::random(10)));
        $user->token_password = $token_password;
        $user->date_token_password = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        $user->save();

        $data = ['data' => ['email' => $user->email, 'token_password' => $token_password]];

        Mail::send('mails.recover_password', $data, function ($message) use ($user) {
            $message->subject('Restablecer contraseña ' . env('APP_NAME'));
            $message->to($user->email);
        });

        return response()->json(['result' => 'ok']);
    }

    public function recover_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'repeat_password' => 'required',
            'token' => 'required',
            'email' => 'required',
        ]);

      
        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => $validator->errors()]);
        }

        if($request->password != $request->repeat_password){
            return response()->json(['result' => 'error-validation', 'errors' => json_encode(['repeat_password'=>'Las contraseñas no coinciden.'])]);
        }

        $user = User::whereNotNull("token_password")->where('email', $request->email)->where('token_password', $request->token)->first();
        if (!$user) {
            return response()->json(['result' => 'error', 'message' => 'La solicitud es incorrecta o se ha vencido.']);
        }

        if ($user->date_token_password) {
            if ($user->date_token_password > \Carbon\Carbon::now()->format("Y-m-d H:i:s")) {
                return response()->json(['result' => 'error', 'message' => 'La solicitud de recuperar contraseña se ha vencido.']);
            }
        }

        $user->token_password  = null;
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json(['result' => 'ok', 'message' => 'Contraseña actualizada con éxito.']);
    }

    public function recover_password_verify(Request $request)
    {

        if(Auth::check()){
            Auth::logout();
        }
       
        // return response()->json(['result'=>'error','message'=>$request->token]);
        $user = User::whereNotNull("token_password")->where('email', $request->email)->where('token_password', $request->token)->first();
        if (!$user) {
            return response()->json(['result' => 'error', 'message' => 'La solicitud es incorrecta o se ha vencido.']);
        }
        if ($user->date_token_password) {
            if ($user->date_token_password > \Carbon\Carbon::now()->format("Y-m-d H:i:s")) {
                return response()->json(['result' => 'error', 'message' => 'La solicitud de recuperar contraseña se ha vencido.']);
            }
        }
        return response()->json(['result'=>'ok']);
    }


    public function get_cities_select(Request $request)
    {
        $cities = City::where('state_id', $request->id)->get();
        return response()->json($cities);
    }

    public function get_user_data(Request $request)
    {
        $user = Auth::user();
        $states = State::all();
        $cities = [];
        if ($user->state_id) {
            $cities = City::where('state_id', $user->state_id)->get();
        }
        return response()->json(compact('user', 'states', 'cities'));
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias_or_email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => $validator->errors()]);
        }
        // verifica si existe usuario
        $user = User::where("alias", $request->alias_or_email)->orWhere("email", $request->alias_or_email)->first();
        if ($user == Null) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode(["alias_or_email" => "El alias o correo no ha sido registrado."])]);
        }

        if ($user->email_verified_at == Null && $user->role == 'cliente') {
            return response()->json(['result' => 'correo no verificado', 'correo' => $user->email]);
        }

        if ($user->status == 'disabled') {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode(["alias_or_email" => "Esta cuenta ha sido bloqueada."])]);
        }

        if (Hash::check($request->password, $user->password)) {
            $token = JWTAuth::fromUser($user);
            $user = $user->only('id', 'alias', 'email', 'name', 'last_name', 'role');
            $user['token'] = $token;
            $user['expiration'] = \Carbon\Carbon::now()->addHour()->format('Y/m/d H:i:s');
            return response()->json($user);
        }

        return response()->json(['result' => 'error-validation', 'errors' => json_encode(["password" => "La contraseña ingresada es incorrecta."])]);
    }

    public function loginPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias_or_email' => 'required',
            'password' => 'required',
            'pin' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => $validator->errors()]);
        }
        // verifica si existe usuario
        $user = User::where("alias", $request->alias_or_email)->orWhere("email", $request->alias_or_email)->first();
        if ($user == Null) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode(["alias_or_email" => "El alias o correo no ha sido registrado."])]);
        }

        if ($user->email_verified_at == Null && $user->role == 'cliente') {
            return response()->json(['result' => 'correo no verificado', 'correo' => $user->email]);
        }

        if ($user->status == 'disabled') {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode(["alias_or_email" => "Esta cuenta ha sido bloqueada."])]);
        }

        if (Hash::check($request->password, $user->password)) {
            $token = JWTAuth::fromUser($user);
            $user = $user->only('id', 'alias', 'email', 'name', 'last_name', 'role');
            $user['token'] = $token;
            $user['expiration'] = \Carbon\Carbon::now()->addHour()->format('Y/m/d H:i:s');
            return response()->json($user);
        }

        return response()->json(['result' => 'error-validation', 'errors' => json_encode(["password" => "La contraseña ingresada es incorrecta."])]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'unique:users',
            'email' => 'unique:users',
            'name' => 'required',
            'password' => 'required|min:8',
            'password_repeat' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        if ($request->password != $request->password_repeat) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode(["password_repeat" => "Las contraseñas deben coincidir."])]);
        }

        $userreference = User::where('token_reference', $request->token_reference)->first();
        $user = new User;
        if ($userreference and $request->acept_reference) {
            $user->token_reference_father = $request->token_reference;
        }

        $user->alias = $request->alias;
        $user->email = $request->email;
        $user->name = $request->name;
        $token_email = str_replace('/', '', Hash::make(\Carbon\Carbon::now()->format("YmdHis") . Str::random(10)));
        $user->token_email = $token_email;
        $user->role = "cliente";
        $user->token_reference = Str::random(3) . \Carbon\Carbon::now()->format("Ymd") . Str::random(3) . \Carbon\Carbon::now()->format("His");
        $user->password = bcrypt($request->password);

        $data = ['data' => ['email' => $user->email, 'token' => $token_email]];

        Mail::send('mails.confirm_email', $data, function ($message) use ($user) {
            $message->subject('Confirma tu cuenta de ' . env('APP_NAME'));
            $message->to($user->email);
        });
        $user->save();

        return response()->json(['result' => 'ok', 'message' => 'Se ha enviado un mensaje a su correo electrónico con el código de confirmación para completar el registro.']);
    }

    public function refreshToken(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $token = JWTAuth::fromUser($user);
            $user = $user->only('id', 'alias', 'email', 'name', 'last_name', 'role');
            $user['token'] = $token;
            $user['expiration'] = \Carbon\Carbon::now()->addHour()->format('Y/m/d H:i:s');
            return response()->json($user);
        }
        return response()->json([
            'message' => ""
        ], 401); // Status code here
    }

    public function confirm_mail(Request $request)
    {
        $user = User::where("email", $request->email)->first();

        if ($user == Null) {
            return redirect()->to(env("ENDPOINT_FRONT") . "?account=no-existe");
        }

        if ($user->email_verified_at) {
            return redirect()->to(env("ENDPOINT_FRONT") . '?cuenta=confirmada');
        }

        if ($user->token_email != Null and $user->token_email == $request->token) {
            // $user->token_email = Null;
            $user->email_verified_at = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
            $user->save();
            return redirect()->to(env("ENDPOINT_FRONT") . '?cuenta=confirmada');
        }

        return redirect()->to(env("ENDPOINT_FRONT") . "?cuenta=no-confirmada");
    }

    public function resend_email_confirm(Request $request)
    {

        $user = User::where("email", $request->email)->first();
        if ($user) {
            if ($user->email_verified_at) {
                return response()->json(['result' => 'error-validation', 'errors' => json_encode(["email" => "El correo ya fue verificado."])]);
            }

            $token_email = str_replace('/', '', Hash::make(\Carbon\Carbon::now()->format("YmdHis") . Str::random(10)));
            $user->token_email = $token_email;
            $user->save();
            $data = ['data' => ['email' => $user->email, 'token' => $token_email]];

            Mail::send('mails.confirm_email', $data, function ($message) use ($user) {
                $message->subject('Confirma tu cuenta de ' . env('APP_NAME'));
                $message->to($user->email);
            });

            return response()->json(['result' => 'ok', 'message' => 'Se ha enviado un mensaje a su correo electrónico con el código de confirmación para completar el registro.']);
        } else {

            return response()->json(['result' => 'error-validation', 'errors' => json_encode(["email" => "El correo no existe en nuestra base de datos."])]);
        }
    }

    public function update_data_personal(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'alias' => 'unique:users,id,' . $user->id,
            'name' => 'unique:users,id,' . $user->id,
            'date_of_birth' => 'required|date',
            // 'document_type' => 'required',
            // 'document_number' => 'required|max:99999999999999',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $user->alias = $request->alias;
        $user->name = $request->name;
        $user->date_of_birth = $request->date_of_birth;
        $age = \Carbon\Carbon::now()->format('Y') - \Carbon\Carbon::parse($request->date_of_birth)->format('Y');
        $user->age = $age;
        // $user->document_type = $request->document_type;
        // $user->document_number = $request->document_number;
        $user->save();

        return response()->json(['result' => 'ok']);
    }

    public function update_data_contact(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'phone2' => 'nullable',
            'country' => 'required',
            'state' => 'nullable',
            'city' => 'nullable',
            'address' => 'nullable|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $user->phone = $request->phone;
        $user->phone2 = $request->phone2;
        $user->state = $request->state;
        $user->city = $request->city;
        $user->country = $request->country;
        $user->address = $request->address;
        $user->save();

        return response()->json(['result' => 'ok']);
    }

    public function add_cities(Request $request)
    {
        foreach ($request->states as $element) {
            $provincia = new State;
            $provincia->name = $element['provincia'];
            $provincia->save();
            foreach ($element['cantones'] as $subelement) {
                $canton = new City;
                $canton->name = $subelement['canton'];
                $canton->state_id = $provincia->id;
                $canton->save();
            }
        }
    }

    public function delete_image(Request $request)
    {
        $image = Image::find($request->id);
        if ($image)
            $image->delete();
    }

    public function upload_file_verification(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $width_min = 350;
        $width_max = 1200;
        $folder = Auth::user()->id . "/" . "img_verification/";
        $type = 'verification';
        if ($request->hasFile('file')) {
            $file = ImageIntervention::make($request->file('file')->getRealPath());
            if ($file->width() < $width_min) {
                return response()->json(["result" => "error", "message" => "La imagen debe tener un tamaño superior a " . $width_min . " píxeles."]);
            }

            $extension = $request->file('file')->getClientOriginalExtension();
            $fileName   = \Carbon\Carbon::now()->format('dmYHms') . Str::random(10);

            $url_path = asset('images/user/' . $folder . '/' . $fileName . '.' . $extension);
            $local_path = public_path('images/user/' . $folder . '/' . $fileName . '.' . $extension);
            $image = new Image;

            // $image->name = $fileName.$extension;
            $image->url_path = $url_path;
            $image->local_path = $local_path;
            $image->type = $type;


            $image->user_id = Auth::user()->id;

            $image->save();

            // make dir
            if (!File::exists('images')) {
                File::makeDirectory('images');
            }

            if (!File::exists('images/user')) {
                File::makeDirectory('images/user');
            }

            if (!File::exists('images/user/' . Auth::user()->id)) {
                File::makeDirectory('images/user/' . Auth::user()->id);
            }

            if (!File::exists('images/user/' . Auth::user()->id . '/plan')) {
                File::makeDirectory('images/user/' . Auth::user()->id . '/plan');
            }

            if (!File::exists('images/user/' . $folder)) {
                File::makeDirectory('images/user/' . $folder);
            }

            //move image to img folder
            if ($file->width() > $width_max) {
                $img = $file->resize($width_max, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $img->save('images/user/' . $folder . '/' . $fileName . '.' . $extension);
            } else {
                $file->save('images/user/' . $folder . '/' . $fileName . '.' . $extension);
            }

            return response()->json(["result" => "success", "message" => "Archivo subido con éxito."]);
        } else {
            return response()->json("Hubo un error al intentar subir este archivo.");
        }
    }
}
