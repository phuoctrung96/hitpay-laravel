<?php

namespace App\Http\Resources\Business;

use App\Enumerations\Image\Size;
use App\Exceptions\HitPayLogicException;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * @mixin \App\Business\Image
 */
class Image extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|mixed
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function toArray($request)
    {
        $data['id'] = $this->getKey();
        $data['caption'] = $this->caption;
        $data['url'] = $this->getUrl(Size::ORIGINAL);
        $data['other_dimensions'] = [];

        foreach ($this->other_dimensions as $dimension) {
            $data['other_dimensions'][] = [
                'size' => $dimension['size'],
                'path' => $this->getUrl($dimension['size']),
            ];
        }

        return $data;
    }
}
