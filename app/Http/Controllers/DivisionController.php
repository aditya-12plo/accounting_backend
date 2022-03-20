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
use App\Models\Division;

class DivisionController extends Controller
{ 
    public function __construct()
    {
         
    }


    
    public function index(Request $request){
        $auth                   = $request->auth;


        $perPage        		= @$request->per_page;
        $sort_field     		= @$request->sort_field;
        $sort_type      		= @$request->sort_type;
		
        $name     				= @$request->name;
        $code     				= @$request->code;
        $download  				= @$request->download;


        if(!$sort_field){
            $sort_field = "division_id";
            $sort_type  = "DESC";
        }

        if(!$perPage){
            $perPage    = 10;
        }
        
		$query = Division::orderBy($sort_field,$sort_type);
		
		if ($name) {
            $like = "%{$name}%";
            $query = $query->where('name', 'LIKE', $like);
        }
		
		if ($code) {
            $like = "%{$code}%";
            $query = $query->where('code', 'LIKE', $like);
        }

        if($download == "download"){
            $response = $query->get();
        }else{
            $response = $query->paginate($perPage);
        }

        return response()
        ->json(['status'=>200 ,'datas' => $response, 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);

    }

}