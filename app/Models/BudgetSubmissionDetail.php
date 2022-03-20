<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetSubmissionDetail extends Model
{
    protected $table        = 'budget_submission_detail';
    protected $primaryKey   = 'budget_submission_detail_id';
    protected $fillable     = array(
        'budget_submission_header_id','budget_account_id','budget_account_accounting_id','remarks','total'
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

    public function budget_account()
    {
        return $this->belongsTo(BudgetAccount::class, 'budget_account_id','budget_account_id');
    }

    public function header()
    {
        return $this->belongsTo(BudgetSubmissionHeader::class, 'budget_submission_header_id','budget_submission_header_id');
    }

    public function budget_account_accounting()
    {
        return $this->belongsTo(BudgetAccountAccounting::class, 'budget_account_accounting_id','budget_account_accounting_id');
    }

}