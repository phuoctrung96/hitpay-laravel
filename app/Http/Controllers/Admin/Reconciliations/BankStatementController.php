<?php

namespace App\Http\Controllers\Admin\Reconciliations;

use App\Http\Controllers\Controller;
use ErrorException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use League;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BankStatementController extends Controller
{
    protected string $root = 'reconciliations/dbs/processed';

    protected string $routeNamePrefix = 'admin.reconciliations.bank-statements';

    protected array $fileTypesMap = [
        'csv' => 'fas fa-file-csv',
        'zip' => 'far fa-file-archive',
        'pdf' => 'far fa-file-pdf',
    ];

    protected string $defaultFileType = 'far fa-file';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index(string $year = null, string $month = null, string $day = null)
    {
        $requestedPaths = array_filter([ $year, $month, $day ]);

        $requestedFullPath = "{$this->root}/".join('/', $requestedPaths);

        if (Storage::exists($requestedFullPath)) {
            $files = Storage::files($requestedFullPath);
            $folders = Storage::directories($requestedFullPath);
        } else {
            $files = $folders = [];
        }

        $files = Collection::make($files);
        $folders = Collection::make($folders);

        $mapper = function (string $type, string $url, string $path) use ($requestedFullPath) : array {
            $name = trim(str_replace($requestedFullPath, '', $path), '/');

            return compact('name', 'url', 'type');
        };

        $folders = $folders->map(function (string $path) use ($mapper, $requestedPaths) : array {
            $requestedPaths[] = Arr::last(explode('/', $path));

            return $mapper('folder', URL::route("{$this->routeNamePrefix}.index", $requestedPaths), $path);
        })->sortByDesc('name');

        $files = $files->map(function (string $path) use ($mapper) : array {
            $parameters = [ 'file' => trim(str_replace($this->root, '', $path), '/') ];

            $extension = pathinfo($path, PATHINFO_EXTENSION);

            $type = $this->fileTypesMap[$extension] ?? $this->defaultFileType;

            return $mapper($type, URL::route("{$this->routeNamePrefix}.download", $parameters), $path);
        })->sortBy('name');

        if (count($requestedPaths)) {
            $parent = URL::route("{$this->routeNamePrefix}.index", array_slice($requestedPaths, 0, -1));
        } else {
            $parent = null;
        }

        $breadcrumb = [];
        $breadcrumbCurrentPath = [];

        foreach ($requestedPaths as $path) {
            $breadcrumbCurrentPath[] = $path;

            $breadcrumb[] = [
                'name' => $path,
                'url' => URL::route("{$this->routeNamePrefix}.index", $breadcrumbCurrentPath),
            ];
        }

        $data = null;
        $date = null;

        if ($day) {
            $date = "{$year}-{$month}-{$day}";

            $filename = "{$requestedFullPath}/summary.json";

            try {
                $summary = Storage::get($filename);
                $summary = json_decode($summary, true);

                $data = $summary;


                if (\request()->has('sss')) {
                    dd($summary);
                }
            } catch (FileNotFoundException $fileNotFoundException) {
                $data = 'No summary generated for this date.';

                goto output;
            } catch (ErrorException $exception) {
                if (!Str::startsWith($exception->getMessage(), 'Undefined index:')) {
                    throw $exception;
                }

                $data = 'The summary file is corrupted.';

                goto output;
            }
            // Laravel is throwing different file not found exception for different function. Check `download()`, it
            // throws `League\Flysystem\FileNotFoundException`.
        }

        output:

        return Response::view('admin.reconciliations.bank-statements', compact(
            'breadcrumb',
            'parent',
            'files',
            'folders',
            'data',
            'date',
        ));
    }

    public function download(Request $request)
    {
        // TODO - We should check who can download these files.
        //
        $filePath = $request->input('file');

        try {
            return Storage::download("{$this->root}/$filePath");
        } catch (League\Flysystem\FileNotFoundException $exception) {
            throw new NotFoundHttpException;
        }
    }

    public function update(Request $request, string $year, string $month, string $day)
    {
        dd(func_get_args());
    }
}
