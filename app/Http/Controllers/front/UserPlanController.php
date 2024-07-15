<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserPlan;
use App\Models\Plan;
use App\Models\Bank;
use App\Models\Image;
use App\Models\User;
use App\Models\ReferralEarnings;
use App\Models\License;
use App\Models\Inversion;
use Illuminate\Support\Facades\File;
use App\Models\Wallet;
use App\Models\Coins_wallet;
use Auth;
use Mail;
use ImageIntervention;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserPlanController extends Controller
{

    public function list()
    {
        $plan = Userplan::select(
            "user_plans.*",
            "users.alias as user_alias",
            "users.name as user_name"
        )
            ->leftJoin("users", "users.id", "=", "user_plans.user_id")
            ->where('user_plans.status', 'revision')
            ->orWhere('user_plans.status', 'rechazado')
            ->orWhere('user_plans.status', 'finalizado')
            ->orWhere('user_plans.status', 'activo')
            ->get();

        return response()->json(['list' => $plan]);
    }

    public function my_plan(Request $request)
    {
        $userplan = Userplan::where('user_id', Auth::user()->id)->where('status', 'activo')->first();
        if (!$userplan) {
            $userplan = Plan::where('profit', '0')->first();
        }

        return response()->json(compact('userplan'));
    }

    public function getPlanAdmin(Request $request)
    {
        $userplan = UserPlan::find($request->id);
        $user = User::find($userplan->user_id);
        $userImagesVerfication = Image::where('user_id', $userplan->user_id)->where('type', 'verification')->get();
        $images = Image::where('userplan_id', $request->id)->get();

        return response()->json(compact('userplan', 'images', 'user', 'userImagesVerfication'));
    }

    // activar plan admin
    public function activatePlan(Request $request)
    {

        $userplan = UserPlan::find($request->id);
        $userplan->date_activated = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        $userplan->date_end = \Carbon\Carbon::now()->addWeekdays($userplan->weekdays)->format('Y-m-d H:i:s');

        $userplan->observations = "";
        $userplan->status = 'activo';
        $userplan->save();

        $inversionLast = Inversion::where('user_id', $userplan->user_id)->where('status', 'last')->first();

        if ($inversionLast) {
            $inversionLast->status = 'other';
            $inversionLast->save();
        }

       

        $inversion = new Inversion;
        $inversion->date_start = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        
        $inversion->date_end = $userplan->date_end;
        $inversion->user_plan_id = $userplan->id;
        $inversion->user_id = $userplan->user_id;
        $inversion->inversion = $userplan->inversion;
        
        // Ganancias Referidos
        $gainPerLevel1 = ReferralEarnings::where('name', 'Nivel 1')->first();
        $gainPerLevel2 = ReferralEarnings::where('name', 'Nivel 2')->first();
        $gainPerLevel3 = ReferralEarnings::where('name', 'Nivel 3')->first();
        $inversion->earnings_porcentage_referred_1 = $gainPerLevel1->earnings;
        $inversion->earnings_porcentage_referred_2 = $gainPerLevel2->earnings;
        $inversion->earnings_porcentage_referred_3 = $gainPerLevel3->earnings;
       
        $user = User::find($inversion->user_id);
        $user->points = $user->points + $userplan->license;
        $user->inversion_total = $user->inversion_total + $inversion->inversion;
        $user->minimum_charge = $userplan->minimum_charge;
        $user->license_pay = 'Si';
        $user->user_verified = '1';
        $user->save();

        // buscar usuarios superiores
        $user1 = User::where('token_reference', $user->token_reference_father)->first();
        if ($user1) {
            $userplan = UserPlan::where('user_id', $user1->id)->where('status', 'activo')->first();

            if ($userplan) {
                $gain = ($inversion->inversion * $gainPerLevel1->earnings) / 100;
                $user1->earnings_referralls += $gain;
                $user1->earnings_referralls_to_date += $gain;
                
               if($user1->earnings_referralls > 10){
                    $user1->available_earnings_referralls += $user1->earnings_referralls;
                    $user1->earnings_referralls = 0;
                }
           
                $user1->earnings_to_date += $gain;
                $user1->save();
                $user->earnings_for_father_1 += $gain;
                $inversion->earnings_referred_1 = ($inversion->inversion * $gainPerLevel1->earnings) / 100;
            }

            // $nivel = 1;
            $user2 = User::where('token_reference', $user1->token_reference_father)->first();
            if ($user2) {
                $userplan = UserPlan::where('user_id', $user2->id)->where('status', 'activo')->first();
                if ($userplan) {
                    $gain = ($inversion->inversion * $gainPerLevel2->earnings) / 100;
                    $user2->earnings_referralls += $gain;
                    $user2->earnings_referralls_to_date += $gain;
                    $user2->earnings_to_date += $gain;

                    if($user2->earnings_referralls > 10){
                        $user2->available_earnings_referralls += $user2->earnings_referralls;
                        $user2->earnings_referralls = 0;
                    }
                    $user2->save();
                    $user->earnings_for_father_2 += $gain;
                    $inversion->earnings_referred_2 = ($inversion->inversion * $gainPerLevel2->earnings) / 100;
                   
                }
                // $nivel = 2;
                $user3 = User::where('token_reference', $user2->token_reference_father)->first();
                if ($user3) {
                    // $nivel = 3;
                    $userplan = UserPlan::where('user_id', $user3->id)->where('status', 'activo')->first();
                    if ($userplan) {

                        $gain = ($inversion->inversion * $gainPerLevel3->earnings) / 100;
                        $user3->earnings_referralls_to_date += $gain;
                        $user3->earnings_referralls += $gain;
                        $user3->earnings_to_date += $gain;
                      
                        if($user3->earnings_referralls > 10){
                            $user3->available_earnings_referralls += $user3->earnings_referralls;
                            $user3->earnings_referralls = 0;
                        }
    
                        $user3->save();
                        $user->earnings_for_father_3 += $gain;
                        $inversion->earnings_referred_3 = ($inversion->inversion * $gainPerLevel3->earnings) / 100;
                    }

                }
            }
        }

        $inversion->save();
        $user->save();
        // abonar dinero a referidos padres
        $userplan = UserPlan::find($request->id);
        $data = ['data' => ['plan' => $userplan]];
        Mail::send('mails.confirm_activation_plan', $data, function ($message) use ($user) {
            $message->subject('Respuesta solicitud de activación de plan ' . env('APP_NAME') . ' "Confirmada"');
            $message->to($user->email);
        });
    }

    public function rejectPlan(Request $request)
    {
        $userplan = UserPlan::find($request->id);
        $userplan->date_activated = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        $userplan->date_end = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        $userplan->observations = $request->observations;
        $userplan->status = 'rechazado';
        $userplan->save();

        $data = ['data' => ['plan' => $userplan]];
        Mail::send('mails.reject_activation_plan', $data, function ($message) {
            $message->subject('Respuesta solicitud de activación de Plan ' . env('APP_NAME') . ' "Rechazada"');
            $message->to("eavc53189@gmail.com");
        });
    }

    // front
    public function plan_under_review(Request $request)
    {
        $plan = UserPlan::where('status', 'revision')->where('user_id', Auth::user()->id)->first();
        if (!$plan) {
            $plan = null;
        }
        return $plan;
    }

    public function insert_transaction_number(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }


        $planActive = UserPlan::where('user_id', Auth::user()->id)->where('status', 'revision')->first();
        if ($planActive) {
            return response()->json(['error' => 'Ya posee un plan activo.']);
        }


        $userplan = UserPlan::find($request->id);

        $userplan->transaction_number = $request->transaction_number;

        $userplan->status = 'revision';
        $userplan->date_request = \Carbon\Carbon::now()->format('Y-m-d H:i:s');

        $userplan->save();

        $user = Auth::user();

        $user->pin = $request->pin;
        $user->save();
        $data = ['data' => ['user' => $user, 'plan' => $userplan]];

        Mail::send('mails.request_activation_plan', $data, function ($message) {
            $message->subject('Solicitud de activación de Plan ' . env('APP_NAME'));
            $message->to("eavc53189@gmail.com");
        });

        return response()->json('ok');
    }

    public function request_activation(Request $request)
    {
        $request->validate(
            [
                'id' => 'required',
                'pin' => 'required'
            ]
        );

        $planActive = UserPlan::where('user_id', Auth::user()->id)->where('status', 'revision')->first();
        if ($planActive) {
            return response()->json(['error' => 'Ya posee un plan activo.']);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $userplan = UserPlan::find($request->id);
        $userplan->status = 'revision';
        $userplan->date_request = \Carbon\Carbon::now()->format('Y-m-d H:i:s');

        $userplan->save();

        $user = Auth::user();

        $user->pin = $request->pin;
        $user->save();
        $data = ['data' => ['user' => $user, 'plan' => $userplan]];

        Mail::send('mails.request_activation_plan', $data, function ($message) {
            $message->subject('Solicitud de activación de Plan ' . env('APP_NAME'));
            $message->to("eavc53189@gmail.com");
        });
    }

    public function upload_file(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'file' => 'required|image|mimes:jpeg,png,jpg,svg,webp',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $width_min = 350;
        $width_max = 1200;
        $folder = Auth::user()->id . "/" . "plan/" . $request->id;

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
            $image->type = 'userplan';

            $image->userplan_id = $request->id;
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

    private function deleteImage($local_path)
    {

        if (File::exists($local_path)) {
            $local_path = str_replace("\\", "/", $local_path);
            $positionExt = strripos($local_path, '.');
            $ext = substr($local_path, $positionExt);
            $path_xs = str_replace($ext, '-xs' . $ext, $local_path);
            $path_sm = str_replace($ext, '-sm' . $ext, $local_path);
            File::delete($path_xs);
            File::delete($path_sm);
            File::delete($local_path);
        }
    }

    public function delete_file(Request $request)
    {
        $img = Image::find($request->id);
        if ($img) {
            $this->deleteImage($img->local_path);
            $img->delete();
        }

        return response()->json('Archivo borrado con éxito.');
    }

    public function get(Request $request)
    {
        $userplan = UserPlan::where('id', $request->id)->where('user_id', Auth::user()->id)->first();
        $banks = Bank::all();
        $wallets = Wallet::orderBy('name', 'asc')->get();
        $coins = Coins_wallet::orderBy('name', 'asc')->get();
        $images = Image::where('userplan_id', $request->id)->get();


        return response()->json(compact('banks', 'wallets', 'userplan', 'images', 'coins'));
    }

    public function get_accounts_payment(Request $request)
    {
        $bankss = Bank::all()->first();
        $wallets = Wallet::all()->first();
        return response()->json(compact('banks', 'wallets'));
    }

    public function insertAccountsPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',

            'wallet_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => $validator->errors()]);
        }

        $userplan = UserPlan::find($request->id);

        $userplan->coin_id =  $request->coin_id;
        $userplan->wallet_id =  $request->wallet_id;

        // WALLET
        $wallet = Wallet::find($userplan->wallet_id);
        $userplan->nameWallet = $wallet->name;
        $userplan->addressWallet = $wallet->address;
        $coin = Coins_wallet::find($request->coin_id);
        $userplan->coinWallet = $coin->name;
        $userplan->linkWallet = $wallet->link;
        $userplan->save();
    }

    public function insertAmount(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "id" => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $userplan = UserPlan::find($request->id);
        $inversion =  str_replace(',', '.', $request->inversion);

        if (floatval($inversion) < floatval($userplan->cost)) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode(["inversion" => "La inversión debe ser superior a " . str_replace('.', ',', $userplan->cost)])]);
        }

        $userplan->minimum_charge = ($inversion * 10) / 100;
        $userplan->daily_gain = $userplan->daily_gain_porcentage * $inversion /100;
        $userplan->inversion = $inversion;

        $user = Auth::user();
        if ($user->license_pay == 'Si') {
            $license = 0;
        }

        $userplan->save();

        return response()->json("ok");
    }

    // Configurar plan antes de activar
    public function activate_plan(Request $request)
    {
        $planReview = UserPlan::where('status', 'revision')->where('user_id', Auth::user()->id)->first();
        if ($planReview) {
            return response()->json("plan-review");
        }

        $plan = Plan::find($request->id);

        if (!$plan) {
            return 'no-existe';
        }

        $lastPlanCost = null;
        if(Auth::check()){
            $lastPlanCost = UserPlan::where('user_id',Auth::user()->id)->where('status','!=','vacio')->where('status','!=','imcompleto')->where('status','!=','revision')->where('status','!=','rechazado')->orderBy('cost','desc')->first();
            if($lastPlanCost){
                $lastPlanCost = $lastPlanCost->cost;
            }
        }
        if( $lastPlanCost > $plan->cost){
            return response()->json('disabled');
        }


        $planPending =  UserPlan::where('user_id', Auth::user()->id)->where('status', 'incompleto')->where('plan_id', $plan->id)->first();
        if ($planPending != null) {
            $userplan = $planPending;
        } else {
            // borrar inompleto
            $planPending =  UserPlan::where('user_id', Auth::user()->id)->where('status', 'incompleto')->first();
            if ($planPending) {
                $images = Image::where('userplan_id', $planPending->id)->get();
                foreach ($images as $img) {
                    $local_path = $img->local_path;
                    $this->deleteImage($local_path);
                    $img->delete();
                }
                $planPending->delete();
            }

            $planUserEmpty = UserPlan::where('user_id', Auth::user()->id)->where('status', 'vacio')->where('plan_id', $plan->id)->first();
            if (!$planUserEmpty) {
                // Borrar vacio
                $planUserEmpty = UserPlan::where('user_id', Auth::user()->id)->where('status', 'vacio')->first();
                if ($planUserEmpty) {
                    $images = Image::where('userplan_id', $planUserEmpty->id)->get();
                    foreach ($images as $img) {
                        $local_path = $img->local_path;
                        $this->deleteImage($local_path);
                        $img->delete();
                    }
                    $planUserEmpty->delete();
                }

                $userplan = new UserPlan;
                $userplan->name = $plan->name;
                $userplan->cost = $plan->cost;
                // $userplan->inversion = ;
                $userplan->profit = $plan->profit;

                $userplan->duration = $plan->duration;
                // $userplan->days_of_month = $plan->days_of_month;
                $userplan->duration_months = $plan->duration_months;
                $userplan->duration_days = $plan->duration_days;
                $userplan->weekdays = $plan->weekdays;

              
                $userplan->daily_gain_porcentage = $plan->daily_gain;
               

                // $userplan->minimum_charge = $plan->minimum_charge;
                $userplan->products = $plan->products;
                $userplan->plan_id = $plan->id;
                $userplan->user_id = Auth::user()->id;


                // --------------

                
                $inversion =  str_replace(',', '.', $userplan->cost);
        
      
        
                $userplan->minimum_charge = ($inversion * 10) / 100;
                $userplan->daily_gain = $userplan->daily_gain_porcentage * $inversion /100;
                $userplan->inversion = $inversion;
        
                $user = Auth::user();
                if ($user->license_pay == 'Si') {
                    $license = 0;
                }
        
                $userplan->save();
        
               
            } else {
                $userplan = $planUserEmpty;
            }
        }

        if (Auth::user()->user_verified == "1") {
            $datauser = "verified";
        } else {
            $datauser = "incomplete";
        }

        return response()->json(compact('plan', 'userplan', 'datauser'));
    }
}
