<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'details'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, string $details): void
    {
        static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'details' => $details,
        ]);
    }
}