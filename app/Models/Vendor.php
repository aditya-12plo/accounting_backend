<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $table        = 'vendor';
    protected $primaryKey   = 'vendor_id';
    protected $fillable     = array(
        "name","address","npwp_no","balance","vendor01",
        "vendor02",
        "vendor03",
        "vendor04",
        "vendor05",
        "vendor06",
        "vendor07",
        "vendor08",
        "vendor09",
        "vendor10"
    );
    public $timestamps = true; 
	 

}
