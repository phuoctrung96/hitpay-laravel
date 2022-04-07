<?php

namespace App\Http\Controllers\MigratedApi;

use App\FirebaseToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class FirebaseTokenController extends Controller
{
    /**
     * FirebaseTokenController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Update Firebase registration ID.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $this->validate($request, [
            'registration_token' => [
                'required',
                'string',
            ],
        ]);

        if ($user->token()) {
            $accessTokenId = $user->token()->id;
            $registrationToken = $data['registration_token'];

            FirebaseToken::where('access_token_id', $accessTokenId)->orWhere('hash', md5($registrationToken))->delete();

            FirebaseToken::create([
                'registration_token' => $registrationToken,
                'access_token_id' => $accessTokenId,
            ]);
        }

        return Response::json([
            'message' => 'Firebase registration ID has been updated.',
        ]);
    }
}
