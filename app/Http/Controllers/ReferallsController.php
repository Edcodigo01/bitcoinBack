<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ReferralEarnings;
use App\Models\Inversion;

use Auth;
class ReferallsController extends Controller
{
    public function referalls_user(Request $request){
        $user = Auth::user();
        $referalls = User::where('token_reference_father',$user->token_reference)->whereNotNull('email_verified_at')->get();
        $allusers = User::whereNotNull('email_verified_at')->get();
        foreach($referalls as $user){
            $referidos2 = [];
            $count = 0;
            foreach($allusers as $user2){
                if( $user2->token_reference_father == $user->token_reference){
                    // referidos 3
                    $referidos3 = [];
                    $count2 = 0;
                    foreach($allusers as $user3){
                        if( $user3->token_reference_father == $user2->token_reference){
                            $referidos3[$count2] = $user3;
                            $count2++;
                        }
                    }
                    $user2->referalls = $referidos3;
                    // referidos 2
                    $referidos2[$count] = $user2;
                    $count++;
                }
            }
            $user->referalls = $referidos2;
        }

        $token_reference = User::find(Auth::user()->id)->token_reference;
        return response()->json(compact('referalls','token_reference'));
    }

    public function referalls(Request $request){
        $user = Auth::user();
        $referalls = User::whereNotNull('email_verified_at')->get();
        $allusers = User::whereNotNull('email_verified_at')->get();
        foreach($referalls as $user){
            $referidos2 = [];
            $count = 0;
            foreach($allusers as $user2){
               
                if( $user2->token_reference_father == $user->token_reference){
                    // referidos 3
                    $referidos3 = [];
                    $count2 = 0;
                    foreach($allusers as $user3){
                        if( $user3->token_reference_father == $user2->token_reference){
                            $referidos3[$count2] = $user3;
                            $count2++;
                        }
                    }
                    $user2->referalls = $referidos3;
                    // referidos 2
                    $referidos2[$count] = $user2;
                    $count++;
                }
            }
            $user->referalls = $referidos2;
        }

        $token_reference = User::find($user->id)->token_reference;
        return response()->json(compact('referalls','token_reference'));
    }

    public function getReference(Request $request){
        $useralias = User::where('token_reference',$request->token_reference)->first();
         if($useralias){
            $useralias = $useralias->alias;
         }
         return response()->json($useralias);
    }

    public function earnings_referralls(Request $request){
        $ReferralEarnings = ReferralEarnings::orderBy('name','asc')->get();
        return  $ReferralEarnings;
    }

    public function update_earnings_referralls(Request $request){
        $ReferralEarnings = ReferralEarnings::find($request->id);
        $ReferralEarnings->earnings = str_replace(',', '.', $request->earnings) ;
        $ReferralEarnings->save();

        return response()->json(['message'=>'Datos actualizados con éxito.']);
    }

    public function get_referall_details(Request $request){
       
        $user = User::where('token_reference',$request->token)->first();
      
        if($user->token_reference_father == $request->token_father){
            $level = 1;
        }else{
            $user2 = User::where('token_reference',$user->token_reference_father)->first();
            if( $user2 and $user2->token_reference_father == $request->token_father){
                $level = 2;
            }else if($user2){
                $user3 = User::where('token_reference',$user2->token_reference_father)->first();
                if( $user3 and $user3->token_reference_father == $request->token_father){
                    $level = 3;
                }
            }
        }

        $inversions = Inversion::where('user_id',$user->id)->get();
        $user->level = 'Nivel '.$level;
        return response()->json(compact('inversions','user','level'));

    }
}
