<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App,DB;
use Firebase\JWT\JWT;
use PDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

use App\Models\Log;
use App\Models\User;
use App\Models\Company;

class UserController extends Controller
{ 
    public function __construct()
    {
         
    }


    public function changePassword(Request $request)
    {
        $auth = $request->auth;

        $validator = Validator::make($request->all(), [
            'password'              => 'required|confirmed',
            'password_confirmation' => 'required', 
        ]);

        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        User::where("user_id",$auth->user_id)->update(["password" => sha1($request->password)]);

        return response()
        ->json(['status'=>200 ,'datas' => ["messages" => "Successfully"], 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);
    }

    
    public function index(Request $request){
        $auth                   = $request->auth;

        if($auth->level != "ROOT"){
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
        }

        $perPage        		= $request->per_page;
        $sort_field     		= $request->sort_field;
        $sort_type      		= $request->sort_type;
		
        $name     				= $request->name;
        $email     				= $request->email;
        $level     				= $request->level;
        $status   				= $request->status;
        $division_name          = $request->division_name;
        $division_code          = $request->division_code;
		
        if(!$sort_field){
            $sort_field = "user_id";
            $sort_type  = "DESC";
        }

        if(!$perPage){
            $perPage    = 10;
        }
        
		$query = User::with(["division"])->orderBy($sort_field,$sort_type);
		
		if ($name) {
            $like = "%{$name}%";
            $query = $query->where('name', 'LIKE', $like);
        }
		
		if ($email) {
            $like = "%{$email}%";
            $query = $query->where('email', 'LIKE', $like);
        }
		
		
		if ($level) {
            $like = "%{$level}%";
            $query = $query->where('level', 'LIKE', $like);
        }
		
		
		if ($division_name) {
            $like = "%{$division_name}%";
            $query = $query->whereHas('division', function($q) use($like){
                $q->where('name', 'LIKE', $like);
            });
        }
		
		
		if ($division_code) {
            $like = "%{$division_code}%";
            $query = $query->whereHas('division', function($q) use($like){
                $q->where('code', 'LIKE', $like);
            });
        }
		
		
		if ($status) {
            $query = $query->where('status', $status);
        }
		 

		$response = $query->paginate($perPage);

        return response()
        ->json(['status'=>200 ,'datas' => $response, 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);
    }


    public function updateStatus(Request $request,$user_id)
    {
        $auth                   = $request->auth;

        $validator = Validator::make($request->all(), [
            'status'         => 'required|in:active,deactived',
        ]);

        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        User::where("user_id",$user_id)->update(["status" => $request->status]);
        return response()
        ->json(['status'=>200 ,'datas' => ["messages" => "Successfully"], 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);

    }

    public function detail(Request $request,$user_id)
    {
        $auth                   = $request->auth;

        $check = User::with(["division"])->where("user_id",$user_id)->first();

        return response()
        ->json(['status'=>200 ,'datas' => $check, 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);

    }


    public function update(Request $request,$user_id)
    {
        $auth                   = $request->auth;

        $validator = Validator::make($request->all(), [
            'name'              => 'required|max:255',
            'email'             => 'required|max:255|without_spaces|email|unique:user,email,'.$user_id.',user_id',
            'division_id'       => 'required|integer', 
            'status'            => 'required|in:active,deactived',
        ]);

        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        $model  = User::where("user_id",$user_id)->first();
        if($model){
            if($request->password){

                User::where("user_id",$user_id)->update([
                    "name"          => $request->name,
                    "email"         => $request->email,
                    "division_id"   => $request->division_id,
                    "status"        => $request->status,
                    "password"      => sha1($request->password),
                ]);

            }else{

                User::where("user_id",$user_id)->update([
                    "name"          => $request->name,
                    "email"         => $request->email,
                    "division_id"   => $request->division_id,
                    "status"        => $request->status,
                ]);
                
            }

            return response()
            ->json(['status'=>200 ,'datas' => ["messages" => "Successfully"], 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(200);

        }else{

            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => ["user_id" => "user id These credentials do not match our records"]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);

        }
        

    }

    public function create(Request $request)
    {
        $auth                   = $request->auth;

        $validator = Validator::make($request->all(), [
            'name'              => 'required|max:255',
            'password'          => 'required|max:255',
            'email'             => 'required|max:255|without_spaces|email|unique:user,email',
            'division_id'       => 'required|integer', 
            'status'            => 'required|in:active,deactived',
        ]);

        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        User::create([
            "name"          => $request->name,
            "email"         => $request->email,
            "division_id"   => $request->division_id,
            "status"        => $request->status,
            "password"      => sha1($request->password),
        ]);

        return response()
            ->json(['status'=>200 ,'datas' => ["messages" => "Successfully"], 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(200);

    }


    public function download(Request $request){
        $auth                   = $request->auth;

        if($auth->level != "ROOT"){
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
        }

        $perPage        		= $request->per_page;
        $sort_field     		= $request->sort_field;
        $sort_type      		= $request->sort_type;
		$file_name				= $request->file_name;
		
        $name     				= $request->name;
        $email     				= $request->email;
        $level     				= $request->level;
        $status   				= $request->status;
        $division_name          = $request->division_name;
        $division_code          = $request->division_code;
		
        if(!$sort_field){
            $sort_field = "user_id";
            $sort_type  = "DESC";
        }

        if(!$perPage){
            $perPage    = 10;
        }
        
		$query = User::with(["division"])->orderBy($sort_field,$sort_type);
		
		if ($name) {
            $like = "%{$name}%";
            $query = $query->where('name', 'LIKE', $like);
        }
		
		if ($email) {
            $like = "%{$email}%";
            $query = $query->where('email', 'LIKE', $like);
        }
		
		
		if ($level) {
            $like = "%{$level}%";
            $query = $query->where('level', 'LIKE', $like);
        }
		
		
		if ($division_name) {
            $like = "%{$division_name}%";
            $query = $query->whereHas('division', function($q) use($like){
                $q->where('name', 'LIKE', $like);
            });
        }
		
		
		if ($division_code) {
            $like = "%{$division_code}%";
            $query = $query->whereHas('division', function($q) use($like){
                $q->where('code', 'LIKE', $like);
            });
        }
		
		
		if ($status) {
            $query = $query->where('status', $status);
        }
		 

		$datas	= $query->get();

		$file_path  	= storage_path('xlsx/download') . '/' . $file_name;

		$spreadsheet 	= new Spreadsheet();
		$sheet 			= $spreadsheet->getActiveSheet();
		$sheet->setCellValue('A1', 'Name');
		$sheet->setCellValue('B1', 'Email');
		$sheet->setCellValue('C1', 'Division Code');
		$sheet->setCellValue('D1', 'Division Name');
		$sheet->setCellValue('E1', 'Level');
		$sheet->setCellValue('F1', 'Status');
		$sheet->setCellValue('G1', 'Created At');
		$sheet->setCellValue('H1', 'Updated At');

		if(count($datas) > 0){
			$x=2;
			foreach($datas as $data){
                $sheet->setCellValue('A'.$x, $data->name);
                $sheet->setCellValue('B'.$x, $data->email);
                $sheet->setCellValue('C'.$x, $data->division->code);
                $sheet->setCellValue('D'.$x, $data->division->name);
                $sheet->setCellValue('E'.$x, $data->level);
                $sheet->setCellValue('F'.$x, $data->status);
                $sheet->setCellValue('G'.$x, $data->created_at);
                $sheet->setCellValue('H'.$x, $data->updated_at);
                
                $x++;
			}
		}

        
		$writer = new Xlsx($spreadsheet);
		$writer->save($file_path); 
		 $headers	= ['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment'];
		if (file_exists($file_path)) {
		  $file = file_get_contents($file_path);
		  $res = response($file, 200)->withHeaders(['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment;filename="'.$file_name.'"']);
		   register_shutdown_function('unlink', $file_path);
		   return $res;
		}else{
			return response()
					->json(['status'=>500 ,'datas' => null, 'errors' => ['product_code' => 'download file error']])
					->withHeaders([
						'Content-Type'          => 'application/json',
					  ])
					->setStatusCode(500);
		}

    }


}