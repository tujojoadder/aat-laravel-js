<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\Relation;
Relation::enforceMorphMap([
    'user'=>'App\Models\User',
    'page'=>'App\Models\Pages',
    'group'=>'App\Models\Groups',
    'iaccount'=>'App\Models\IAccount',
    'post'=>'App\Models\Posts',
    'comment'=>'App\Models\Comments',
    'reply'=>'App\Models\Replies',
]);

class Report extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

     protected $table = 'reports'; // Specify the custom table name
     protected $primaryKey = 'report_id'; // Specify the custom primary key
     public $incrementing = false;
     protected $fillable = [
         'report_id',
         'report_on_type',
         'report_on_id',
         'report_by_id',
         'report_type',
         'report_category',
     ];
     public function reportable()
     {
         return $this->morphTo();
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
