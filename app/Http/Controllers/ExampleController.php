<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App,DB;
use PDF;

class ExampleController extends Controller
{ 
    public function __construct()
    {
         
    }

    public function index(Request $request){ 

        echo "ok";
    
    }



    /**
     * excel
     */




    /**
     * pdf
     */

    public function downloadPdf(Request $request){ 

        $data   = ["title" => "asdasd"];
        $pdf = PDF::loadView('pdf.invoice', $data);
        return $pdf->download('invoice.pdf');
    
    }

    public function getPdfFromHtml(Request $request){ 
        $pdf = App::make('dompdf.wrapper');
        $pdf->loadHTML('<h1>Test</h1>');
        return $pdf->stream();
    
    }
}
