<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class CoverPhoto extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'cover_photos'; // Specify the custom table name
    protected $primaryKey = 'cover_photo_id';
    public $incrementing = false; // if primay key is string


     protected $fillable = [
       'cover_photo_id',
       'image_url'

    ];
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
