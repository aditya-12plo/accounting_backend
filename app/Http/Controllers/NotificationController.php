<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App,DB;
use Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;
use PDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;
 
use App\Models\DataNotif;


class NotificationController extends Controller
{ 
    public function __construct()
    {
         
    }

    public function index(Request $request){
        $auth           = $request->auth;
        $credentials    = $request->credentials;
     

        $perPage        		= $request->per_page;
        $sort_field     		= $request->sort_field;
        $sort_type      		= $request->sort_type;
    
        $token                  = $request->token;
        $status                 = $request->status;
        $channel                = $request->channel;
        $event                  = $request->event;
    
        if(!$sort_field){
            $sort_field = "notification_id";
            $sort_type  = "DESC";
        }
  
        if(!$perPage){
            $perPage    = 10;
        }
            
        $query = DataNotif::orderBy($sort_field,$sort_type);
        
        if ($token) {
            $query = $query->where('token', $token);
        }

        if ($status) {
            $query = $query->where('status', $status);
        }
        
        if ($channel) {
            $like = "%{$channel}%";
            $query = $query->where('channel', 'LIKE', $like);
        }
        
        if ($event) {
            $like = "%{$event}%";
            $query = $query->where('event', 'LIKE', $like);
        }
           
     
        $response = $query->paginate($perPage);
  
        return response()
        ->json(['status'=>200 ,'datas' => ["data" => $response, "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);
        
    }
  
  
}