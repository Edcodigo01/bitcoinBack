<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function delete_img(Request $request){
        $img = Image::find($request->id);
        if($img){
            $this->deleteImage($img->local_path);
            $img->delete();
        }
        return response()->json('Archivo eliminado con éxito.');
    }

    private function deleteImage($local_path){
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
  
}
