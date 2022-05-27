<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Http\ResponseFactory;
 
use App\Models\User;
use App\Events\BudgetingEvent;

class IndexController extends Controller
{ 
    public function __construct()
    {
         
    }

    public function index(Request $request){ 
      
      $message  = trans("translate.welcome")." Accounting System By PT. Aplikasi Pemuda Indonesia";
      $user     = User::where("user_id","1")->first();
      $key_id   = 1;

      event(new BudgetingEvent($user->token,$key_id,$message));
        return response()
        ->json(['status'=>200 ,'datas' => ["message" => $message], 'errors' => null])
        ->withHeaders([
          'Content-Type'          => 'application/json',
          ])
        ->setStatusCode(200);
    } 


}
