<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Withdrawal;
use App\Models\Image;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Auth;
use Mail;
use ImageIntervention;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class WithdrawalController extends Controller
{
    //
    public function list(Request $request){
        $withdrawals = Withdrawal::leftJoin('users', 'users.id', '=', 'withdrawals.user_id')
        ->select('withdrawals.*','users.alias as userAlias')
        ->where('withdrawals.status','!=','imcompleto')
        ->where('withdrawals.status','!=','incompleto')
        ->get();

        return response()->json(["withdrawals"=>$withdrawals]);
    }

    public function get(Request $request){
        $withdrawal = Withdrawal::find($request->id);
        $images = Image::where('withdrawal_id',$request->id)->where('type','pay_withdrawal')->get();
        $user = User::find($withdrawal->user_id);

        return response()->json(compact('withdrawal','images','user'));
    }

    public function upload_file(Request $request){

        $validator = Validator::make($request->all(), [
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }
    
        $width_min = 350;
        $width_max = 1200;
        $folder = Auth::user()->id."/"."withdrawal/".$request->id;
    
        if ($request->hasFile('file')) {
            $file = ImageIntervention::make($request->file('file')->getRealPath());
            if ($file->width() < $width_min) {
                return response()->json(["result" => "error", "message" => "La imagen debe tener un tamaño superior a " . $width_min . " píxeles."]);
            }
    
            $extension = $request->file('file')->getClientOriginalExtension();
            $fileName   = \Carbon\Carbon::now()->format('dmYHms').Str::random(10);
    
            $url_path = asset('images/user/'.$folder.'/' . $fileName . '.' . $extension);
            $local_path = public_path('images/user/'.$folder.'/'. $fileName . '.' . $extension);
            $image = new Image;
    
            // $image->name = $fileName.$extension;
            $image->url_path = $url_path;
            $image->local_path = $local_path;
            $image->type = 'pay_withdrawal';
    
            $image->withdrawal_id = $request->id;
            $image->user_id = Auth::user()->id;
        
            $image->save();
    
            // make dir
            if (!File::exists('images')) {
                File::makeDirectory('images');
            }
    
            if (!File::exists('images/user')) {
                File::makeDirectory('images/user');
            }
    
            if (!File::exists('images/user/'.Auth::user()->id)) {
                File::makeDirectory('images/user/'.Auth::user()->id);
            }
    
            if (!File::exists('images/user/'.Auth::user()->id.'/withdrawal')) {
                File::makeDirectory('images/user/'.Auth::user()->id.'/withdrawal');
            }
    
            if (!File::exists('images/user/'.$folder)) {
                File::makeDirectory('images/user/'.$folder);
            }
    
            //move image to img folder
            if ($file->width() > $width_max) {
                $img = $file->resize($width_max, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $img->save('images/user/'.$folder.'/'. $fileName . '.' . $extension);
            } else {
                $file->save('images/user/'.$folder.'/' . $fileName . '.' . $extension);
            }
    
            return response()->json(["result" => "success", "message" => "Archivo subido con éxito."]);
        } else {
            return response()->json("Hubo un error al intentar subir este archivo.");
        }
    }

    public function getImages(Request $request){
        $images = Image::where('withdrawal_id',$request->id)->where('type','pay_withdrawal')->get();
    }

    public function confirmPay(Request $request){
        $withdrawal = Withdrawal::find($request->id);
        $withdrawal->date_response = \Carbon\Carbon::now()->format('Y-m-d H:i:s');

        $user = User::find($withdrawal->user_id);
        if($withdrawal->type == 'earnings'){
            $type = 'ganancias';
            if($withdrawal->request > $user->earnings_procesing){
                return response()->json(["result" => "error", "message" => "Saldo insuficiente."]);
            }
            $user->earnings_procesing = $user->earnings_procesing - $withdrawal->request;
        }else if($withdrawal->type == 'earnings-referralls'){
            $type = 'ganancias por referidos';
            if($withdrawal->request > $user->available_earnings_referralls_procesing){
                return response()->json(["result" => "error", "message" => "Saldo insuficiente."]);
            }
            $user->available_earnings_referralls_procesing = $user->available_earnings_referralls_procesing - $withdrawal->request;
        
        }else{
            $type = 'inversión';
            if($withdrawal->request > $user->inversion_procesing){
                return response()->json(["result" => "error", "message" => "Saldo insuficiente."]);
            }
            $user->inversion_procesing = $user->inversion_procesing - $withdrawal->request;
        }

        

        $withdrawal->status = 'aprobado';
        $data = ['data'=>['user'=>$user,'withdrawal'=>$withdrawal,'type'=>$type]];

        Mail::send('mails.withdrawal_completed',$data,function($message) use($user,$withdrawal,$type){
            $message->subject('Se ha abonado a su cuenta el retiro de '.$type.' '.env('APP_NAME').' solicitado.');
            $message->to($user->email);
        });

        $user->save();
        $withdrawal->save();
        return response()->json(["result" => "ok", "message" => "Solicitud marcada como pagada, se ha enviado un correo al usuario para notificarle."]);
    }

}
