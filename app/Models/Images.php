<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Images extends Model
{
    protected $guarded = ['id'];
    public function scopeTable($query)
    {
        return $query ->from('images  as i');
    }
    public function scopeByUserId($query, $user_id)
    {
        return $query -> where('user_id', $user_id);
    }
    public function scopeToday($query)
    {
        $today = Carbon::now() -> format('Y-m-d');
        return $query ->whereDate('created_at', $today);
    }
    public function scopeBetweenDates($query, $start_date, $end_date)
    {
        return $query -> whereDate('created_at', '>=', $start_date)
                      -> whereDate('created_at', '<=', $end_date);
    }
    public function scopeActive($query)
    {
        return $query -> where('status', '1');
    }
}
