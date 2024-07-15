<?php

namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\UserPlan;
use App\Models\License;
use Illuminate\Support\Facades\Validator;
use Auth;
class PlanController extends Controller
{
    // Admin
    public function update_license(Request $request){
        $validator = Validator::make($request->all(), [
            "cost" => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $license = License::first();
        $license->cost = str_replace(',','.',$request->cost);
        $license->save();

        return response()->json(['result'=>'ok','message'=>'Licencia actualizada con éxito.']);
    }

    public function list(){
        $list = Plan::all();
        // $planReview = UserPlan::where('status','revision')->where('user_id',Auth::user()->id)->first();
        $start = '2022-05-25 13:28:13';
        $end = '2022-06-25 13:28:13';

        $from = \Carbon\Carbon::parse($start);
        $to = \Carbon\Carbon::parse($end);

        $weekDay = $from->diffInWeekdays($to);
        $lastPlanCost = null;
        if(Auth::check()){
            $lastPlanCost = UserPlan::where('user_id',Auth::user()->id)->where('status','!=','vacio')->where('status','!=','imcompleto')->where('status','!=','revision')->where('status','!=','rechazado')->orderBy('cost','desc')->first();
            if($lastPlanCost){
                $lastPlanCost = $lastPlanCost->cost;
            }
        }

        return response()->json(compact('list','weekDay','lastPlanCost'));
    }

    public function listAdmin(){
        $list = Plan::all();
        return response()->json(compact('list'));
    }

    public function calculateDurationPlan2($porcentaje){
        $porcentaje = $porcentaje; //% de ganancia
        $cantidad = 100; //100 % de cualquier cantidad
        $countMonth = 0;
        $daysMonth = 30; // días del mes
        $amountPorcentageMonth =  $porcentaje * $cantidad / 200;
        $amountPorcentageDay = $amountPorcentageMonth / $daysMonth;
        $countDays = 0;
        $breakTotal = false;

        for ( $var = 0; $var <= $cantidad;){

            for ( $day = 0; $day <= $daysMonth;){
               
                if(($var + $amountPorcentageDay) <= $cantidad){
                    $var += $amountPorcentageDay;
                    $countDays++;
                    $day++;
                }else{
                    $breakTotal = true;
                    break;
                }
                
            }
    
            if( $breakTotal == true){
                break;
            }
    
            $day = 0;
            
            $countMonth++;
        }

        // verifica si da el monto exacto, si no añade un día, el valor de este dia es el q falta y es distinto al resto
        // if($cantidad - $var > 0){
        //     $countDays += 1;
        // }

        $daysExtras = $countDays - ($countMonth * $daysMonth);
        $duration = $countMonth;

        if($daysExtras == $daysMonth){
            $daysExtras = 0;
            $duration++;
            $countMonth++;
            // si sobre pasa el mes se resta los días del mes, se añade un mes y suman los días extras
        }else if($daysExtras > $daysMonth){
            $daysExtras = $daysExtras - $daysMonth;
            $duration++;
            $countMonth++;
        }

        if($duration == 1){
            $duration = $duration.' mes';
        }else{
            $duration = $duration.' meses';
        }
       
        if($daysExtras != 0){
            $duration = $duration.' y '.$daysExtras.' días';
        }

        return ['duration'=>$duration, 'duration_days'=>$daysExtras,'duration_months'=>$countMonth,'days_of_month'=>$daysMonth];
    }


    public function calculateDurationPlan($daily_gain){
        $days = round((200 / $daily_gain));
        return $days;
    }

    public function store(Request $request){
        
        if($request->id){
            $validator = Validator::make($request->all(), [
                "name" => 'required|unique:plans,id,'.$request->id,
                "cost" => 'required',
                "daily_gain" => 'required',
                "duration" => 'nullable',
               
            ]);
    
            if ($validator->fails()) {
                return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
            }
            $plan = Plan::find($request->id);
        }else{
            $validator = Validator::make($request->all(), [
                "name" => 'required|unique:plans',
                "cost" => 'required',
                "daily_gain" => 'required',
                "duration" => 'nullable',
                
            ]);
    
            if ($validator->fails()) {
                return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
            }
            $plan = new Plan;
        }
        
        $plan->name = $request->name;
        $plan->cost =  str_replace(',','.',$request->cost);
        $plan->daily_gain =  str_replace(',','.',$request->daily_gain);
        $duration = $this->calculateDurationPlan($plan->daily_gain);
     
        $plan->weekdays = $duration;
        // $plan->days_of_month = $duration['days_of_month'];
      
        $plan->save();

        if($request->id){
            return response()->json(['result'=>'ok','message'=>'Plan Creado con éxito']);
        }else{
            return response()->json(['result'=>'ok','message'=>'Datos actualizados con éxito']);
        }
    }

    public function update(Request $request){
        $id =  $request->id;
        $validator = Validator::make($request->all(), [
            "id"=>"required",
            "name" => 'required|unique:plans,id,'.$id,
            "cost" => 'required',
            "profit" => 'required',
            "duration" => 'required',
            "products"=> 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $user =  Plan::find($id);
        $user->name = $request->name;
        $user->cost =  str_replace(',','.',$request->cost);
        $user->profit =  str_replace(',','.',$request->profit);
        $user->duration = $request->duration;
        $user->products = $request->products;
        $user->save();
       
        return response()->json(['result'=>'ok','message'=>'Datos actualizados con éxito.']);
    }

    public function delete(Request $request)
    {
        $Plan = Plan::find($request->id);
        if($Plan){
            $Plan->delete();
        }
        return response()->json(['result'=>'ok','message'=>'Plan eliminado con éxito.']);
    }

}

