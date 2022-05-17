<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAccountHeader extends Model
{
    protected $table        = 'budget_account_header';
    protected $primaryKey   = 'budget_account_header_id';
    protected $fillable     = array(
        'budget_year_id','name','description','sequence','create_by','update_by'
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

    public function budget_details()
    {
        return $this->hasMany('App\Models\BudgetAccountDetails', 'budget_account_header_id','budget_account_header_id');
    }

    public function budget()
    {
        return $this->belongsTo('App\Models\BudgetAccountYear', 'budget_year_id','budget_year_id');
    }


}
