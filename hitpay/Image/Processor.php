<?php

namespace HitPay\Image;

use App\Business;
use App\Business\Image as ImageModel;
use App\Enumerations\Business\ImageGroup;
use App\Enumerations\Image\Size;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Constraint as ImageConstraint;
use Intervention\Image\Facades\Image as ImageFacade;

final class Processor
{
    /**
     * The configurations for different image type.
     *
     * @var array
     */
    private $configurations = [
        ImageGroup::LOGO => [
            Size::ORIGINAL => null,
            Size::LARGE => 1024,
            Size::MEDIUM => 512,
            Size::SMALL => 256,
            Size::THUMBNAIL => 128,
            Size::ICON => 64,
        ],
        ImageGroup::PRODUCT => [
            Size::ORIGINAL => null,
            Size::LARGE => 2048,
            Size::MEDIUM => 1024,
            Size::SMALL => 512,
            Size::THUMBNAIL => 256,
            Size::ICON => 128,
        ],
        ImageGroup::COVER => [
            Size::ORIGINAL => null,
            Size::LARGE => 2048,
            Size::MEDIUM => 1024,
            Size::SMALL => 512,
            Size::THUMBNAIL => 256,
            Size::ICON => 128
        ]
    ];

    /**
     * The group for the image to be processed.
     *
     * @var string
     */
    private $group;

    /**
     * The associableed file.
     *
     * @var mixed
     */
    private $file;

    /**
     * The caption of the image, if have.
     *
     * @var string|null
     */
    private $caption = null;

    /**
     * The business.
     *
     * @var \App\Business
     */
    private $business;

    /**
     * @var \HitPay\Image\HasImages|\Illuminate\Database\Eloquent\Model|null
     */
    private $associable;

    /**
     * Processor constructor.
     *
     * @param \App\Business $business
     * @param string $group
     * @param $file
     * @param \HitPay\Image\HasImages|null $associable
     *
     * @throws \Exception
     */
    private function __construct(Business $business, string $group, $file, HasImages $associable = null)
    {
        if (!isset($this->configurations[$group])) {
            throw new Exception('No configuration found for this group for the image.');
        }

        $this->business = $business;
        $this->group = $group;
        $this->file = $file;
        $this->associable = $associable;
    }

    /**
     * Get the caption of the image.
     *
     * @return string|null
     */
    public function getCaption() : ?string
    {
        return $this->caption;
    }

    /**
     * Set the caption of the image.
     *
     * @param string|null $caption
     *
     * @return $this
     */
    public function setCaption(?string $caption) : self
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * Process the image.
     *
     * @return \App\Business\Image
     * @throws \Exception
     */
    public function process() : ImageModel
    {
        $configuration = $this->configurations[$this->group];

        $imageFile = ImageFacade::make($this->file);

        // TODO - 2020-02-16
        // We are converting all images into JPEG format. We will need to update this method if we decided to accept and
        // store different format images in future.

        $imageModel = new ImageModel;

        $imageModel->group = $this->group;
        $imageModel->media_type = 'image/jpeg';
        $imageModel->extension = 'jpg';
        $imageModel->caption = $this->getCaption();

        $imageModel->business()->associate($this->business);

        if ($this->associable) {
            $imageModel->associable()->associate($this->associable);
        }

        $storageDefaultDisk = Storage::getDefaultDriver();
        $imageModel->disk = $storageDefaultDisk;

        if (Config::get('filesystems.disks.'.$imageModel->disk.'.driver') === 's3') {
            $imageModel->disk .= ':'.Config::get('filesystems.disks.'.$imageModel->disk.'.bucket');
        }

        $filename = str_replace('-', '', Str::orderedUuid()->toString()).'.jpg';
        $destination = Str::plural($this->group).DIRECTORY_SEPARATOR;
        $otherDimensions = [];
        $storageSize = 0;

        try {
            foreach ($configuration as $imageSize => $pixels) {
                $imageFile = $imageFile ?? ImageFacade::make($this->file);

                if ($imageSize !== Size::ORIGINAL) {
                    $imageFile->resize($pixels, $pixels, function (ImageConstraint $constraint) {
                        $constraint->aspectRatio();
                    });
                }

                $streamed = $imageFile->stream('jpg');
                $fileSize = strlen($streamed);

                if ($imageSize === Size::ORIGINAL) {
                    $path = $destination.$filename;

                    $imageModel->path = $path;
                    $imageModel->width = $imageFile->getWidth();
                    $imageModel->height = $imageFile->getHeight();
                    $imageModel->file_size = $fileSize;
                } else {
                    $path = $destination.$imageSize.DIRECTORY_SEPARATOR.$filename;

                    $otherDimensions[$imageSize] = [
                        'size' => $imageSize,
                        'path' => $path,
                        'width' => $imageFile->getWidth(),
                        'height' => $imageFile->getHeight(),
                        'file_size' => $fileSize,
                    ];
                }

                if ($this->group === ImageGroup::PRODUCT) {
                    Storage::disk($storageDefaultDisk)->put($path, $streamed, 'public');
                } else {
                    Storage::disk($storageDefaultDisk)->put($path, $streamed);
                }

                $storageSize += $fileSize;

                unset($imageFile, $streamed, $fileSize, $path);
            }

            $imageModel->storage_size = $storageSize;
            $imageModel->other_dimensions = $otherDimensions;
            $imageModel->save();
        } catch (Exception $exception) {
            $toBeDeleted = collect($otherDimensions)->pluck('path');

            if ($imageModelPath = $imageModel->path) {
                $toBeDeleted->add($imageModelPath);
            }

            Storage::delete($toBeDeleted->toArray());

            throw $exception;
        }

        return $imageModel;
    }

    /**
     * Create a static object.
     *
     * @param mixed ...$arguments
     *
     * @return $this
     * @throws \Exception
     */
    public static function new(...$arguments)
    {
        return new static(...$arguments);
    }
}
