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


class BudgetAccountYearController extends Controller
{ 
    public function __construct()
    {
         
    }


    public function index(Request $request){
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

        $perPage        		    = $request->per_page;
        $sort_field     		    = $request->sort_field;
        $sort_type      		    = $request->sort_type;
     
        $year                               = $request->year;
        $status                             = $request->status;
        $create_by                          = $request->create_by;
        $update_by                          = $request->update_by;
        $download                           = $request->download;
    
        if(!$sort_field){
            $sort_field = "budget_year_id";
            $sort_type  = "DESC";
        }
  
        if(!$perPage){
            $perPage    = 10;
        }
            
        $query = BudgetAccountYear::orderBy($sort_field,$sort_type);
         
        if ($status) {
            $query = $query->where('status', $status);
        }
                 
        if ($year) {
            $like = "%{$year}%";
            $query = $query->where('year', 'LIKE', $like);
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


    public function create(Request $request)
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
            'year'             => 'required|max:4|without_spaces',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        $check  = BudgetAccountYear::where([["year",$request->year],["status","!=","cancelled"]])->first();
        if($check){

            $message = trans("translate.duplicateData");
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => ["year" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);

        }else{

            $model                  = new BudgetAccountYear();
            $model->year            = strtoupper($request->year);
            $model->status          = "draft";
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
   
    }


    public function update(Request $request,$budget_year_id)
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
            'year'      => 'required|max:4|without_spaces',
            'status'    => 'required|in:draft,locked,cancelled'
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
  
        $model  = BudgetAccountYear::where("budget_year_id",$budget_year_id)->first();
        if($model){
 
            if($request->status == "draft"){
                
                $model2  = BudgetAccountYear::where([["year",$model->year],["status","!=","cancelled"],["budget_year_id","!=",$model->budget_year_id]])->first();
                if($model2){

                    $message = trans("translate.duplicateData");
                    return response()
                    ->json(['status'=>422 ,'datas' => null, 'errors' => ["company_id" => [$message]]])
                    ->withHeaders([
                        'Content-Type'          => 'application/json',
                    ])
                    ->setStatusCode(422);
                    
                }else{
                
                    BudgetAccountYear::where("budget_year_id",$budget_year_id)->update([
                        "year"          => $request->year,
                        "status"        => $request->status,
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
                }

            }else{

                BudgetAccountYear::where("budget_year_id",$budget_year_id)->update([
                    "company_id"    => $request->company_id,
                    "year"          => $request->year,
                    "status"        => $request->status,
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



    public function downloadDataDetail(Request $request,$budget_year_id)
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
 
        $model  = BudgetAccountYear::where("budget_year_id",$budget_year_id)->first();
        if($model){
 
            $spreadsheet 	= new Spreadsheet();
            $sheet 			= $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', $model->company_id.' Detail Dana Anggaran '.$model->year);
            $sheet->setCellValue('A2', 'BB');
            $sheet->setCellValue('B2', 'BT');
            $sheet->setCellValue('C2', 'SBT');
            $sheet->setCellValue('D2', 'Nama Rekening');
            $sheet->setCellValue('E2', 'Total Anggaran');


            $model2 = BudgetAccountHeader::where("budget_year_id",$budget_year_id)->orderBy("sequence","ASC")->get();
            if(count($model2) > 0){
                $x=3;
                foreach($model2 as $header){

                    $sheet->setCellValue('A'.$x, $header->name);
                    $sheet->setCellValue('D'.$x, $header->description);

                    $x++;
                    
                    $model3 = BudgetAccountDetails::where("budget_account_header_id",$header->budget_account_header_id)->orderBy("budget_account_detail_id","ASC")->get();
                    if(count($model3) > 0){
                        
                        foreach($model3 as $detail){

                            $sheet->setCellValue('A'.$x, $detail->bb);
                            $sheet->setCellValue('B'.$x, $detail->bt);
                            $sheet->setCellValue('C'.$x, $detail->sbt);
                            $sheet->setCellValue('D'.$x, $detail->description);
                            $sheet->setCellValue('E'.$x, $detail->total);
    
                            $x++;

                        }
                    }


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

    public function detail(Request $request,$budget_year_id)
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


        $check = BudgetAccountYear::where("budget_year_id",$budget_year_id)->first();
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
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["budget_year_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
  
        }
  
    }


 

}