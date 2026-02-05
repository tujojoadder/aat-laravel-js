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
]);

class UploadRequest extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'uploadrequests'; // Specify the custom table name
    protected $primaryKey = 'uploadrequest_id';
    public $incrementing = false;
    protected $fillable = [
        'uploadrequest_id',
        'uploadrequest_on_id',
        'uploadrequest_on_type',
        'uploadrequest_by',
        'photo_url',
        'type',
        'status',
        'audience'
        
    ];

    public function uploadrequest_on()
    {
        return $this->morphTo('uploadrequest_on');
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
