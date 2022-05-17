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
use App\Models\DivisionMaster;
use App\Models\User;
use App\Models\CompanyDivision;

class DivisionController extends Controller
{ 
    public function __construct()
    {
         
    }

    public function getAllData(Request $request){
        $auth           = $request->auth;
        $credentials    = $request->credentials;


        $perPage        		= @$request->per_page;
        $sort_field     		= @$request->sort_field;
        $sort_type      		= @$request->sort_type;
		
        $name     				= @$request->name;
        $code     				= @$request->code;
        $download  				= @$request->download;


        $sort_field = "division_id";
        $sort_type  = "DESC";
        
		$query = DivisionMaster::orderBy($sort_field,$sort_type);
		
        $response    = $query->get();

        return response()
        ->json(['status'=>200 ,'datas' => ["data" => $response, "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);

    }
    
    public function index(Request $request){
        $auth                   = $request->auth;

        $credentials    = $request->credentials;

        $perPage        		= @$request->per_page;
        $sort_field     		= @$request->sort_field;
        $sort_type      		= @$request->sort_type;
		
        $name     				= @$request->name;
        $division_id     		= @$request->division_id;
        $download  				= @$request->download;


        if(!$sort_field){
            $sort_field = "created_at";
            $sort_type  = "DESC";
        }

        if(!$perPage){
            $perPage    = 10;
        }
        
		$query = DivisionMaster::orderBy($sort_field,$sort_type);
		
		if ($name) {
            $like = "%{$name}%";
            $query = $query->where('name', 'LIKE', $like);
        }
		
		if ($division_id) {
            $like = "%{$division_id}%";
            $query = $query->where('division_id', 'LIKE', $like);
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
		$sheet->setCellValue('A1', 'division id');
		$sheet->setCellValue('B1', 'name');
		$sheet->setCellValue('C1', 'Created At');
		$sheet->setCellValue('D1', 'Updated At');

		if(count($datas) > 0){
			$x=2;
			foreach($datas as $data){
                $sheet->setCellValue('A'.$x, $data->division_id);
                $sheet->setCellValue('B'.$x, $data->name);
                $sheet->setCellValue('C'.$x, $data->created_at);
                $sheet->setCellValue('D'.$x, $data->updated_at);
                
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
					->json(['status'=>500 ,'datas' => null, 'errors' => ['division_id' => ['download file error']]])
					->withHeaders([
						'Content-Type'          => 'application/json',
					  ])
					->setStatusCode(500);
		}

    }


    public function detail(Request $request,$division_id)
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
  
        $check = DivisionMaster::where("division_id",$division_id)->first();
        if($check){
   
  
          return response()
          ->json(['status'=>200 ,'datas' => ["data" => $check , "credentials" => $credentials], 'errors' => null])
          ->withHeaders([
              'Content-Type'          => 'application/json',
          ])
          ->setStatusCode(200);
  
        }else{
            $message = trans("translate.divisioncredentialsnotmatchrecords");
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["division_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
  
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
            'division_id'        => 'required|max:255|without_spaces|unique:division_master,division_id',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
  
   
        
        $model              = new DivisionMaster();
        $model->division_id = strtoupper($request->division_id);
        $model->name        = $request->name;
        $model->save();
   
  
        $message = trans("translate.Successfully");
        return response()
            ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(200);
    }


    public function update(Request $request,$division_id)
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
            'division_id'       => 'required|max:255|without_spaces|unique:division_master,division_id,'.$division_id.',division_id',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
  
        $model  = DivisionMaster::where("division_id",$division_id)->first();
        if($model){
   
            DivisionMaster::where("division_id",$division_id)->update([
              "name"          => $request->name,
              "division_id"   => strtoupper($request->division_id)
            ]);
  
          $company_code         = $model->company_id;
  
          CompanyDivision::where('division_id',strtoupper($request->division_id))->update([
            "division_id"   => strtoupper($request->division_id)
          ]);
  
          User::where('division_id',strtoupper($request->division_id))->update([
            "division_id"   => strtoupper($request->division_id)
          ]);
          
          $message = trans("translate.Successfully");
          return response()
              ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
              ->withHeaders([
                  'Content-Type'          => 'application/json',
              ])
              ->setStatusCode(200);
  
        }else{
            $message = trans("translate.companycredentialsnotmatchrecords");
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["company_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
  
        }
        
  
    }
  
}