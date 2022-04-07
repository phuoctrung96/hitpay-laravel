<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Shortcut extends Model
{
    protected $table = 'shortcuts';

    public $incrementing = false;

    protected $casts = [
        'parameters' => 'array',
    ];

    protected $fillable = [
        'route_name',
        'parameters',
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $url) {
            $random = null;
            $count = 0;

            do {
                $temp = strtolower(Str::random(6));

                $count++;

                if (static::find($temp) === null) {
                    $random = $temp;
                }
            } while ($random === null || $count <= 5);

            $url->setAttribute('id', $random);
        });
    }
}
