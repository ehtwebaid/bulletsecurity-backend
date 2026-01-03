<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
        protected $table = 'customers';
	public $timestamps = true;
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'name', 'mobile','email','alternate_name','alternate_mobile','address','city','province','postal_code','notes','is_del','primary_contact_name','customer_link'
	];
}
