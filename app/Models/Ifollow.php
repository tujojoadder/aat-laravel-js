<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Ifollow extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'ifollow';
    protected $primaryKey = 'ifollow_id';
    public $incrementing = false;

    protected $fillable = [
        'ifollow_id',
        'iaccount_id',
        'follower_id'
    ];

    public function iaccount()
    {
        return $this->belongsTo(User::class, 'iaccount_id', 'user_id');
    }

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id', 'user_id');
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    /*  protected $hidden = [
        'password',
        'remember_token',
    ];
 */
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
