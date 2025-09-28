<?php
namespace App\Http\Middleware;use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtMiddleware extends BaseMiddleware{

	public function handle($request, Closure $next){
		
		try{
			$user = JWTAuth::parseToken()->authenticate();
			if(!$user) throw new Exception('User Not Found');

		}catch(Exception $e){

			if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
				return response()->json(['status' => "error",'message' => 'Token Invalid',],400);
			}elseif($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
					return response()->json(['status' => "error",'message' => 'Token Expired',],400);
			}else{
				if($e->getMessage() === 'User Not Found') {
					return response()->json(["status" => "error","message" => "User Not Found",],400);
				}

				return response()->json(['status' => "error",'message' => 'Authorization Token not found',],400);
			}
			
		}
		return $next($request);
	}
}