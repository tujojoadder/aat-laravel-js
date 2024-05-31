<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Story extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'stories'; // Specify the custom table name
    protected $primaryKey = 'story_id';
    public $incrementing = false; // if primay key is string


     protected $fillable = [
       'story_id',
       'story'

    ];
    public function questionAnswerSets()
    {
        return $this->hasMany(QuestionAnswerSet::class, 'story_id', 'story_id');
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
