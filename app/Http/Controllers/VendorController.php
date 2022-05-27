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
use App\Models\Vendor;


class VendorController extends Controller
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
    
        $company_id     		= $request->company_id;
        $name     				= $request->name;
        $npwp_no 			    = $request->npwp_no;
        $address 			    = $request->address;
        $download               = $request->download;
    
        if(!$sort_field){
            $sort_field = "vendor_id";
            $sort_type  = "DESC";
        }
  
        if(!$perPage){
            $perPage    = 10;
        }
            
        $query = Vendor::orderBy($sort_field,$sort_type);
        
        if ($company_id) {
            $query = $query->where('company_id', $company_id);
        }
        
        if ($name) {
            $like = "%{$name}%";
            $query = $query->where('name', 'LIKE', $like);
        }
        
        if ($address) {
            $like = "%{$address}%";
            $query = $query->where('address', 'LIKE', $like);
        }
        
        if ($npwp_no) {
            $like = "%{$npwp_no}%";
            $query = $query->where('npwp_no', 'LIKE', $like);
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
        $sheet->setCellValue('C1', 'Address');
        $sheet->setCellValue('D1', 'NPWP No');
        $sheet->setCellValue('E1', 'Balance');
        $sheet->setCellValue('F1', 'Created At');
        $sheet->setCellValue('G1', 'Updated At');

        if(count($datas) > 0){
            $x=2;
            foreach($datas as $data){
                $sheet->setCellValue('A'.$x, $data->company_id);
                $sheet->setCellValue('B'.$x, $data->name);
                $sheet->setCellValue('C'.$x, $data->address);
                $sheet->setCellValue('D'.$x, $data->npwp_no);
                $sheet->setCellValue('E'.$x, $data->balance);
                $sheet->setCellValue('F'.$x, $data->created_at);
                $sheet->setCellValue('G'.$x, $data->updated_at);
                        
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
            ->json(['status'=>500 ,'datas' => null, 'errors' => ['vendor_id' => ['download file error']]])
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
    
        $validator = Validator::make($request->all(), [
            'company_id'        => 'required|max:255',
            'name'              => 'required|max:255',
            'npwp_no'           => 'required|max:15|without_spaces',
            'address'           => 'required',
            'balance'           => 'required|numeric',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }

        $checkNpwp  = Vendor::where([["company_id",$request->company_id],["npwp_no",$request->npwp_no]])->first();

        if($checkNpwp){
            $message = trans("translate.duplicateData");
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => ["npwp_no" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
   
        $model              = new Vendor();
        $model->company_id  = strtoupper($request->company_id);
        $model->name        = strtoupper($request->name);
        $model->address     = strtoupper($request->address);
        $model->npwp_no     = $request->npwp_no;
        $model->balance     = $request->balance;
        $model->save();
   
  
        $message = trans("translate.Successfully");
        return response()
            ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(200);
    }



    public function detail(Request $request,$vendor_id)
    {
        $auth           = $request->auth;
        $credentials    = $request->credentials;
  
        $check = Vendor::where("vendor_id",$vendor_id)->first();
        if($check){
   
          return response()
          ->json(['status'=>200 ,'datas' => ["data" => $check , "credentials" => $credentials], 'errors' => null])
          ->withHeaders([
              'Content-Type'          => 'application/json',
          ])
          ->setStatusCode(200);
  
        }else{

            $message = trans("translate.vendorcredentialsnotmatchrecords");
            return response()
            ->json(['status'=>404 ,'datas' => null, 'errors' => ["vendor_id" => [$message]]])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(404);
  
        }
  
    }


    public function update(Request $request,$vendor_id)
    {
        $auth           = $request->auth;
        $credentials    = $request->credentials;
  
        $validator = Validator::make($request->all(), [
            'company_id'        => 'required|max:255',
            'name'              => 'required|max:255',
            'npwp_no'           => 'required|max:15|without_spaces',
            'address'           => 'required',
            'balance'           => 'required|numeric',
        ]);
  
        if ($validator->fails()) {
            return response()
            ->json(['status'=>422 ,'datas' => null, 'errors' => $validator->errors()])
            ->withHeaders([
                'Content-Type'          => 'application/json',
            ])
            ->setStatusCode(422);
        }
  
        $model  = Vendor::where("vendor_id",$vendor_id)->first();
        if($model){

            $checkNpwp  = Vendor::where([["company_id",$request->company_id],["npwp_no",$request->npwp_no],["vendor_id","<>",$vendor_id]])->first();

            if($checkNpwp){
                $message = trans("translate.duplicateData");
                return response()
                ->json(['status'=>422 ,'datas' => null, 'errors' => ["npwp_no" => [$message]]])
                ->withHeaders([
                    'Content-Type'          => 'application/json',
                ])
                ->setStatusCode(422);
            }

   
            Vendor::where("vendor_id",$vendor_id)->update([
              "company_id"  => strtoupper($request->company_id),
              "name"        => strtoupper($request->name),
              "address"     => strtoupper($request->address),
              "npwp_no"     => $request->npwp_no,
              "balance"     => $request->balance
          ]); 
    
          $message = trans("translate.Successfully");
          return response()
              ->json(['status'=>200 ,'datas' => ["messages" => $message, "credentials" => $credentials], 'errors' => null])
              ->withHeaders([
                  'Content-Type'          => 'application/json',
              ])
              ->setStatusCode(200);
  
        }else{

            $message = trans("translate.vendorcredentialsnotmatchrecords");
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

        $file_name      = "vendor-template.xlsx";
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
  
        $validator = Validator::make($request->all(), [
            'company_id'        => 'required|max:255',
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


        $validator = Validator::make($removed, [
            '*.0' => 'required|max:255',
            '*.1' => 'required|without_spaces|max:15',
            '*.2' => 'required',
            '*.3' => 'required|numeric',
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
 
            $check              = Vendor::where([["company_id",$request->company_id],["npwp_no",$data[1]]])->first();
            if( $check ){
                $response[] = ["name" => strtoupper($data[0]) , "status" => "failed" , "message" => "duplicate npwp no"];
            }else{

                $model              = new Vendor();
                $model->company_id  = strtoupper($request->company_id);
                $model->name        = strtoupper($data[0]);
                $model->npwp_no     = $data[1];
                $model->address     = strtoupper($data[2]);
                $model->balance     = $data[3];
                $model->save();
                
                $response[] = ["name" => strtoupper($data[0]) , "status" => "success" , "message" => ""];
            }   

        }


        return response()
        ->json(['status'=>200 ,'datas' => ["data" => $response, "credentials" => $credentials], 'errors' => null])
        ->withHeaders([
            'Content-Type'          => 'application/json',
        ])
        ->setStatusCode(200);
    }


}