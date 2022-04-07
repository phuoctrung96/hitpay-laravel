<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmailAttachmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, string $folder = null)
    {
        /** @var \App\User $user */
        $user = $request->user();

        $parent = null;
        $storagePath = storage_path('app/');
        $rootPath = "{$storagePath}email-attachments/";
        $currentPath = $rootPath;

        if (!is_null($folder)) {
            $parent = URL::route('admin.email-attachment.index');
            $currentPath .= "{$folder}/{$user->email}/";
        }

        if (File::exists($currentPath)) {
            $folders = Collection::make(File::directories($currentPath));
            $files = Collection::make(File::files($currentPath));
        } else {
            $folders = $files = Collection::make();
        }

        $folders = $folders->map(function (string $path) use (
            $rootPath,
            $currentPath
        ) : array {
            return [
                'name' => trim(str_replace($currentPath, '', $path), '/'),
                'url' => URL::route('admin.email-attachment.index', [ 'any' => str_replace($rootPath, '', $path) ]),
                'type' => 'folder',
            ];
        });

        $files = $files->map(function (string $path) use (
            $storagePath,
            $currentPath
        ) : array {
            return [
                'name' => trim(str_replace($currentPath, '', $path), '/'),
                'url' => URL::route('admin.email-attachment.download', [
                    'any' => str_replace($storagePath, '', $path),
                ]),
                'type' => 'file',
            ];
        });

        return Response::view('admin.email-attachments', compact('folders', 'files', 'parent'));
    }

    public function download(Request $request, string $any)
    {
        /** @var \App\User $user */
        $user = $request->user();

        // TODO - 20210926
        //   --------------->>>
        //   This is a temporary solution.
        //
        switch (false) {
            case !!$user->role:
            case count($segments = $request->segments()) === 5:
            case $segments[3] === $user->getEmailForPasswordReset():
            case Storage::disk('local')->exists($any);
                throw new NotFoundHttpException;
        }

        return Response::download(storage_path("app/$any"));
    }
}
