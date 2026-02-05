<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class TextPosts extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'text_posts'; // Specify the custom table name
    protected $primaryKey = 'text_post_id';
    public $incrementing = false; // if primay key is string

    protected $fillable = [
        'text_post_id',
        'post_id',
        'post_text',

    ];
    public function post()
{
    return $this->belongsTo(Posts::class, 'post_id', 'post_id');
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
