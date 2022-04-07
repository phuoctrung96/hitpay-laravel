<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LocalStorageController extends Controller
{
    /**
     * The accessible directories.
     *
     * @var array
     */
    public $publicDirectories = [
        'logos',
        'products',
        'recurring_plans',
        'covers'
    ];

    /**
     * A method to get file from local storage, without using symbolic links, this is mainly for development purpose,
     * calling this endpoint in production should always return 404.
     *
     * @param string ...$path
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getFile(string ...$path)
    {
        switch (true) {

            case App::environment('production'):
            case Storage::getDefaultDriver() !== 'local':
            case !in_array($path[0], $this->publicDirectories):
            case !Storage::exists($finalPath = implode(DIRECTORY_SEPARATOR, $path)):
                throw new NotFoundHttpException;
        }

        return Response::file(storage_path('app'.DIRECTORY_SEPARATOR.$finalPath));
    }
}
