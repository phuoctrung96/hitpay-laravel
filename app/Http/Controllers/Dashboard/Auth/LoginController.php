<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Business\Xero;
use App\Exceptions\AuthenticationSecretEnabledException;
use App\Http\Controllers\Controller;
use App\Services\XeroApiFactory;
use App\User;
use App\XeroOrganization;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use XeroAPI\XeroPHP\Api\IdentityApi;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Models\Accounting\Organisation;
use XeroAPI\XeroPHP\Models\Accounting\Organisations;

class LoginController extends Controller
{
    use ThrottlesLogins;

    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the application's login form.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm(Request $request)
    {
        Cache::forget('auth_token:' . $request->session()->getId());

        if ($request->session()->has('errors')) {
            $errors = $request->session()->get('errors');

            if ($errors instanceof ViewErrorBag && $errors->hasBag('default')) {
                foreach ($errors->getBag('default')->toArray() as $key => $messages) {
                    $data['errors'][$key] = Arr::first($messages);
                }
            }

            if (isset($data['errors']['remaining_seconds'])) {
                $data['remaining_seconds'] = $data['errors']['remaining_seconds'];

                unset($data['errors']);
            }
        }

        if ($request->session()->has('_old_input')) {
            $data['_old_input'] = $request->session()->pull('_old_input');
        }

        if ($request->has('email')) {
            $data['_old_input']['email'] = $request->get('email');
        }

        return Response::view('dashboard.authentication.login', [
            'form_data' => $data ?? [],
        ]);
    }

    public function showCheckpointForm(Request $request)
    {
        $token = Cache::get('auth_token:' . $request->session()->getId());

        if (is_null($token)) {
            return Response::redirectToRoute('login');
        }

        return Response::view('dashboard.authentication.checkpoint', [
            'token' => $token,
        ]);
    }

    /**
     * Handle a login request to the application.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function loginStep1(Request $request)
    {
        try {
            $data = $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(URL::route('login'));
        }

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            $seconds = $this->limiter()->availableIn($this->throttleKey($request));

            throw ValidationException::withMessages([
                'remaining_seconds' => $seconds,
            ])->redirectTo(URL::route('login'))->status(HttpResponse::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            if (Auth::attempt($data, true)) {
                $request->session()->regenerate();

                $this->clearLoginAttempts($request);

                if ($request->expectsJson()) {
                    return Response::json([
                        'redirect_url' => $request->session()->pull('url.intended', $this->defaultRedirectTo()),
                    ]);
                }

                return Response::redirectToIntended($this->defaultRedirectTo());
            }
        } catch (AuthenticationSecretEnabledException $exception) {
            $this->clearLoginAttempts($request);

            Cache::put('auth_token:' . $request->session()->getId(), $exception->getToken(), 10 * 60);

            if ($request->expectsJson()) {
                return Response::json([
                    'redirect_url' => URL::route('checkpoint'),
                ]);
            }

            return Response::redirectToRoute('checkpoint');
        }

        $this->incrementLoginAttempts($request);

        throw ValidationException::withMessages([
            $this->username() => Lang::get('auth.failed'),
        ])->redirectTo(URL::route('login'));
    }

    public function loginStep2(Request $request)
    {
        $data = $this->validate($request, [
            'password' => 'required|digits:6',
        ]);
        try {
            $authenticationString = Crypt::decrypt($request->get('authentication_token'));
        } catch (DecryptException $exception) {
            return Response::redirectToRoute('login'); // todo should inclure message
        }

        if (!isset($authenticationString['expires_at'])
            || !isset($authenticationString['user_id'])
            || !isset($authenticationString['email'])) {
            if ($request->expectsJson()) {
                return Response::json([
                    'redirect_url' => URL::route('login'),
                ]);
            }

            return Response::redirectToRoute('login'); // todo should inclure message
        }

        $expiresAt = Date::createFromTimeString($authenticationString['expires_at']);

        if ($expiresAt->isPast()) {
            if ($request->expectsJson()) {
                return Response::json([
                    'redirect_url' => URL::route('login'),
                ]);
            }

            return Response::redirectToRoute('login'); // todo should inclure message
        }

        $user = User::where('id', $authenticationString['user_id'])
            ->where('email', $authenticationString['email'])
            ->first();

        if (!$user) {
            if ($request->expectsJson()) {
                return Response::json([
                    'redirect_url' => URL::route('login'),
                ]);
            }

            return Response::redirectToRoute('login'); // todo should inclure message
        }

        if (!$user->verifyAuthenticationSecret($data['password'])) {
            throw ValidationException::withMessages([
                'password' => 'incorrect',
            ]);
        }

        Auth::login($user, true);

        if ($request->expectsJson()) {
            return Response::json([
                'redirect_url' => $request->session()->pull('url.intended', $this->defaultRedirectTo()),
            ]);
        }

        return Response::redirectToIntended($this->defaultRedirectTo());
    }

    /**
     * Log the user out of the application.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws Exception
     */
    public function logout(Request $request)
    {
        if ($request->get('force_logout')) {
            Auth::logout();
        } else {
            Auth::logoutCurrentDevice();
        }

        $request->session()->invalidate();

        return Response::redirectToRoute('dashboard.home');
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    protected function username(): string
    {
        return 'email';
    }

    /**
     * Get the default post login redirect path.
     *
     * @return string
     */
    protected function defaultRedirectTo(): string
    {
        return URL::route('dashboard.home');
    }

    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($provider)
    {
        if ($provider == 'xero') {
            $xero = new Xero(true);
            $authorizeURL = $xero->authorizeLogin();

            return redirect($authorizeURL);
        } else {
            return Socialite::driver($provider)->redirect();
        }
    }

    /**
     * @param $provider
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function handleProviderCallback(Request $request, $provider)
    {
        if (!$request->has('code')) {
            abort(\Illuminate\Http\Response::HTTP_BAD_REQUEST);
        }

        try {
            $xero = new Xero(true);
            $xero_email_address = null;
            $xeroName = null;
            $accessToken = $xero->provider->getAccessToken('authorization_code', [
                'code' => $request->get('code')
            ]);
            $refreshToken = $accessToken->getRefreshToken(); // save only the refresh token for later use
            $config = Configuration::getDefaultConfiguration()->setAccessToken((string)$accessToken->getToken());
            $identityApi = new IdentityApi(
                new Client(),
                $config
            );
            $result = $identityApi->getConnections();
            $jwtToken = $accessToken->getValues()["id_token"];
            $tokenParts = explode('.', $jwtToken);
            $profile = \GuzzleHttp\json_decode(base64_decode($tokenParts[1]));

            if (isset($profile->email)) {
                $xero_email_address = $profile->email;
            }

            $xeroName = $profile->given_name . ' ' . $profile->family_name;

            $data = [
                'refreshToken' => $refreshToken,
                'tenantId' => $result[0]->getTenantId(),
                'email' => $xero_email_address,
                'name' => $xeroName,
            ];

            cache()->forever('xero_data_' . session()->getId(), (array)$data);

            header('Location: /register');
            die;
        } catch (Exception $exception) {
            Log::error($exception);;

            abort(\Illuminate\Http\Response::HTTP_BAD_REQUEST);
        }

    }
}
