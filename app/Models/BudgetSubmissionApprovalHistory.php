<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetSubmissionApprovalHistory extends Model
{
    protected $table        = 'budget_submission_approval_history';
    protected $primaryKey   = 'budget_submission_approval_history_id';
    protected $fillable     = array(
        'budget_submission_header_id','user_id','status'
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

    public function header()
    {
        return $this->belongsTo(BudgetSubmissionHeader::class, 'budget_submission_header_id','budget_submission_header_id');
    }
}
