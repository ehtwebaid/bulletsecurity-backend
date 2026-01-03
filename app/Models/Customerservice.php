<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customerservice extends Model
{
        protected $table = 'customerservices';
	public $timestamps = true;
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'name', 'description','color','minutes','service_length','is_del','border_color'
	];
}
