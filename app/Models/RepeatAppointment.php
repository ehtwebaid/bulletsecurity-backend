<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepeatAppointment extends Model
{
    //
       protected $table = 'repeat_appointments';
	   public $timestamps = true;
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'appointment_id','start_time','end_time','parent_id'
	];
}
