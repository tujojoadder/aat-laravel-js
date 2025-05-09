<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class DayLike extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'day_likes'; // Specify the custom table name
    protected $primaryKey = 'day_likes_id';
    public $incrementing = false; // if primay key is string


    protected $fillable = [
        'day_likes_id',
        'day_hadith_id',
        'user_id'
    ];



    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
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
