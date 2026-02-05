<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class About extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'abouts'; // Specify the custom table name
    protected $primaryKey = 'about_id';
    public $incrementing = false; // if primay key is string


     protected $fillable = [
       'about_id',
       'user_id',
       'location',
       'relationship_status',
       'work',
       'education'

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
