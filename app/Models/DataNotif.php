<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataNotif extends Model
{
    protected $table        = 'notification';
    protected $primaryKey   = 'notification_id';
    protected $fillable     = array(
        'token',
        'channel',
        'event',
        'key_id',
        'status',
        'messages'
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
