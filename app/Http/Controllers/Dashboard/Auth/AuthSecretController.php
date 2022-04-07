<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Http\Controllers\Controller;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class AuthSecretController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show auth secret management page or redirect to auth secret setup page.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function showHomepage()
    {
        $user = Auth::user();

        if ($user->isAuthenticationSecretEnabled()) {
            return Response::view('dashboard.auth-secret.home', [
                'user' => $user,
            ]);
        }

        return Response::redirectToRoute('dashboard.auth-secret.setup');
    }

    /**
     * Show auth secret setup page.
     *
     * @return \Illuminate\Http\Response
     */
    public function showSetUpPage()
    {
        return Response::view('dashboard.auth-secret.setup', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Get the new secret for auth secret.
     *
     * @param \Illuminate\Http\Request $request
     * @param \PragmaRX\Google2FA\Google2FA $google2FA
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     */
    public function secret(Request $request, Google2FA $google2FA)
    {
        $user = Auth::user();

        $secretKey = $google2FA->generateBase32RandomKey(32);

        $qrCode = new QrCode($google2FA->getQRCodeUrl(Config::get('app.name'), $user->getEmailForPasswordReset(),
            $secretKey));

        $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevel(ErrorCorrectionLevel::HIGH));
        $qrCode->setSize(512);
        // todo is the color correct?
        $qrCode->setBackgroundColor([
            'r' => 248,
            'g' => 249,
            'b' => 250,
        ]);
        $qrCode->setForegroundColor([
            'r' => 33,
            'g' => 37,
            'b' => 41,
        ]);

        Cache::put($this->getCacheKey($request), [
            'user_id' => $user->id,
            'secret' => $secretKey,
        ], 30);

        return Response::json([
            'secret' => [
                'string' => $secretKey,
                'qr_code' => $qrCode->writeDataUri(),
            ],
        ]);
    }

    /**
     * Enable auth secret.
     *
     * @param \Illuminate\Http\Request $request
     * @param \PragmaRX\Google2FA\Google2FA $google2FA
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     */
    public function enable(Request $request, Google2FA $google2FA)
    {
        $user = Auth::user();

        $data = $this->validate($request, [
            'secret' => [
                'required',
                'string',
            ],
            'auth_code' => [
                'required',
                'string',
                'digits:6',
            ],
        ]);

        $requestedSecret = Cache::get($this->getCacheKey($request));

        switch (true) {

            case is_null($requestedSecret):
            case !isset($requestedSecret['user_id']):
            case $requestedSecret['user_id'] !== $user->id:
            case !isset($requestedSecret['secret']):
            case $requestedSecret['secret'] !== $data['secret']:
                return Response::json([
                    'code' => 'auth_secret_invalid',
                    'message' => Lang::get('The page has expired due to inactivity. Reload this page and try again.'),
                    'redirect_url' => URL::route('dashboard.user.security.secret'),
                ], 400);

            case $google2FA->verify($data['auth_code'], $data['secret']) === false:
                throw ValidationException::withMessages([
                    'auth_code' => Lang::get('The authentication code doesn\'t match.'),
                ]);
        }

        $isResetting = $user->isAuthenticationSecretEnabled();

        $user->authentication_secret = $data['secret'];
        $user->save();

        Cache::forget($this->getCacheKey($request));

        // $analyzedData = $user->auth_secret['extra_data'];

        // $user->notify(new AuthSecretEnabledNotification($isResetting, $analyzedData));

        // Event::dispatch(new AuthSecretEnabledEvent($user, $isResetting, $analyzedData));

        if ($isResetting) {
            $message = Lang::get('Auth secret successfully reset!');
        } else {
            $message = Lang::get('Auth secret successfully enabled!');
        }

        // todo - use toast ?
        $request->session()->flash('security.success', $message);

        return JsonResponse::create([
            'message' => $message,
            'redirect_url' => URL::route('dashboard.user.security.home'),
        ]);
    }

    /**
     * Disable auth secret.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function disable(Request $request)
    {
        $user = Auth::user();

        $user->authentication_secret = null;
        $user->save();

        // Event::dispatch(new AuthSecretDisabledEvent($user, $analyzedData));

        $message = Lang::get('Auth secret successfully disabled!');

        // todo template havent show this. we should use toast.
        $request->session()->flash('security.info', $message);

        return JsonResponse::create([
            'message' => $message,
            'redirect_url' => URL::route('dashboard.user.security.home'),
        ]);
    }

    /**
     * Returns a cache key for the given Request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return string
     */
    private function getCacheKey(Request $request) : string
    {
        return 'auth_secret:'.$request->session()->getId();
    }
}
