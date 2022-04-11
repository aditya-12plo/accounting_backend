<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App,DB;
use Firebase\JWT\JWT;
use PDF;
use Illuminate\Support\Facades\Mail;

use App\Mail\ForgotPasswordNotification;
use App\Models\Log;
use App\Models\User;
use App\Models\Company;

class AuthController extends Controller
{ 
    public function __construct()
    {
         
    }

    public function index(Request $request){ 

        return response()
        ->json(['status'=>200 ,'datas' => ["message" => "welcome to dashboard"], 'errors' => null])
        ->withHeaders([
          'Content-Type'          => 'application/json',
          ])
        ->setStatusCode(200);
    
    }

    public function userChangePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required|email|max:255',
            'token'         => 'required',
            'password'      => 'required',
        ]);

        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        $check  = User::where([["email",$request->email],["token",$request->token],["status","active"]])->first();
        if($check){
            
            User::where("user_id",$check->user_id)->update([
                "password"      => sha1($request->password),
                "updated_at"    => date("Y-m-d H:i:s")
            ]);

            return response()
            ->json(['status'=>200 ,'datas' =>["messages" => "Successfully"], 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(200);

        }else{
            $errors = [
                "email"   => ["Email does not exist"]
            ];
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $errors])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);

        }
    }


    public function userResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required|email|max:255'
        ]);

        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        $check  = User::where([["email",$request->email],["status","active"]])->first();
        if($check){
            $subject    = 'ICDX GROUP ACCOUNTING & BUDGETING SYSTEM - Reset Password';
            $emails     = [
                array(
                    'email' => $check->email,
                    'name'  => $check->name,
                    'type'  => 'to'
                ),
            ];
            
            $link       =  env('FRONT_URL')."/reset-password/{$check->email}/{$check->token}";


            Mail::to($emails)->send(new ForgotPasswordNotification($subject,$check,$link));

            return response()
            ->json(['status'=>200 ,'datas' =>["messages" => "Successfully send to your email"], 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(200);

        }else{
            $errors = [
                "email"   => ["Email does not exist"]
            ];
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $errors])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);

        }

    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required|email|max:255', 
            'password'      => 'required'
        ]);

        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        $check  = User::with(["division"])->where([["email",$request->email],["password",sha1($request->password)],["status","active"]])->first();
        if($check){
            $token  = $this->jwt($check);
             
            User::where("user_id",$check->user_id)->update([
                "updated_at"    => date("Y-m-d H:i:s")
            ]);

            $response = [
                'access_token'  => $token,
                'refresh_token' => $check->token,
                'token_type'    => 'bearer',
                'expires_in'    => time() + (1440*60*7)
            ];

            return response()
            ->json(['status'=>200 ,'datas' => $response, 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(200);

        }else{
            $errors = [
                "email"   => ["Email / Password not match"]
            ];
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $errors])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);

        }
    }

    private function jwt(User $user) {
        
        $payload = [
            'iss' => "token",
            'sub' => $user,
            'iat' => time(),
            'exp' => time() + (1440*60*7)
        ];
        
        return JWT::encode($payload, env('JWT_SECRET'));
    
    }



    public function profile(Request $request){ 
        $auth               = $request->auth;
        $credentials        = $request->credentials;

        return response()
        ->json(['status'=>200 ,'datas' =>["auth" => $auth, "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
          'Content-Type'          => 'application/json',
          ])
        ->setStatusCode(200);
    
    }


}