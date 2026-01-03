<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    protected $guarded = [];
    public function appoinment_dtls() 
    {

        return $this->belongsTo(Appointment::class,'appoinment_id');
    }
}
