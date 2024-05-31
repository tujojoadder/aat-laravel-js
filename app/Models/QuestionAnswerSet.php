<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class QuestionAnswerSet extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'question_answer_sets'; // Specify the custom table name
    protected $primaryKey = 'question_id';
    public $incrementing = false; // if primay key is string


     protected $fillable = [
       'question_id',
       'question',
       'wrong_ans',
       'correct_ans',
       'story_id',

    ];
    public function story()
    {
        return $this->belongsTo(Story::class, 'story_id', 'story_id');
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
