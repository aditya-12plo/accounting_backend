<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAccountAccounting extends Model
{
    protected $table        = 'budget_account_accounting';
    protected $primaryKey   = 'budget_account_accounting_id';
    protected $fillable     = array(
        'bb','bt','sbt','description'
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
