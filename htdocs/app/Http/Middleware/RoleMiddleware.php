<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\Action;
use App\Models\ActionMaster;

class RoleMiddleware
{
    
    public function handle($request, Closure $next, ...$roles){
        $user = Auth::user();
        $routName=Route::currentRouteName();
        if($user){
            if($request->ajax() || ($user->user_id==1) ){
                return $next($request);
            }else{
                $master=ActionMaster::whereRaw('actmas_action_name="'.$routName.'" OR  FIND_IN_SET(actmas_action_name2,"'.$routName.'")  ')->first();
                if($master){
                    $access=Action::where('act_role',$user->role_id)->where('act_masid',$master->actmas_id)->first();
                    if($access)
                        return $next($request);
                    else
                        abort(403, 'Sorry! You are not allowed to access this page.');
                }else{
                    abort(403, 'Sorry! You are not allowed to access this page.');
                }
            }
        }
        //abort(403, 'Sorry! You are not allowed to access this page.');
        return redirect()->route('index');
    }
}
