<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserAnswer extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user_answers'; // Specify the custom table name
    protected $primaryKey = 'user_answer_id';
    public $incrementing = false; // if primay key is string


     protected $fillable = [
       'user_answer_id',
       'user_id',
       'hadith_id',
       'question_id',
       'selected_ans',
       'is_correct',
       'earned_points',

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
