<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Basicsetting extends Model
{
    //
       protected $table = 'basic_settings';
	public $timestamps = true;
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'industry', 'phone_no','currency','country','company_name'
	];
}
