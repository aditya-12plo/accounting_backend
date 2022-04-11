<?php
namespace App\Http\Middleware;
use Closure;
use Exception;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
class JwtMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        $token 			= $request->bearerToken();
        $refreshToken 	= $this->bearerRefreshToken($request);

        if(!$token) {
            // Unauthorized response if token not there
			
			return response()
				->json(['status'=>401 ,'datas' => null, 'errors' => ['message' => ["Token not provided."]]])
				->withHeaders([
				  'Content-Type'          => 'application/json',
				  ])
				->setStatusCode(401);
        }
        try {
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {
			return response()
				->json(['status'=>401 ,'datas' => null, 'errors' => ['message' => ["Provided token is expired."]]])
				->withHeaders([
				  'Content-Type'          => 'application/json',
				  ])
				->setStatusCode(401);
        } catch(Exception $e) {
			return response()
				->json(['status'=>401 ,'datas' => null, 'errors' => ['message' => ["An error while decoding token."]]])
				->withHeaders([
				  'Content-Type'          => 'application/json',
				  ])
				->setStatusCode(401);
        }
        // $user = User::find($credentials->sub->user_id);
        // Now let's put the user in the request class so that you can grab it from there



        $request->auth 					= $credentials->sub;
        $request->credentials 			= false;
        $getTotalLastLogin 				= $this->getTotalLastLogin($credentials->sub->updated_at);

		if($getTotalLastLogin > 4){

			$getNewToken	= $this->refreshToken($refreshToken);

			if($getNewToken){
				$request->credentials 				= ["access_token" => $getNewToken, "refresh_token" => $credentials->sub->token];
			}else{
				
				return response()
				->json(['status'=>401 ,'datas' => null, 'errors' => ['message' => ["Provided token is expired."]]])
				->withHeaders([
				'Content-Type'          => 'application/json',
				])
				->setStatusCode(401);

			}

		}

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

	
	private function refreshToken($token){
		$user = User::where([["token",$token],["status","active"]])->first();
		if($user){
			User::where("user_id",$user->user_id)->update([
                "updated_at"    => date("Y-m-d H:i:s")
            ]);

			$check  = User::with(["division"])->where("user_id",$user->user_id)->first();
			$payload = [
				'iss' => "token",
				'sub' => $user,
				'iat' => time(),
				'exp' => time() + (1440*60*7)
			];
			
			return JWT::encode($payload, env('JWT_SECRET'));
		}else{
			return false;
		}
	}

 

}