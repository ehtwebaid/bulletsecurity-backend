<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class AppointmentLog extends Model
{
    protected $table = 'appointment_logs';
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'appointment_id', 'user_id', 'action','changes'
    ];
    protected $casts = [
        'changes' => 'array', // Cast the changes field to an array
    ];
    // Define the accessor for the "before" staff
    public function staff()
    {

        return $this->belongsTo(User::class, 'user_id')->select(['id', 'name']);
    }
    public function appointment()
    {

        return $this->belongsTo(Appointment::class, 'user_id')->select(['id', 'code']);
    }
    public function user()
    {

        return $this->belongsTo(User::class, 'user_id');
    }

}
