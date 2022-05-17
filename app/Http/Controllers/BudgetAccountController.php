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


class BudgetAccountController extends Controller
{ 
    public function __construct()
    {
         
    }



    public function index(Request $request){
        $auth           = $request->auth;
        $credentials    = $request->credentials;
    
        $divisionAllow      = ["ROOT","ACCOUNTING"];
        if(!in_array($auth->level,$divisionAllow)){
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
        }

        $perPage        		    = $request->per_page;
        $sort_field     		    = $request->sort_field;
        $sort_type      		    = $request->sort_type;
    
        $company_id                         = $request->company_id;
        $year                               = $request->year;
        $status                             = $request->status;
        $budget_account_header_name         = $request->budget_account_header_name;
        $budget_account_header_description  = $request->budget_account_header_description;
        $bb                                 = $request->bb;
        $bt                                 = $request->bt;
        $sbt                                = $request->sbt;
        $budget_account_detail_description  = $request->budget_account_detail_description;
        $create_by                          = $request->create_by;
        $update_by                          = $request->update_by;
        $download                           = $request->download;
    
        if(!$sort_field){
            $sort_field = "budget_account_detail_id";
            $sort_type  = "DESC";
        }
  
        if(!$perPage){
            $perPage    = 10;
        }
            
        $query = BudgetAccountDetails::with(["budget_account_year","budget_account_header"])->orderBy($sort_field,$sort_type);
        
        if ($company_id) {
            $query = $query-whereHas('budget_account_year',function($q) use ($company_id){
                return $q->where('company_id',$company_id);
            });
        }
        
        if ($year) {
            $query = $query-whereHas('budget_account_year',function($q) use ($year){
                return $q->where('year',$year);
            });
        }
        
        if ($budget_account_header_name) {
            $query = $query-whereHas('budget_account_header',function($q) use ($budget_account_header_name){
                $like = "%{$budget_account_header_name}%";
                return $q->where('name','LIKE', $like);
            });
        }
        
        if ($budget_account_header_description) {
            $query = $query-whereHas('budget_account_header',function($q) use ($budget_account_header_description){
                $like = "%{$budget_account_header_description}%";
                return $q->where('description','LIKE', $like);
            });
        }
        
        if ($status) {
            $query = $query->where('status', $status);
        }
        
        if ($bb) {
            $like = "%{$bb}%";
            $query = $query->where('bb', 'LIKE', $like);
        }
        
        if ($bt) {
            $like = "%{$bt}%";
            $query = $query->where('bt', 'LIKE', $like);
        }
        
        if ($sbt) {
            $like = "%{$sbt}%";
            $query = $query->where('sbt', 'LIKE', $like);
        }
        
        if ($budget_account_detail_description) {
            $like = "%{$budget_account_detail_description}%";
            $query = $query->where('budget_account_detail_description', 'LIKE', $like);
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
        $sheet->setCellValue('A1', 'company_id');
        $sheet->setCellValue('B1', 'year');
        $sheet->setCellValue('C1', 'status data');
        $sheet->setCellValue('D1', 'name');
        $sheet->setCellValue('E1', 'description header');
        $sheet->setCellValue('F1', 'bb');
        $sheet->setCellValue('G1', 'bt');
        $sheet->setCellValue('H1', 'sbt');
        $sheet->setCellValue('I1', 'description account');
        $sheet->setCellValue('J1', 'total');
        $sheet->setCellValue('K1', 'status');
        $sheet->setCellValue('L1', 'create by');
        $sheet->setCellValue('M1', 'update by');
        $sheet->setCellValue('N1', 'created at');
        $sheet->setCellValue('O1', 'updated at');

        if(count($datas) > 0){
            $x=2;
            foreach($datas as $data){
                $sheet->setCellValue('A'.$x, $data->budget_account_year->company_id);
                $sheet->setCellValue('B'.$x, $data->budget_account_year->year);
                $sheet->setCellValue('C'.$x, $data->budget_account_year->status);
                $sheet->setCellValue('D'.$x, $data->budget_account_header->name);
                $sheet->setCellValue('E'.$x, $data->budget_account_header->description);
                $sheet->setCellValue('F'.$x, $data->bb);
                $sheet->setCellValue('G'.$x, $data->bt);
                $sheet->setCellValue('H'.$x, $data->sbt);
                $sheet->setCellValue('I'.$x, $data->description);
                $sheet->setCellValue('J'.$x, $data->total);
                $sheet->setCellValue('K'.$x, $data->status);
                $sheet->setCellValue('L'.$x, $data->create_by);
                $sheet->setCellValue('M'.$x, $data->update_by);
                $sheet->setCellValue('N'.$x, $data->created_at);
                $sheet->setCellValue('O'.$x, $data->updated_at);
                        
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
        $user_company   = $auth->user_company;
        $credentials    = $request->credentials;
    
        $divisionAllow      = ["ROOT","ACCOUNTING"];
        if(!in_array($auth->level,$divisionAllow)){
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
        }

        $validator = Validator::make($request->all(), [
            'company_id'                        => 'required|max:255|without_spaces',
            'year'                              => 'required|max:4',
            'budget_account_header_name'        => 'required|max:255',
            'budget_account_header_description' => 'required|max:255',
            'bb'                                => 'required|max:4',
            'bt'                                => 'required|max:4',
            'sbt'                               => 'required|max:4',
            'budget_account_detail_description' => 'required|max:255',
            'total'                             => 'required|numeric',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
        
        $check  = $this->checkCompanyId($user_company,strtoupper($request->company_id));

        if($check){

            $company_id = strtoupper($request->company_id);

            $check2     = BudgetAccountYear::where([["company_id",$company_id],["year",$request->year]])->first();
        
            if($check2){

                //data check
                $budget_year_id = $check2->budget_year_id;
                if($check2->status == "draft"){

                    $check3 = BudgetAccountHeader::where([["budget_year_id",$budget_year_id],["name",strtoupper($request->budget_account_header_name)],["description",strtoupper($request->budget_account_header_description)]])->first();
                    if($check3){
                        
                        $check4 = BudgetAccountDetails::where([["budget_year_id",$budget_year_id],["budget_account_header_id",$check3->budget_account_header_id],["bb",strtoupper($request->bb)],["bt",strtoupper($request->bt)],["sbt",strtoupper($request->sbt)]])->first();
                        if($check4){

                            $message = trans("translate.duplicateData");
                            return response()
                            ->json(['status'=>422 ,'datas' => null, 'errors' => ["sbt" => [$message]]])
                            ->withHeaders([
                                'Content-Type'          => 'application/json',
                            ])
                            ->setStatusCode(422);

                        }else{

                            $modelBudgetAccountDetails                          = new BudgetAccountDetails();
                            $modelBudgetAccountDetails->budget_year_id          = $budget_year_id;
                            $modelBudgetAccountDetails->budget_account_header_id= $check3->budget_account_header_id;
                            $modelBudgetAccountDetails->bb                      = strtoupper($request->bb);
                            $modelBudgetAccountDetails->bt                      = strtoupper($request->bt);
                            $modelBudgetAccountDetails->sbt                     = strtoupper($request->sbt);
                            $modelBudgetAccountDetails->description             = strtoupper($request->budget_account_detail_description);
                            $modelBudgetAccountDetails->total                   = $request->total;
                            $modelBudgetAccountDetails->status                  = "draft";
                            $modelBudgetAccountDetails->create_by               = $auth->name." ( ".$auth->email." )";
                            $modelBudgetAccountDetails->update_by               = $auth->name." ( ".$auth->email." )";
                            $modelBudgetAccountDetails->save();
    
                            $message = trans("translate.Successfully");
                            return response()
                                ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
                                ->withHeaders([
                                    'Content-Type'          => 'application/json',
                                ])
                                ->setStatusCode(200);

                        }

                    }else{
                        
                        //create new
                        $modelBudgetAccountHeader                   = new BudgetAccountHeader();
                        $modelBudgetAccountHeader->budget_year_id   = $budget_year_id;
                        $modelBudgetAccountHeader->name             = strtoupper($request->budget_account_header_name);
                        $modelBudgetAccountHeader->description      = strtoupper($request->budget_account_header_description);
                        $modelBudgetAccountHeader->save();

                        $budget_account_header_id   = $modelBudgetAccountHeader->budget_account_header_id;


                        $modelBudgetAccountDetails                          = new BudgetAccountDetails();
                        $modelBudgetAccountDetails->budget_year_id          = $budget_year_id;
                        $modelBudgetAccountDetails->budget_account_header_id= $budget_account_header_id;
                        $modelBudgetAccountDetails->bb                      = strtoupper($request->bb);
                        $modelBudgetAccountDetails->bt                      = strtoupper($request->bt);
                        $modelBudgetAccountDetails->sbt                     = strtoupper($request->sbt);
                        $modelBudgetAccountDetails->description             = strtoupper($request->budget_account_detail_description);
                        $modelBudgetAccountDetails->total                   = $request->total;
                        $modelBudgetAccountDetails->status                  = "draft";
                        $modelBudgetAccountDetails->create_by               = $auth->name." ( ".$auth->email." )";
                        $modelBudgetAccountDetails->update_by               = $auth->name." ( ".$auth->email." )";
                        $modelBudgetAccountDetails->save();

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
                    ->json(['status'=>422 ,'datas' => null, 'errors' => ["company_id" => [$message]]])
                    ->withHeaders([
                        'Content-Type'          => 'application/json',
                    ])
                    ->setStatusCode(422);

                }


            }else{

                // create new
                $modelBudgetAccountYear                 = new BudgetAccountYear();
                $modelBudgetAccountYear->company_id     = $company_id;
                $modelBudgetAccountYear->year           = $request->year;
                $modelBudgetAccountYear->status         = "draft";
                $modelBudgetAccountYear->create_by      = $auth->name." ( ".$auth->email." )";
                $modelBudgetAccountYear->update_by      = $auth->name." ( ".$auth->email." )";
                $modelBudgetAccountYear->save();

                $budget_year_id = $modelBudgetAccountYear->budget_year_id;

                $modelBudgetAccountHeader                   = new BudgetAccountHeader();
                $modelBudgetAccountHeader->budget_year_id   = $budget_year_id;
                $modelBudgetAccountHeader->name             = strtoupper($request->budget_account_header_name);
                $modelBudgetAccountHeader->description      = strtoupper($request->budget_account_header_description);
                $modelBudgetAccountHeader->save();

                $budget_account_header_id   = $modelBudgetAccountHeader->budget_account_header_id;


                $modelBudgetAccountDetails                          = new BudgetAccountDetails();
                $modelBudgetAccountDetails->budget_year_id          = $budget_year_id;
                $modelBudgetAccountDetails->budget_account_header_id= $budget_account_header_id;
                $modelBudgetAccountDetails->bb                      = strtoupper($request->bb);
                $modelBudgetAccountDetails->bt                      = strtoupper($request->bt);
                $modelBudgetAccountDetails->sbt                     = strtoupper($request->sbt);
                $modelBudgetAccountDetails->description             = strtoupper($request->budget_account_detail_description);
                $modelBudgetAccountDetails->total                   = $request->total;
                $modelBudgetAccountDetails->status                  = "draft";
                $modelBudgetAccountDetails->create_by               = $auth->name." ( ".$auth->email." )";
                $modelBudgetAccountDetails->update_by               = $auth->name." ( ".$auth->email." )";
                $modelBudgetAccountDetails->save();

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
            ->json(['status'=>422 ,'datas' => null, 'errors' => ["company_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);

        }

    }


    private function checkCompanyId($companys, $company_id){

        foreach($companys as $company){
            if( $company->company_id == $company_id){
                return $company_id;
            }
        }

        return false;

    }



}