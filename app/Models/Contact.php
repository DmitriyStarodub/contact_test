<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class Contact extends Model
{
    use HasFactory;

    protected $table = 'contacts';

    const PERPAGE = 10;

    protected $fillable = [
       'id',
        'title',
        'first_name',
        'last_name',
        'email',
        'tz',
        'date',
        'time',
        'note',
        'ip'
    ];

    protected $hidden = [];

    protected $appends = [
        'date_time',
        'image_url'
    ];

    public function getImageUrlAttribute()
    {
        return asset('/contacts/' . $this->id.'.jpeg');
    }

    public function getDateTimeAttribute()
    {
        $time_zone = Redis::get('timezone')? Redis::get('timezone'):$this->tz;

        Redis::set('timezone', config('app.timezone'));

        return Carbon::parse($this->date.' '.$this->time, $time_zone);
    }
}
