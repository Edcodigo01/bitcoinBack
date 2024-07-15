<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coin;


use Illuminate\Support\Facades\Validator;

class CoinController extends Controller
{
    public function list_coins()
    {
        $list = Coin::all();
     
        return response()->json(["list"=>$list]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:coins',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $data = new Coin;
        $data->name = $request->name;
        $data->save();

        return response()->json(['result'=>'ok','message'=>'Moneda agregada con éxito']);
    }

    public function update(Request $request)
    {
        $id = $request->id;
        $validator = Validator::make($request->all(), [
            'id'=>'required|integer',
            'name' => 'required|unique:coins,name,'.$id,
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $data = Coin::find($id);
        $data->name = $request->name;
        $data->save();

        return response()->json(['result'=>'ok','message'=>'Datos actualizados con éxito']);
    }

    public function delete(Request $request)
    {
        $data = Coin::find($request->id);
        
        if($data){
            $data->delete();
        }
        return response()->json(['result'=>'ok','message'=>'Moneda eliminada con éxito']);
    }

}
