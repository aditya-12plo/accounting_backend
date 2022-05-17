<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAccountDetails extends Model
{
    protected $table        = 'budget_account_detail';
    protected $primaryKey   = 'budget_account_detail_id';
    protected $fillable     = array(
        'budget_year_id','budget_account_header_id','bb','bt','sbt','description','total','create_by','update_by'
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


    public function budget_account_year()
    {
        return $this->belongsTo('App\Models\BudgetAccountYear', 'budget_year_id','budget_year_id');
    }


    public function budget_account_header()
    {
        return $this->belongsTo('App\Models\BudgetAccountHeader', 'budget_account_header_id','budget_account_header_id');
    }


}
