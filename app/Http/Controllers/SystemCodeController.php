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

use App\Models\Company;
use App\Models\CompanyDivision;
use App\Models\DivisionMaster;
use App\Models\SystemCode;


class SystemCodeController extends Controller
{ 
    public function __construct()
    {
         
    }


    public function getDataBySystemCode(Request $request){
        
        $auth           = $request->auth;
        $credentials    = $request->credentials;

        $validator = Validator::make($request->all(), [
            'system_code'              => 'required|max:255'
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }


        $value  = $request->value;
 
        $query  = SystemCode::where('system_code',$request->system_code)->select("value")->orderBy('sequence', 'ASC')->limit(10);
  
        if ($value) {
            $like = "%{$value}%";
            $query = $query->where('value', 'LIKE', $like);
        }

        $response = $query->pluck('value')->toArray();

        return response()
          ->json(['status'=>200 ,'datas' => ["data" => $response, "credentials" => $credentials], 'errors' => null])
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
    
        $system_code            = $request->system_code;
        $value                  = $request->value;
        $create_by              = $request->create_by;
        $update_by              = $request->update_by;
        $download               = $request->download;
    
        if(!$sort_field){
            $sort_field = "system_code_id";
            $sort_type  = "DESC";
        }
  
        if(!$perPage){
            $perPage    = 10;
        }
            
        $query = SystemCode::orderBy($sort_field,$sort_type);
        
        if ($system_code) {
            $like = "%{$system_code}%";
            $query = $query->where('system_code', 'LIKE', $like);
        }
        
        if ($value) {
            $like = "%{$value}%";
            $query = $query->where('value', 'LIKE', $like);
        }
        
        if ($create_by) {
            $like = "%{$create_by}%";
            $query = $query->where('create_by', 'LIKE', $like);
        }
        
        if ($update_by) {
            $like = "%{$update_by}%";
            $query = $query->where('update_by', 'LIKE', $like);
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
        $sheet->setCellValue('A1', 'system_code_id');
        $sheet->setCellValue('B1', 'system_code');
        $sheet->setCellValue('C1', 'value');
        $sheet->setCellValue('D1', 'sequence');
        $sheet->setCellValue('E1', 'create_by');
        $sheet->setCellValue('F1', 'update_by');
        $sheet->setCellValue('G1', 'created_at');
        $sheet->setCellValue('H1', 'updated_at');

        if(count($datas) > 0){
            $x=2;
            foreach($datas as $data){
                $sheet->setCellValue('A'.$x, $data->system_code_id);
                $sheet->setCellValue('B'.$x, $data->system_code);
                $sheet->setCellValue('C'.$x, $data->value);
                $sheet->setCellValue('D'.$x, $data->sequence);
                $sheet->setCellValue('E'.$x, $data->create_by);
                $sheet->setCellValue('F'.$x, $data->update_by);
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
            ->json(['status'=>500 ,'datas' => null, 'errors' => ['system_code_id' => 'download file error']])
            ->withHeaders([
                'Content-Type'          => 'application/json',
                ])
            ->setStatusCode(500);
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
            'system_code'       => 'required|max:255|without_spaces',
            'value'             => 'required|max:255',
            'sequence'          => 'required|numeric',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
   
        $model                  = new SystemCode();
        $model->system_code     = strtoupper($request->system_code);
        $model->value           = strtoupper($request->value);
        $model->sequence        = $request->sequence;
        $model->create_by       = $auth->name." ( ".$auth->email." )";
        $model->update_by       = $auth->name." ( ".$auth->email." )";
        $model->save();
   
  
        $message = trans("translate.Successfully");
        return response()
            ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(200);
    }



    public function detail(Request $request,$system_code_id)
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


        $check = SystemCode::where("system_code_id",$system_code_id)->first();
        if($check){
   
          return response()
          ->json(['status'=>200 ,'datas' => ["data" => $check , "credentials" => $credentials], 'errors' => null])
          ->withHeaders([
              'Content-Type'          => 'application/json',
          ])
          ->setStatusCode(200);
  
        }else{

            $message = trans("translate.credentialsnotmatchrecords");
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["system_code_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
  
        }
  
    }


    public function update(Request $request,$system_code_id)
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
            'system_code'       => 'required|max:255|without_spaces',
            'value'             => 'required|max:255',
            'sequence'          => 'required|numeric',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
  
        $model  = SystemCode::where("system_code_id",$system_code_id)->first();
        if($model){
   
            SystemCode::where("system_code_id",$system_code_id)->update([
              "system_code"         => strtoupper($request->system_code),
              "value"               => strtoupper($request->value),
              "sequence"            => $request->sequence,
              "update_by"           => $auth->name." ( ".$auth->email." )",
              "updated_at"          => date("Y-m-d H:i:s")
            ]); 
    
          $message = trans("translate.Successfully");
          return response()
              ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
              ->withHeaders([
                  'Content-Type'          => 'application/json',
              ])
              ->setStatusCode(200);
  
        }else{

            $message = trans("translate.credentialsnotmatchrecords");
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["system_code_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
  
        }
        
  
    }
  
    public function downloadTemplate(Request $request){
        set_time_limit(0);
        error_reporting(0);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0); 

        $file_name      = "system-code-template.xlsx";
        $file_path  	= storage_path('template') . '/' . $file_name;
 
        $headers	= ['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment'];
        $file = file_get_contents($file_path);
        $res = response($file, 200)->withHeaders(['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment;filename="'.$file_name.'"']);
        
        return $res;

    }
  

    public function uploadData(Request $request)
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
            'file'              => 'required|mimes:xlsx',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
  
        
        $document       = $request->file('file');
        $inputFileType  = IOFactory::identify($document);
        $reader         = IOFactory::createReader($inputFileType);
        $spreadsheet    = $reader->load($document);
        $datas          = $spreadsheet->getActiveSheet()->toArray();
        $removed        = array_splice($datas, 1);
        $response       = [];

        for($x=0;$x<count($removed);$x++){
            $data    = $removed[$x];

           
            $model                  = new SystemCode();
            $model->system_code     = strtoupper($data[0]);
            $model->value           = strtoupper($data[1]);
            $model->sequence        = strtoupper($data[2]);
            $model->create_by       = $auth->name." ( ".$auth->email." )";
            $model->update_by       = $auth->name." ( ".$auth->email." )";
            $model->save();
            
            $response[] = ["name" => strtoupper($data[1]) , "status" => "success" , "message" => ""];  

        }


        return response()
        ->json(['status'=>200 ,'datas' => ["data" => $response, "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);
    }



    public function destroy(Request $request,$system_code_id)
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

 
  
        $model  = SystemCode::where("system_code_id",$system_code_id)->first();
        if($model){
   
            SystemCode::where("system_code_id",$system_code_id)->delete(); 
    
            $message = trans("translate.Successfully");
            return response()
                ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(200);
  
        }else{

            $message = trans("translate.credentialsnotmatchrecords");
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["system_code_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
  
        }
        
  
    }
  
}