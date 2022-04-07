<?php

namespace HitPay\Image;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface HasImages
{
    public function images() : MorphMany;
}
