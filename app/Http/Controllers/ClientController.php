<?php

namespace App\Http\Controllers;

use App\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Passport\Http\Controllers\ClientController as BaseClientController;
use Laravel\Passport\Passport;

class ClientController extends BaseClientController
{
    /**
     * Get all of the clients for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business\Business  $business
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUserBusiness(Request $request, Business $business)
    {        
        $userId  = $request->user()->getKey();

        return $this->activeForUserBusiness($userId, $business->getKey())
            ->makeVisible('secret')
        ;
    }

    /**
     * Store a new client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Laravel\Passport\Client
     */
    public function store(Request $request)
    {
        $this->validation->make($request->all(), [
            'name'          => 'required|max:255',
            'redirect'      => ['required', $this->redirectRule],
            'confidential'  => 'boolean',
            'business_id'   => 'required|max:255',
        ])->validate();

        $client = Passport::client()->forceFill([
            'user_id'                   => $request->user()->getKey(),
            'name'                      => $request->name,
            'secret'                    => Str::random(40),
            'redirect'                  => $request->redirect,
            'personal_access_client'    => false,
            'password_client'           => false,
            'business_id'               => $request->business_id,
            'revoked'                   => false,
        ]);

        $client->save();
        
        return $client->makeVisible('secret');
    }

    /**
     * Get the active client instances for the given user ID.
     *
     * @param  mixed  $userId
     * @param  string  $businessId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function activeForUserBusiness($userId, $businessId)
    {
        $users = Passport::client()
            ->where('user_id', $userId)
            ->where('business_id', $businessId)
            ->orderBy('name', 'asc')->get()
        ;

        return $users->reject(function ($client) {
            return $client->revoked;
        })->values();
    }
}