<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DivisionMaster extends Model
{
    protected $table        = 'division_master';
    protected $primaryKey   = 'division_id';
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
