<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FirebaseToken extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'firebase_tokens';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'access_token_id',
        'registration_token',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        static::saving(function (FirebaseToken $firebaseToken) {
            $firebaseToken->hash = md5($firebaseToken->registration_token);
        });

        parent::boot();
    }
}
