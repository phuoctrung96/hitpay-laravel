<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\ApiKey;
use App\Http\Controllers\Controller;
use App\Manager\ApiKeyManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class ApiKeyController extends Controller
{
    /**
     * ApiKeyController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $paginator = $business->apikeys()->paginate();

        return Response::view('dashboard.business.apikey.index', compact('business', 'paginator'));
    }

    /**
     * Create new resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function create(Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        $apiKey = ApiKeyManager::create($business);

        Session::flash('success_message', 'New API Key has been created successfully.');

        return Response::redirectToRoute('dashboard.business.apikey.index', [
            $business->getKey()
        ]);
    }

    /**
     * Changes status of specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\ApiKey $apiKey
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function changeStatus(Business $business, ApiKey $apiKey)
    {
        Gate::inspect('update', $apiKey)->authorize();

        $key = $apiKey->api_key ?? $apiKey->api_key;

        ApiKeyManager::changeStatus($apiKey);

        Session::flash('success_message', 'The API Key \''.$key.'\' has been updated successfully.');

        return Response::redirectToRoute('dashboard.business.apikey.index', [
            $business->getKey()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\ApiKey $apiKey
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(Business $business, ApiKey $apiKey)
    {
        Gate::inspect('delete', $apiKey)->authorize();

        $key = $apiKey->api_key ?? $apiKey->api_key;

        ApiKeyManager::delete($apiKey);

        Session::flash('success_message', 'The API Key \''.$key.'\' has been deleted successfully.');

        return Response::redirectToRoute('dashboard.business.apikey.index', [
            $business->getKey()
        ]);
    }
}