<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetSubmissionHeader extends Model
{
    protected $table        = 'budget_submission_header';
    protected $primaryKey   = 'budget_submission_header_id';
    protected $fillable     = array(
        'company_id','division_id','form_no','date_form','vendor_id','invoice_no','invoice_date',
        'invoice_tax_no','remarks','total','ppn','pph','discount','down_payment','outstanding','status'
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

    public function details()
    {
        return $this->hasMany(BudgetSubmissionDetail::class, 'budget_submission_header_id','budget_submission_header_id');
    }

    public function trackings()
    {
        return $this->hasMany(BudgetSubmissionTracking::class, 'budget_submission_header_id','budget_submission_header_id');
    }

}
