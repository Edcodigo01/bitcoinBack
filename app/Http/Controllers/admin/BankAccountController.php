<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bank;
use Illuminate\Support\Facades\Validator;

class BankAccountController extends Controller
{
    public function list(){
        $list = Bank::all();
        return response()->json($list);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'holder' => 'required',
            'identification' => 'required',
            'type' => 'required',
            'numberAccount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $user = new Bank;
        $user->name = $request->name;
        $user->holder = $request->holder;
        $user->identification = $request->identification;
        $user->type = $request->type;
        $user->numberAccount = $request->numberAccount;
        $user->save();

        return response()->json(['result'=>'ok','message'=>'Cuenta Bancaria agregada con éxito']);
    }

    public function update(Request $request){
        $id =  $request->id;
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'holder' => 'required',
            'identification' => 'required',
            'type' => 'required',
            'numberAccount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $user = Bank::find($id);
        $user->name = $request->name;
        $user->holder = $request->holder;
        $user->identification = $request->identification;
        $user->type = $request->type;
        $user->numberAccount = $request->numberAccount;
        $user->save();

        return response()->json(['result'=>'ok','message'=>'Datos actualizados con éxito.']);
    }

    public function delete(Request $request)
    {
        $bank = Bank::find($request->id);
        if($bank){
            $bank->delete();
        }
        return response()->json(['result'=>'ok','message'=>'Cuenta Bancaria eliminada con éxito.']);
    }

}
