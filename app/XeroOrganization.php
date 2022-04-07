<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class XeroOrganization extends Model
{
    use SoftDeletes;

    protected $fillable = ['business_id', 'short_code', 'name'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
