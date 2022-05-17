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
use App\Models\BudgetAccountYear;
use App\Models\BudgetAccountHeader;
use App\Models\BudgetAccountDetails;


class BudgetAccountHeaderController extends Controller
{ 
    public function __construct()
    {
         
    }


    public function index(Request $request,$budget_year_id){
        $auth           = $request->auth;
        $credentials    = $request->credentials;
     
        if($auth->level != "ROOT"){
            if($auth->division_id != "ACCOUNTING"){
                return response()
                ->json(['status'=>404 ,'datas' => null, 'errors' => null])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(404);
            }
        }

        $perPage        = $request->per_page;
        $sort_field     = $request->sort_field;
        $sort_type      = $request->sort_type;
    
        $name           = $request->name;
        $description    = $request->description;
        $create_by      = $request->create_by;
        $update_by      = $request->update_by;
    
        if(!$sort_field){
            $sort_field = "sequence";
            $sort_type  = "ASC";
        }
  
        if(!$perPage){
            $perPage    = 10;
        }
            
        $query = BudgetAccountHeader::where("budget_year_id",$budget_year_id)->orderBy($sort_field,$sort_type);
          
        
        if ($name) {
            $like = "%{$name}%";
            $query = $query->where('name', 'LIKE', $like);
        }
                 
        if ($description) {
            $like = "%{$description}%";
            $query = $query->where('description', 'LIKE', $like);
        }       
                 
        if ($create_by) {
            $like = "%{$create_by}%";
            $query = $query->where('create_by', 'LIKE', $like);
        }     
                 
        if ($update_by) {
            $like = "%{$update_by}%";
            $query = $query->where('update_by', 'LIKE', $like);
        }     
           
     
        $response = $query->paginate($perPage);
  
        return response()
        ->json(['status'=>200 ,'datas' => ["data" => $response, "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);
        
    }


    public function detail(Request $request,$budget_account_header_id){
        $auth           = $request->auth;
        $credentials    = $request->credentials;
     
        if($auth->level != "ROOT"){
            if($auth->division_id != "ACCOUNTING"){
                return response()
                ->json(['status'=>404 ,'datas' => null, 'errors' => null])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(404);
            }
        }

        $check = BudgetAccountHeader::with("budget")->where("budget_account_header_id",$budget_account_header_id)->first();
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
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["budget_account_header_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
  
        }
        
    }


    public function create(Request $request,$budget_year_id)
    {
        $auth           = $request->auth;
        $credentials    = $request->credentials;
    
     
        if($auth->level != "ROOT"){
            if($auth->division_id != "ACCOUNTING"){
                return response()
                ->json(['status'=>404 ,'datas' => null, 'errors' => null])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(404);
            }
        }

        $validator = Validator::make($request->all(), [
            'sequence'          => 'required|numeric',
            'name'              => 'required|max:255',
            'description'       => 'required|max:255',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        $checkBudgetAccountYear = BudgetAccountYear::where("budget_year_id",$budget_year_id)->first();
        if($checkBudgetAccountYear){
            if($checkBudgetAccountYear->status == "draft"){

                $check  = BudgetAccountHeader::where([["name",$request->name],["description",$request->description]])->first();
                if($check){
        
                    $message = trans("translate.duplicateData");
                    return response()
                    ->json(['status'=>422 ,'datas' => null, 'errors' => ["year" => [$message]]])
                    ->withHeaders([
                        'Content-Type'          => 'application/json',
                    ])
                    ->setStatusCode(422);
        
                }else{
        
                    $model                  = new BudgetAccountHeader();
                    $model->budget_year_id  = $budget_year_id;
                    $model->name            = strtoupper($request->name);
                    $model->description     = strtoupper($request->description);
                    $model->sequence        = strtoupper($request->sequence);
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

            }else{
                $message = trans("translate.statusOnlyDraft");
                return response()
                ->json(['status'=>422 ,'datas' => null, 'errors' => ["name" => [$message]]])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(422);
            }
        }else{

            $message = trans("translate.credentialsnotmatchrecords");
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["budget_account_header_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);

        }
   
    }


    public function update(Request $request,$budget_account_header_id)
    {
        $auth           = $request->auth;
        $credentials    = $request->credentials;
  
     
        if($auth->level != "ROOT"){
            if($auth->division_id != "ACCOUNTING"){
                return response()
                ->json(['status'=>404 ,'datas' => null, 'errors' => null])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(404);
            }
        }


        $validator = Validator::make($request->all(), [
            'sequence'          => 'required|numeric',
            'name'              => 'required|max:255',
            'description'       => 'required|max:255',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
  
        $model  = BudgetAccountHeader::where("budget_account_header_id",$budget_account_header_id)->first();
        if($model){
 

            $checkBudgetAccountYear = BudgetAccountYear::where("budget_year_id",$model->budget_year_id)->first();
            if(@$checkBudgetAccountYear->status == "draft"){

                BudgetAccountHeader::where("budget_account_header_id",$budget_account_header_id)->update([
                    "sequence"      => $request->sequence,
                    "name"          => strtoupper($request->name),
                    "description"   => strtoupper($request->description),
                    "update_by"     => $auth->name." ( ".$auth->email." )",
                    "updated_at"    => date("Y-m-d H:i:s")
                ]);
    
      
                $message = trans("translate.Successfully");
                return response()
                    ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
                    ->withHeaders([
                        'Content-Type'          => 'application/json',
                    ])
                    ->setStatusCode(200);

            }else{

                $message = trans("translate.statusOnlyDraft");
                return response()
                ->json(['status'=>422 ,'datas' => null, 'errors' => ["name" => [$message]]])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(422);
            }

        }else{

            $message = trans("translate.credentialsnotmatchrecords");
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["company_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
  
        }
        
  
    }


    public function destroy(Request $request,$budget_account_header_id)
    {
        $auth           = $request->auth;
        $credentials    = $request->credentials;
  

     
        if($auth->level != "ROOT"){
            if($auth->division_id != "ACCOUNTING"){
                return response()
                ->json(['status'=>404 ,'datas' => null, 'errors' => null])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(404);
            }
        }

   
        $model  = BudgetAccountHeader::where("budget_account_header_id",$budget_account_header_id)->first();
        if($model){
 
            $checkBudgetAccountYear = BudgetAccountYear::where("budget_year_id",$model->budget_year_id)->first();

            if(@$checkBudgetAccountYear->status == "draft"){

                BudgetAccountHeader::where("budget_account_header_id",$budget_account_header_id)->delete();
                BudgetAccountDetails::where("budget_account_header_id",$budget_account_header_id)->delete();
    
      
                $message = trans("translate.Successfully");
                return response()
                    ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
                    ->withHeaders([
                        'Content-Type'          => 'application/json',
                    ])
                    ->setStatusCode(200);

            }else{

                $message = trans("translate.statusOnlyDraft");
                return response()
                ->json(['status'=>422 ,'datas' => null, 'errors' => ["name" => [$message]]])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(422);

            }
                
        }else{

            $message = trans("translate.credentialsnotmatchrecords");
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["company_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
  
        }
        
  
    }


    public function downloadDataDetail(Request $request,$budget_account_header_id)
    {
        set_time_limit(0);
        error_reporting(0);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        $auth           = $request->auth;
        $credentials    = $request->credentials;
  

     
        if($auth->level != "ROOT"){
            if($auth->division_id != "ACCOUNTING"){
                return response()
                ->json(['status'=>404 ,'datas' => null, 'errors' => null])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(404);
            }
        }

        $file_name      = uniqid().".xlsx";
        $file_path  	= storage_path('download') . '/' . $file_name;
 
        $model  = BudgetAccountHeader::with(["budget"])->where("budget_account_header_id",$budget_account_header_id)->first();
        if($model){
 
            $spreadsheet 	= new Spreadsheet();
            $sheet 			= $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', $model->budget->company_id.' Detail Dana Anggaran '.$model->budget->year);
            $sheet->setCellValue('A2', 'BB');
            $sheet->setCellValue('B2', 'BT');
            $sheet->setCellValue('C2', 'SBT');
            $sheet->setCellValue('D2', 'Nama Rekening');
            $sheet->setCellValue('E2', 'Total Anggaran');
            $sheet->setCellValue('A3', $model->name);
            $sheet->setCellValue('D3', $model->description);

            $model2 = BudgetAccountDetails::where("budget_account_header_id",$model->budget_account_header_id)->orderBy("budget_account_detail_id","ASC")->get();
            if(count($model2) > 0){
                $x=4;
                foreach($model2 as $detail){
                    
                    $sheet->setCellValue('A'.$x, $detail->bb);
                    $sheet->setCellValue('B'.$x, $detail->bt);
                    $sheet->setCellValue('C'.$x, $detail->sbt);
                    $sheet->setCellValue('D'.$x, $detail->description);
                    $sheet->setCellValue('E'.$x, $detail->total);

                    $x++;

                }

            }
  
        }else{

            $spreadsheet 	= new Spreadsheet();
            $sheet 			= $spreadsheet->getActiveSheet();
            $sheet->setCellValue('D1', 'Detail Dana Anggaran');
            $sheet->setCellValue('A2', 'BB');
            $sheet->setCellValue('B2', 'BT');
            $sheet->setCellValue('C2', 'SBT');
            $sheet->setCellValue('D2', 'Nama Rekening');
            $sheet->setCellValue('E2', 'Total Anggaran');

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


    public function downloadTemplate(Request $request){
        set_time_limit(0);
        error_reporting(0);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0); 

        $file_name      = "budget-header-template.xlsx";
        $file_path  	= storage_path('template') . '/' . $file_name;
 
        $headers	= ['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment'];
        $file = file_get_contents($file_path);
        $res = response($file, 200)->withHeaders(['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment;filename="'.$file_name.'"']);
        
        return $res;

    }


    public function uploadData(Request $request,$budget_year_id)
    {
        $auth           = $request->auth;
        $credentials    = $request->credentials;
  

        if($auth->level != "ROOT"){
            if($auth->division_id != "ACCOUNTING"){
                return response()
                ->json(['status'=>404 ,'datas' => null, 'errors' => null])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(404);
            }
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



        $checkBudgetAccountYear = BudgetAccountYear::where("budget_year_id",$budget_year_id)->first();
        if($checkBudgetAccountYear){
            if($checkBudgetAccountYear->status == "draft"){


        
                $document       = $request->file('file');
                $inputFileType  = IOFactory::identify($document);
                $reader         = IOFactory::createReader($inputFileType);
                $spreadsheet    = $reader->load($document);
                $datas          = $spreadsheet->getActiveSheet()->toArray();
                $removed        = array_splice($datas, 1);
                $response       = [];
        
                $validator = Validator::make($removed, [
                    '*.0' => 'required|max:255',
                    '*.1' => 'required|max:255',
                    '*.2' => 'required|numeric'
                ]);
 

                if ($validator->fails()) {
                    return response()
                    ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
                    ->withHeaders([
                        'Content-Type'          => 'application/json',
                    ])
                    ->setStatusCode(422);
                }
                
                for($x=0;$x<count($removed);$x++){
                    $data    = $removed[$x];

                    $check  = BudgetAccountHeader::where([["name",strtoupper($data[0])],["description",strtoupper($data[1])]])->first();
                    if($check){

                        $response[] = ["name" => strtoupper($data[1]) , "status" => "failed" , "message" => "duplicate data"];

                    }else{

                        $model                  = new BudgetAccountHeader();
                        $model->budget_year_id  = $budget_year_id;
                        $model->name            = strtoupper($data[0]);
                        $model->description     = strtoupper($data[1]);
                        $model->sequence        = strtoupper($data[2]);
                        $model->create_by       = $auth->name." ( ".$auth->email." )";
                        $model->update_by       = $auth->name." ( ".$auth->email." )";
                        $model->save();
                        
                        $response[] = ["name" => strtoupper($data[1]) , "status" => "success" , "message" => ""]; 

                    }
                    
        
                }
        
        
                return response()
                ->json(['status'=>200 ,'datas' => ["data" => $response, "credentials" => $credentials], 'errors' => null])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(200);

            }else{
                $message = trans("translate.statusOnlyDraft");
                return response()
                ->json(['status'=>422 ,'datas' => null, 'errors' => ["name" => [$message]]])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(422);
            }
        }else{

            $message = trans("translate.credentialsnotmatchrecords");
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["budget_account_header_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);

        }
  
    }

}