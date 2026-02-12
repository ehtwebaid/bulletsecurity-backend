<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    //
       protected $table = 'calender_settings';
	public $timestamps = true;
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'default_mode', 'week_start','time_interval','timepicker_interval','start_hour','calender_stats','no_emp','end_hour','default_color','completion_color','border_color'
	];
}
