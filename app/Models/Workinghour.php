<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Workinghour extends Model
{
    //
       protected $table = 'working_hours';
	public $timestamps = true;
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'user_id', 'day_name','start_time','end_time','is_weekday'
	];
}
