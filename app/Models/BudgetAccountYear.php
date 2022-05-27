<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAccountYear extends Model
{
    protected $table        = 'budget_year';
    protected $primaryKey   = 'budget_year_id';
    protected $fillable     = array(
        'year','status','create_by','update_by'
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

     
    public function budget_account_details()
    {
        return $this->hasMany('App\Models\BudgetAccountDetails', 'budget_year_id','budget_year_id');
    }

}
