<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class IAccount extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'iaccounts'; // Specify the custom table name
    protected $primaryKey = 'iaccount_id';
    public $incrementing = false; // if primay key is string


    protected $fillable = [
        'iaccount_id',
        'identifier',
        'iaccount_name',
        'iaccount_creator',
        'iaccount_picture',
        'iaccount_cover',
        'is_reported',
        'reported_count',

    ];
    //Get the followers for the IAccount.
    public function upload()
    {
        return $this->morphMany(UploadRequest::class, 'uploadrequest_on');
    }
    public function followers()
    {
        return $this->hasMany(IAccountFollowers::class, 'iaccount_id', 'iaccount_id');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
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
