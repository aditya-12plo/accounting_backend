<?php
namespace App\Http\Middleware;
use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
 

use App\Models\User;

class JwtMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        $token 					= $request->bearerToken();
        $refreshToken 			= $this->bearerRefreshToken($request);
        $request->credentials	= false;


        if(!$token) {
            // Unauthorized response if token not there
			
			$message = trans("translate.tokenprovided");
			return response()
				->json(['status'=>401 ,'datas' => null, 'errors' => ['message' => [$message]]])
				->withHeaders([
				  'Content-Type'          => 'application/json',
				  ])
				->setStatusCode(401);
        }
        try {
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {

			if($e->getMessage() == "Expired token"){
				
				if($refreshToken){
					$getUser	= User::with(["division","user_company.company"])->where([["token",$refreshToken],["status","active"]])->first();
					
					if($getUser){

						$getTotalLastLogin 				= $this->getTotalLastLogin($getUser->updated_at);
						if($getTotalLastLogin < 30){

							$getNewToken			= $this->refreshToken($getUser);
							
							$request->auth			= $getUser;
							$request->credentials	= ["access_token" => $getNewToken, "refresh_token" => $refreshToken];
						
							return $next($request);

						}else{
							
							$message = trans("translate.ProvidedTokenexpired");
							return response()
								->json(['status'=>401 ,'datas' => null, 'errors' => ['message' => [$message]]])
								->withHeaders([
								'Content-Type'          => 'application/json',
								])
								->setStatusCode(401);

						}

					}else{

						$message = trans("translate.ProvidedTokenexpired");
						return response()
							->json(['status'=>401 ,'datas' => null, 'errors' => ['message' => [$message]]])
							->withHeaders([
							  'Content-Type'          => 'application/json',
							  ])
							->setStatusCode(401);

					}
 
				}else{

					$message = trans("translate.ProvidedTokenexpired");
					return response()
						->json(['status'=>401 ,'datas' => null, 'errors' => ['message' => [$message]]])
						->withHeaders([
						  'Content-Type'          => 'application/json',
						  ])
						->setStatusCode(401);
				}

			}else{

				$message = trans("translate.ProvidedTokenexpired");
				return response()
					->json(['status'=>401 ,'datas' => null, 'errors' => ['message' => [$message]]])
					->withHeaders([
					  'Content-Type'          => 'application/json',
					  ])
					->setStatusCode(401);

			}

        } catch(Exception $e) {
			$message = trans("translate.tokendecoding");
			return response()
				->json(['status'=>401 ,'datas' => null, 'errors' => ['message' => [$message]]])
				->withHeaders([
				  'Content-Type'          => 'application/json',
				  ])
				->setStatusCode(401);
        } 
     
        $request->auth 					= $credentials->sub;
        return $next($request);
    }
	
	public function bearerToken(){
		$header	= $this->header('Authorization','');
		if(Str::startsWith($header, 'Bearer ')){
			return Str::substr($header, 7);
		}
	}
	
	private function bearerRefreshToken($request){
		$header = explode(':', $request->header('Refresh-Token'));
		$refreshToken = @trim($header[0]);
		return $refreshToken;
	}

	
	private function getTotalLastLogin($date1){
		$date2 = date("Y-m-d H:i:s");

		$diff = abs(strtotime($date2) - strtotime($date1));

		$years 	= floor($diff / (365*60*60*24));
		$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
		$days 	= floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));

		return $days;
	}

	
	private function refreshToken($user){
		User::where("user_id",$user->user_id)->update([
			"updated_at"    => date("Y-m-d H:i:s")
		]);
 
		$payload = [
			'iss' => "token",
			'sub' => $user,
			'iat' => time(),
			'exp' => time() + (1440*60*7)
		];
		
		return JWT::encode($payload, env('JWT_SECRET'));
	}

 

}