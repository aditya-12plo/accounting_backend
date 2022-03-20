<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetSubmissionTracking extends Model
{
    protected $table        = 'budget_submission_tracking';
    protected $primaryKey   = 'budget_submission_tracking_id';
    protected $fillable     = array(
        'created_by','remarks'
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
