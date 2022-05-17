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


class BudgetAccountDetailsController extends Controller
{

    public function __construct()
    {
         
    }


    public function index(Request $request,$budget_account_header_id){
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
    
        $bb             = $request->bb;
        $bt             = $request->bt;
        $sbt            = $request->sbt;
        $description    = $request->description;
        $create_by      = $request->create_by;
        $update_by      = $request->update_by;
        $download       = $request->download;
    
        if(!$sort_field){
            $sort_field = "budget_account_detail_id";
            $sort_type  = "ASC";
        }
  
        if(!$perPage){
            $perPage    = 10;
        }
            
        $query = BudgetAccountDetails::where("budget_account_header_id",$budget_account_header_id)->orderBy($sort_field,$sort_type);
          
        
        if ($bb) {
            $query = $query->where('bb',$bb);
        }
        
        if ($bt) {
            $query = $query->where('bt',$bt);
        }
                 
        if ($sbt) {
            $like = "%{$sbt}%";
            $query = $query->where('sbt', 'LIKE', $like);
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
           
     

        if($download == "download"){
            $response    = $query->get();
            return $this->downloadData($response,$budget_account_header_id);
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



    private function downloadData($datas,$budget_account_header_id){
        set_time_limit(0);
        error_reporting(0);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0); 

        $file_name      = uniqid().".xlsx";
		$file_path  	= storage_path('download') . '/' . $file_name;

        $spreadsheet 	= new Spreadsheet();

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

            if(count($datas) > 0){
                $x=4;
                foreach($datas as $detail){
                    
                    
                    $sheet->setCellValue('A'.$x, $detail->bb);
                    $sheet->setCellValue('B'.$x, $detail->bt);
                    $sheet->setCellValue('C'.$x, $detail->sbt);
                    $sheet->setCellValue('D'.$x, $detail->description);
                    $sheet->setCellValue('E'.$x, $detail->total);

                    $x++;

                }

            }

        }else{

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
					->json(['status'=>500 ,'datas' => null, 'errors' => ['product_code' => 'download file error']])
					->withHeaders([
						'Content-Type'          => 'application/json',
					  ])
					->setStatusCode(500);
		}

    }


    public function detail(Request $request,$budget_account_detail_id){
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

        $check = BudgetAccountDetails::with(["budget_account_year","budget_account_header"])->where("budget_account_detail_id",$budget_account_detail_id)->first();
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
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["budget_account_detail_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
  
        }
        
    }



    public function create(Request $request,$budget_account_header_id)
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
            'bb'                => 'required|without_spaces|max:4',
            'bt'                => 'required|without_spaces|max:4',
            'sbt'               => 'required|without_spaces|max:4',
            'description'       => 'required|max:255',
            'total'             => 'required|numeric',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
 
   
        $check  = BudgetAccountHeader::with("budget")->where("budget_account_header_id",$budget_account_header_id)->first();
        if($check){

            if($check->budget->status == "draft"){

                $checkBudgetAccountDetails      = BudgetAccountDetails::where([["budget_year_id",$check->budget_year_id],["budget_account_header_id",$check->budget_account_header_id],["bb",$request->bb],["bt",$request->bt],["sbt",$request->sbt]])->first();
                if($checkBudgetAccountDetails){

                    $message = trans("translate.duplicateData");
                    return response()
                    ->json(['status'=>422 ,'datas' => null, 'errors' => ["sbt" => [$message]]])
                    ->withHeaders([
                        'Content-Type'          => 'application/json',
                    ])
                    ->setStatusCode(422);

                }else{

                    $model                          = new BudgetAccountDetails();
                    $model->budget_year_id          = $check->budget_year_id;
                    $model->budget_account_header_id= $check->budget_account_header_id;
                    $model->bb                      = strtoupper($request->bb);
                    $model->bt                      = strtoupper($request->bt);
                    $model->sbt                     = strtoupper($request->sbt);
                    $model->description             = strtoupper($request->description);
                    $model->total                   = $request->total;
                    $model->create_by               = $auth->name." ( ".$auth->email." )";
                    $model->update_by               = $auth->name." ( ".$auth->email." )";
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
                ->json(['status'=>422 ,'datas' => null, 'errors' => ["bb" => [$message]]])
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


    public function update(Request $request,$budget_account_detail_id)
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
            'bb'                => 'required|without_spaces|max:4',
            'bt'                => 'required|without_spaces|max:4',
            'sbt'               => 'required|without_spaces|max:4',
            'description'       => 'required|max:255',
            'total'             => 'required|numeric',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
  
        $model  = BudgetAccountDetails::with("budget_account_year")->where("budget_account_detail_id",$budget_account_detail_id)->first();
        if($model){
 
            if(@$model->budget_account_year->status == "draft"){

                $checkBudgetAccountDetails      = BudgetAccountDetails::where([["budget_year_id",$model->budget_year_id],["budget_account_header_id",$model->budget_account_header_id],["bb",$request->bb],["bt",$request->bt],["sbt",$request->sbt],["budget_account_detail_id","!=",$budget_account_detail_id]])->first();
                if($checkBudgetAccountDetails){

                    $message = trans("translate.duplicateData");
                    return response()
                    ->json(['status'=>422 ,'datas' => null, 'errors' => ["sbt" => [$message]]])
                    ->withHeaders([
                        'Content-Type'          => 'application/json',
                    ])
                    ->setStatusCode(422);

                }else{

                    BudgetAccountDetails::where("budget_account_detail_id",$budget_account_detail_id)->update([
                        "bb"            => strtoupper($request->bb),
                        "bt"            => strtoupper($request->bt),
                        "sbt"           => strtoupper($request->sbt),
                        "description"   => strtoupper($request->description),
                        "total"         => $request->total,
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


    public function destroy(Request $request,$budget_account_detail_id)
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

   
        $model  = BudgetAccountDetails::with("budget_account_year")->where("budget_account_detail_id",$budget_account_detail_id)->first();
        if($model){
  
            if(@$model->budget_account_year->status == "draft"){

                BudgetAccountDetails::where("budget_account_detail_id",$budget_account_detail_id)->delete();    
      
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


    public function downloadTemplate(Request $request){
        set_time_limit(0);
        error_reporting(0);
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0); 

        $file_name      = "budget-details-template.xlsx";
        $file_path  	= storage_path('template') . '/' . $file_name;
 
        $headers	= ['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment'];
        $file = file_get_contents($file_path);
        $res = response($file, 200)->withHeaders(['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment;filename="'.$file_name.'"']);
        
        return $res;

    }


    public function uploadData(Request $request,$budget_account_header_id)
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



        $checkBudgetAccountHeader = BudgetAccountHeader::with("budget")->where("budget_account_header_id",$budget_account_header_id)->first();
        if($checkBudgetAccountHeader){
            if($checkBudgetAccountHeader->budget->status == "draft"){


        
                $document       = $request->file('file');
                $inputFileType  = IOFactory::identify($document);
                $reader         = IOFactory::createReader($inputFileType);
                $spreadsheet    = $reader->load($document);
                $datas          = $spreadsheet->getActiveSheet()->toArray();
                $removed        = array_splice($datas, 1);
                $response       = [];

                $validator = Validator::make($removed, [
                    '*.0' => 'required|without_spaces|max:4',
                    '*.1' => 'required|without_spaces|max:4',
                    '*.2' => 'required|without_spaces|max:4',
                    '*.3' => 'required|max:255',
                    '*.4' => 'required|numeric',
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

                    $check  = BudgetAccountDetails::where([["budget_year_id",$checkBudgetAccountHeader->budget_year_id],["budget_account_header_id",$checkBudgetAccountHeader->budget_account_header_id],["bb",$data[0]],["bt",$data[1]],["sbt",$data[2]]])->first();
                    if($check){

                        $response[] = ["name" => strtoupper($data[3]) , "status" => "failed" , "message" => "duplicate data"];

                    }else{

                        $model                          = new BudgetAccountDetails();
                        $model->budget_year_id          = $checkBudgetAccountHeader->budget_year_id;
                        $model->budget_account_header_id= $checkBudgetAccountHeader->budget_account_header_id;
                        $model->bb                      = strtoupper($data[0]);
                        $model->bt                      = strtoupper($data[1]);
                        $model->sbt                     = strtoupper($data[2]);
                        $model->description             = strtoupper($data[3]);
                        $model->total                   = $data[4];
                        $model->create_by               = $auth->name." ( ".$auth->email." )";
                        $model->update_by               = $auth->name." ( ".$auth->email." )";
                        $model->save();
                        
                        $response[] = ["name" => strtoupper($data[3]) , "status" => "success" , "message" => ""]; 

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