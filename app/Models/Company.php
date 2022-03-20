<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table        = 'company';
    protected $primaryKey   = 'company_id';
    public $incrementing    = false;
    protected $keyType      = 'string';
    protected $fillable     = array(
        'name'
    );
    public $timestamps = true; 
	 
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

}
