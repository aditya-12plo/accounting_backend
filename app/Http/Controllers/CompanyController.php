<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Http\ResponseFactory;

use App\Models\Company;

class CompanyController extends Controller
{ 
    public function __construct()
    {
         
    }
 
    public function getAll(Request $request){
      $auth           = $request->auth;
      $credentials    = $request->credentials;
      $models  = Company::orderBy('company_id', 'DESC')->get();

        return response()
        ->json(['status'=>200 ,'datas' => ["data" => $models, "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
          'Content-Type'          => 'application/json',
          ])
        ->setStatusCode(200);
    }


}
