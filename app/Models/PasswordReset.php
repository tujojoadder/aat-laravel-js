<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PasswordReset extends Model
{

    public $timestamps = false; // Disable timestamps
    protected $table = 'password_resets'; 
    protected $primaryKey = 'id';
    public $incrementing = false; // if primay key is string

     protected $fillable = [
       'id',
       'email',
       'token',
       'created_at',

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
