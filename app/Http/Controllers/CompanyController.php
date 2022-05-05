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

use App\Models\Company;
use App\Models\CompanyDivision;
use App\Models\DivisionMaster;


class CompanyController extends Controller
{ 
    public function __construct()
    {
         
    }
 
    public function getAllData(Request $request){
      $auth           = $request->auth;
      $credentials    = $request->credentials;
      $models  = Company::orderBy('company_id', 'DESC')->get();

        return response()
        ->json(['status'=>200 ,'datas' => ["data" => $models, "credentials" => $credentials], 'errors' => null])
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
      $company_id 			= $request->company_id;
      $download         = $request->download;
  
      if(!$sort_field){
          $sort_field = "created_at";
          $sort_type  = "DESC";
      }

      if(!$perPage){
          $perPage    = 10;
      }
          
      $query = Company::with(["division_company.division"])->orderBy($sort_field,$sort_type);
      
      if ($name) {
              $like = "%{$name}%";
              $query = $query->where('name', 'LIKE', $like);
          }
      
      if ($company_id) {
              $like = "%{$company_id}%";
              $query = $query->where('company_id', 'LIKE', $like);
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
    $sheet->setCellValue('A1', 'Company ID');
    $sheet->setCellValue('B1', 'Name');
    $sheet->setCellValue('C1', 'Created At');
    $sheet->setCellValue('D1', 'Updated At');

    if(count($datas) > 0){
      $x=2;
      foreach($datas as $data){
                $sheet->setCellValue('A'.$x, $data->company_id);
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
          ->json(['status'=>500 ,'datas' => null, 'errors' => ['product_code' => 'download file error']])
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
          'name'              => 'required|max:255',
          'company_id'        => 'required|max:255|without_spaces|unique:company,company_id',
          'divisions'         => 'required|array|min:1',
      ]);

      if ($validator->fails()) {
          return response()
          ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
          ->withHeaders([
              'Content-Type'          => 'application/json',
          ])
          ->setStatusCode(422);
      }


      $array_unique       = array_unique($request->divisions);
      
      $model              = new Company();
      $model->company_id  = strtoupper($request->company_id);
      $model->name        = $request->name;
      $model->save();

      $company_id         = $model->company_id;

      CompanyDivision::where('company_id',$company_id)->delete();

      for($x=0;$x<count($array_unique);$x++){
        $division = DivisionMaster::where("division_id",$array_unique[$x])->first();
        
        if($division){

          CompanyDivision::create([
            "company_id"    => $company_id,
            "division_id"   => $division->division_id,
          ]);

        }

      }


      $message = trans("translate.Successfully");
      return response()
          ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
          ->withHeaders([
              'Content-Type'          => 'application/json',
          ])
          ->setStatusCode(200);
  }


  public function detail(Request $request,$company_id)
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

      $check = Company::with(["division_company.division"])->where("company_id",$company_id)->first();
      if($check){
 

        return response()
        ->json(['status'=>200 ,'datas' => ["data" => $check , "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);

      }else{
          $message = trans("translate.companycredentialsnotmatchrecords");
          return response()
          ->json(['status'=>404 ,'datas' => null, 'errors' => ["company_id" => $message]])
          ->withHeaders([
              'Content-Type'          => 'application/json',
          ])
          ->setStatusCode(404);

      }

  }


  public function update(Request $request,$company_id)
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
          'company_id'        => 'required|max:255|without_spaces|unique:company,company_id,'.$company_id.',company_id',
          'divisions'         => 'required|array|min:1',
      ]);

      if ($validator->fails()) {
          return response()
          ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
          ->withHeaders([
              'Content-Type'          => 'application/json',
          ])
          ->setStatusCode(422);
      }

      $model  = Company::where("company_id",$company_id)->first();
      if($model){
 
        Company::where("company_id",$company_id)->update([
            "name"          => $request->name,
            "company_id"    => strtoupper($request->company_id)
        ]);

        $company_code         = $model->company_id;

        CompanyDivision::where('company_id',strtoupper($request->company_id))->delete();
  
        $array_unique       = array_unique($request->divisions);
        for($x=0;$x<count($array_unique);$x++){
          $division = DivisionMaster::where("division_id",$array_unique[$x])->first();
          
          if($division){
  
            CompanyDivision::create([
              "company_id"    => $company_code,
              "division_id"   => $division->division_id,
            ]);
  
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
          $message = trans("translate.companycredentialsnotmatchrecords");
          return response()
          ->json(['status'=>404 ,'datas' => null, 'errors' => ["company_id" => $message]])
          ->withHeaders([
              'Content-Type'          => 'application/json',
          ])
          ->setStatusCode(404);

      }
      

  }


}
