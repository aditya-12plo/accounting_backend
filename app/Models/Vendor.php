<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $table        = 'vendor';
    protected $primaryKey   = 'vendor_id';
    protected $fillable     = array(
        'code','name','address','npwp_no','balance'
    );
    public $timestamps = true; 
	 

}
