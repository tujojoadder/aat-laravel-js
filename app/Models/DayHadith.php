<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class DayHadith extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'day_hadiths'; // Specify the custom table name
    protected $primaryKey = 'day_hadith_id';
    public $incrementing = false; // if primay key is string


     protected $fillable = [
       'day_hadith_id',
       'hadith_id',
       'user_id',

    ];







/* who created hadith */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
/* get hadith details from Day hadith */
// DayHadith.php
public function hadith()
{
    return $this->belongsTo(Hadith::class, 'hadith_id', 'hadith_id');
}

public function likes()
{
    return $this->hasMany(DayLike::class, 'day_hadith_id', 'day_hadith_id');
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
