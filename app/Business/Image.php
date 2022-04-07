<?php

namespace App\Business;

use App\Enumerations\Image\Size;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Image extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_images';

    protected $casts = [
        'height' => 'int',
        'width' => 'int',
        'file_size' => 'int',
        'storage_size' => 'int',
        'other_dimensions' => 'collection',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleted(function (self $model) : void {
            $paths = $model->getAttribute('other_dimensions')
                ->pluck('path')
                ->add($model->getAttribute('path'))
                ->toArray();

            Storage::disk(explode(':', $model->disk)[0])->delete($paths);
        });
    }

    public function associable() : MorphTo
    {
        return $this->morphTo('associable', 'business_associable_type', 'business_associable_id', 'id');
    }

    /**
     * Get the URL for the image.
     *
     * @param string $size
     *
     * @return string|null
     */
    public function getUrl(string $size = Size::MEDIUM) : ?string
    {
        if (!$this->exists) {
            return null;
        }

        $otherDimensions = $this->other_dimensions;

        if (isset($otherDimensions[$size]['path'])) {
            $url = Storage::url($otherDimensions[$size]['path']);
        } else {
            $url = Storage::url($this->path);
        }

        return URL::to($url);
    }

    //associable
}
