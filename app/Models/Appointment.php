<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\AppointmentLog;
use Illuminate\Support\Facades\Auth;
class Appointment extends Model
{
    protected $table = 'appointments';
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id', 'staff_id', 'customer_id', 'start_time', 'is_repeat', 'notes', 'is_del', 'job_completion', 'extra_materials', 'summary', 'price', 'customerservice_id', 'current_status', 'staff',
        'service', 'code', 'custom_type', 'custom_duration', 'no_term', 'end_time', 'project_manager', 'time_in', 'time_out'
    ];
    public function customer()
    {

        return $this->belongsTo(Customer::class);
    }
    public function staff()
    {

        return $this->belongsTo(User::class, 'staff_id');
    }
    public function service()
    {

        return $this->belongsTo(Customerservice::class, 'customerservice_id')->where('is_del','0');
    }
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
    public function favourite_dtls()
    {
        return $this->belongsTo(Favourite::class,'id','appoinment_id')->withDefault(['is_favourite' => '0']);
    }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            AppointmentLog::create([
                'action' => 'Add', // Define a default action
                'appointment_id' => $model->id,
                'user_id' => Auth::guard('api')->id(), // Or Auth::id() for web guard
                'changes' => ([
                    'after' => $model->getAttributes(),
                ]),
            ]);
        });
        static::updating(function ($model) {
            // Capture original values
            $original = $model->getOriginal();
            // Capture only changed fields
            $changedFields = $model->getDirty();
            $original = array_map(function ($value) {
                return $value === null ? 0 : $value;
            }, $original);

            $changedFields = array_map(function ($value) {
                return $value === null ? 0 : $value;
            }, $changedFields);

            $result1 = array_diff_assoc($original, $changedFields);
            $result2 = array_diff_assoc($changedFields, $original);

            $filteredChanges = array_filter($changedFields, function ($value, $key) use ($model) {
                $originalValue = $model->getOriginal($key);
                // Exclude if original value is null and new value is 0
                return !(is_null($originalValue) && $value == 0);
            }, ARRAY_FILTER_USE_BOTH);

            // If no relevant changes, prevent the update
            if (empty($filteredChanges)) {

                return false;
            }
            if (!empty($changedFields)) {
                AppointmentLog::create([
                    'action'  => request()->input('action'), // Custom log title from request
                    'appointment_id'   => $model->id,
                    'user_id'   =>\Auth::guard('api')->id(),
                    'changes'    => ([
                        'before' => array_intersect_key($result1, $result2),
                        'after'  => $result2,
                    ]),
                ]);
            }
        });
    }
}
