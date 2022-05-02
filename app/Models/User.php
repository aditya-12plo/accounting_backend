<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

    protected $table = 'user';
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'division_id','name', 'email','token','status','company_id','level'
    ];
 
    protected $hidden = [
        'password',
    ];

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

    public function user_company()
    {
        return $this->hasMany('App\Models\UserCompany', 'user_id','user_id');
    }

    public function division()
    {
        return $this->belongsTo('App\Models\DivisionMaster', 'division_id','division_id');
    }
}
