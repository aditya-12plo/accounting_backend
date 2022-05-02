<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCompany extends Model
{
    protected $table        = 'user_company';
    protected $primaryKey   = 'user_company_id';
    protected $fillable     = array(
        'user_id','company_id'
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


    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id','company_id');
    }

}
