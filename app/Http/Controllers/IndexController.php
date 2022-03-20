<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Http\ResponseFactory;
 
class IndexController extends Controller
{ 
    public function __construct()
    {
         
    }

    public function index(Request $request){ 
        return response()
        ->json(['status'=>200 ,'datas' => ["message" => "Accounting System By PT. Aplikasi Pemuda Indonesia"], 'errors' => null])
        ->withHeaders([
          'Content-Type'          => 'application/json',
          ])
        ->setStatusCode(200);
    } 


}
