<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemCode extends Model
{
    protected $table        = 'system_code';
    protected $primaryKey   = 'system_code_id';
    protected $fillable     = array(
        'system_code','value','create_by','update_by','sequence'
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
