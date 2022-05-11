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

use App\Models\Log;
use App\Models\User;
use App\Models\Company;
use App\Models\DivisionMaster;
use App\Models\UserCompany;

class UserController extends Controller
{ 
    public function __construct()
    {
         
    }


    public function changePassword(Request $request)
    {
        $auth           = $request->auth;
        $credentials    = $request->credentials;

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

        User::where("user_id",$auth->user_id)->update(["password" => sha1($request->password),"updated_at"    => date("Y-m-d H:i:s")]);

        return response()
        ->json(['status'=>200 ,'datas' => ["messages" => "Successfully", "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);
    }

    
    public function index(Request $request){
        $auth           = $request->auth;
        $credentials    = $request->credentials;

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
        $division_id            = $request->division_id;
        $download               = $request->download;
		
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
		
		
		if ($division_id) {
            $like = "%{$division_id}%";
            $query = $query->where('division_id', 'LIKE', $like);
        }
		
		
		if ($status) {
            $query = $query->where('status', $status);
        }
		 
        if($download == "download"){
            $response    = $query->get();
            return $this->downloadData($response);
        }else{

            $response = $query->paginate($perPage);

            return response()
            ->json(['status'=>200 ,'datas' => ["data" => $response, "credentials" => $credentials], 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(200);
        }
        
    }


    private function downloadData($datas){
        set_time_limit(0);
        error_reporting(0);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0); 

        $file_name      = uniqid().".xlsx";
		$file_path  	= storage_path('download') . '/' . $file_name;

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

    public function updateStatus(Request $request,$user_id)
    {
        $auth           = $request->auth;
        $credentials    = $request->credentials;
        
        if($auth->level != "ROOT"){
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
        }

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

        User::where("user_id",$user_id)->update(["status" => $request->status,"updated_at" => date("Y-m-d H:i:s")]);
        $message = trans("translate.Successfully");
        return response()
        ->json(['status'=>200 ,'datas' => ["messages" => $message , "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);

    }

    public function detail(Request $request,$user_id)
    {
        $auth           = $request->auth;
        $credentials    = $request->credentials;

        if($auth->level != "ROOT"){
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
        }

        $check = User::with(["division","user_company.company"])->where("user_id",$user_id)->first();

        return response()
        ->json(['status'=>200 ,'datas' => ["data" => $check , "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);

    }


    public function update(Request $request,$user_id)
    {
        $auth           = $request->auth;
        $credentials    = $request->credentials;

        if($auth->level != "ROOT"){
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
        }

        $validator = Validator::make($request->all(), [
            'name'              => 'required|max:255',
            'email'             => 'required|max:255|without_spaces|email|unique:user,email,'.$user_id.',user_id',
            'division_id'       => 'required|max:255', 
            'status'            => 'required|in:active,deactived',
            'level'             => 'required|in:STAFF,ROOT',
            'company_ids'       => 'required', 
            'signature_file'    => 'mimes:jpeg,jpg,png|max:20000' // max 20000 kb
        ]);

        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        $json_decode = @json_decode(@$request->company_ids);
        if (!$json_decode) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => ["company_ids" => ["The company ids must be an array."]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        $model  = User::where("user_id",$user_id)->first();
        if($model){

            $checkDivisionMaster = DivisionMaster::where("division_id",$request->division_id)->first();
            if($checkDivisionMaster){

                if($request->password){

                    User::where("user_id",$user_id)->update([
                        "name"          => $request->name,
                        "email"         => $request->email,
                        "division_id"   => $checkDivisionMaster->division_id,
                        "status"        => $request->status,
                        "password"      => sha1($request->password),
                        "updated_at"    => date("Y-m-d H:i:s")
                    ]);
    
                }else{
    
                    User::where("user_id",$user_id)->update([
                        "name"          => $request->name,
                        "email"         => $request->email,
                        "division_id"   => $checkDivisionMaster->division_id,
                        "status"        => $request->status,
                        "updated_at"    => date("Y-m-d H:i:s")
                    ]);
                    
                }

                if($request->signature_file){
                    
                    if($model->signature_file){
                        $file_path = 'signature/'.$model->signature_file;
                        unlink($file_path);
                    }

 
                    $file       = $request->file('signature_file');
                    $extension  = $file->getClientOriginalExtension();
                    $imageName  = uniqid()."-".time().'.'.$extension;
        
                    $file->move('signature/', $imageName);

                    User::where("user_id",$user_id)->update([
                        "signature_file"          => $imageName
                    ]);
                }


                UserCompany::where('user_id', $user_id)->delete();
    
                $companys = $json_decode;

                for($x=0;$x<count($companys);$x++){
                    $checkCompany   = Company::where("company_id",$companys[$x])->first();
                    if($checkCompany){
                        $UserCompany                = new UserCompany;
                        $UserCompany->user_id       = $user_id;                
                        $UserCompany->company_id    = $checkCompany->company_id;                
                        $UserCompany->save();
                    }
                }


                $message = trans("translate.Successfully");
                return response()
                ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(200);

            }else{
                $message = trans("translate.Divisionotmatchrecords");
                return response()
                ->json(['status'=>422 ,'datas' => null, 'errors' => ["division_id" => $message]])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(422);
            }


        }else{
            $message = trans("translate.usercredentialsnotmatchrecords");
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => ["user_id" => $message]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);

        }
        

    }

    public function create(Request $request)
    {
        $auth           = $request->auth;
        $credentials    = $request->credentials;

        if($auth->level != "ROOT"){
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
        }

        $validator = Validator::make($request->all(), [
            'name'              => 'required|max:255',
            'password'          => 'required|max:255',
            'level'             => 'required|in:STAFF,ROOT',
            'email'             => 'required|max:255|without_spaces|email|unique:user,email',
            'division_id'       => 'required|max:255', 
            'company_ids'       => 'required', 
            'status'            => 'required|in:active,deactived',
            'signature_file'    => 'required|mimes:jpeg,jpg,png|max:20000' // max 20000 kb
        ]);

        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        $json_decode = @json_decode(@$request->company_ids);
        if (!$json_decode) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => ["company_ids" => ["The company ids must be an array."]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        $checkDivisionMaster = DivisionMaster::where("division_id",$request->division_id)->first();
        if($checkDivisionMaster){
 
            $file       = $request->file('signature_file');
            $extension  = $file->getClientOriginalExtension();
            $imageName  = uniqid()."-".time().'.'.$extension;

            $file->move('signature/', $imageName);


            $model              = new User();
            $model->name        = $request->name;
            $model->level       = $request->level;
            $model->email       = $request->email;
            $model->division_id = $checkDivisionMaster->division_id;
            $model->status      = $request->status;
            $model->password    = sha1($request->password);
            $model->signature_file  = $imageName;
            $model->token       = Uuid::uuid1();
            $model->updated_at  = date("Y-m-d H:i:s");
            $model->save();

            $insertedId    = $model->user_id;

            UserCompany::where('user_id', $insertedId)->delete();
            
            $companys = $json_decode;

            for($x=0;$x<count($companys);$x++){

                $checkCompany   = Company::where("company_id",$companys[$x])->first();
                if($checkCompany){
                    $UserCompany                = new UserCompany;
                    $UserCompany->user_id       = $insertedId;                
                    $UserCompany->company_id    = $checkCompany->company_id;                
                    $UserCompany->save();
                }
                
            }


                
            $message = trans("translate.Successfully");
            return response()
                ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(200);

        }else{

            $message = trans("translate.Divisionotmatchrecords");
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => ["division_id" => $message]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);

        }


    }
 


    public function userCompany(Request $request)
    {
        $auth               = $request->auth;
        $credentials        = $request->credentials;

        $user_company       = $auth->user_company;
        return response()
        ->json(['status'=>200 ,'datas' =>["data" => $user_company, "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
          'Content-Type'          => 'application/json',
          ])
        ->setStatusCode(200);

    }

}