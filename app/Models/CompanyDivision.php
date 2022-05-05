<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyDivision extends Model
{
    protected $table        = 'company_division';
    protected $primaryKey   = 'company_division_id';
    protected $fillable     = array(
        'company_id',"division_id"
    );
    public $timestamps = false; 
	 
    public function getCreatedAtAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['created_at'])
          ->format('Y-m-d H:i:s');
    }
    public function getUpdatedAtAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['updated_at'])
           ->format('Y-m-d H:i:s');
    }


    public function division()
    {
        return $this->belongsTo('App\Models\DivisionMaster', 'division_id','division_id');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id','company_id');
    }


}
