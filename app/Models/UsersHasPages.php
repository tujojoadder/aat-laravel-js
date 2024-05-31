<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UsersHasPages extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

     protected $table = 'users_has_pages'; // Specify the custom table name
     protected $primaryKey = ['user_id', 'page_id'];
     public $incrementing = false;

     protected $fillable = [
         'user_id',
         'page_id'
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
