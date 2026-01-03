<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    //
       protected $table = 'logs';
	public $timestamps = true;
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'user_id', 'action','ip','action_by'
	];
	public function staff()
    {

        return $this->belongsTo(User::class, 'user_id');
    }
	public function actionuser()
    {

        return $this->belongsTo(User::class, 'action_by');
    }
}
