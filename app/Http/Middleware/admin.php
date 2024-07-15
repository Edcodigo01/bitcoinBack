<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
class admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(Auth::user()->status == 'disabled'){
            return response()->json([
                'message' => "usuario-bloqueado"
            ], 401); // Status code here
        }else if(Auth::user()->role == "administrador" || Auth::user()->role == "administrador-p"){
            return $next($request);
        }
        return response()->json([
            'message' => "role-sin-permisos"
        ], 401); // Status code here
    }
}
