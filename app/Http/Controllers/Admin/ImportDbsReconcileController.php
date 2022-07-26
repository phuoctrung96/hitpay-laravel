<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades;

class ImportDbsReconcileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): \Illuminate\Http\Response
    {
        return Response::view('admin.form-import-dbs-reconcile');
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
            'csv' => 'required',
            'csv.*' => 'required|mimes:csv,txt'
        ]);

        $files = $request->file('csv');

        $path = 'reconciliations/dbs/waiting-list';

        foreach ($files as $file) {
            Facades\Storage::putFileAs($path, $file, $file->getClientOriginalName());
        }

        return Response::json([
            'status' => 'success',
            'message' => 'Successfully processed.'
        ]);
    }
}
