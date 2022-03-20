<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App,DB;
use Firebase\JWT\JWT;
use PDF;

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
             
            $response = [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => time() + (1440*60*4)
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
            'exp' => time() + (1440*60*4)
        ];
        
        return JWT::encode($payload, env('JWT_SECRET'));
    
    }



    public function profile(Request $request){ 
        $auth = $request->auth;
        return response()
        ->json(['status'=>200 ,'datas' => $auth, 'errors' => null])
        ->withHeaders([
          'Content-Type'          => 'application/json',
          ])
        ->setStatusCode(200);
    
    }


}