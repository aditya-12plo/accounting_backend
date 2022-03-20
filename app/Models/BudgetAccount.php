<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAccount extends Model
{
    protected $table        = 'budget_account';
    protected $primaryKey   = 'budget_account_id';
    protected $fillable     = array(
        'company_id','division_id','years','bb','bt','sbt','description'
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

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id','division_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id','company_id');
    }
}
