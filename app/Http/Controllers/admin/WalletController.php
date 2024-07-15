<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\Coin;
use App\Models\Coins_wallet;


use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function list(){
        $list = Wallet::all();
        $coins = Coin::orderBy('name','asc')->get();
        $Coins_wallet = Coins_wallet::all();
        foreach ($list as $key => $l) {
            $array = [];
            $coun = 0;
            foreach ($Coins_wallet as $key2 => $r) {
                if($l->id == $r->wallet_id){
                    $array[$coun++] = ['id'=>$r->coin_id,'name'=>$r->name];
                }
            }
            $l->coins = $array;
        }
        return response()->json(['list'=>$list,'coins'=>$coins]);
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            "name" => 'required|unique:wallets',
            "address" => 'required',
            "link" => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $wallet = new Wallet;
        $wallet->name = $request->name;
        $wallet->address = $request->address;
        // $wallet->coin = $request->coin;
        $wallet->link =$request->link;
        $wallet->save();

        foreach($request->coins as $r){
            $new = new Coins_wallet;
            $new->coin_id = $r['id'];
            $new->wallet_id = $wallet->id;
            $new->name = $r['name'];
            $new->save();
        }

        return response()->json(['result'=>'ok','message'=>'Cartera creada con éxito']);
    }

    public function update(Request $request){
        $id =  $request->id;
        $validator = Validator::make($request->all(), [
            "name" => 'required|unique:wallets,id,'.$id,
            "address" => 'required',
            
            "link" => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error-validation', 'errors' => json_encode($validator->errors())]);
        }

        $wallet = Wallet::find($id);
        $wallet->name = $request->name;
        $wallet->address = $request->address;
        // $wallet->coin = $request->coin;
        $wallet->link =$request->link;
        $wallet->save();
        
        Coins_wallet::where('wallet_id',$id)->delete();
        foreach($request->coins as $r){
            $new = new Coins_wallet;
            $new->coin_id = $r['id'];
            $new->wallet_id = $wallet->id;
            $new->name = $r['name'];
            $new->save();
        }

        return response()->json(['result'=>'ok','message'=>'Datos actualizados con éxito.']);
    }

    public function delete(Request $request)
    {
        $Plan = Wallet::find($request->id);
        $coins = Coins_wallet::where('wallet_id',$request->id)->delete();
        if($Plan){
            $Plan->delete();
        }
        return response()->json(['result'=>'ok','message'=>'Cartera eliminada con éxito.']);
    }
}
