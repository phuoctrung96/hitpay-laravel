<?php

namespace App\Exceptions;

use HitPay\MyInfoSG\Exceptions\AccessTokenNotFoundException;
use HitPay\MyInfoSG\Exceptions\InvalidAccessTokenException;
use HitPay\MyInfoSG\Exceptions\InvalidDataOrSignatureForPersonDataException;
use HitPay\MyInfoSG\Exceptions\MyInfoPersonDataNotFoundException;
use HitPay\MyInfoSG\Exceptions\SubNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Laravel\Passport\Exceptions\OAuthServerException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        AccessTokenNotFoundException::class,
        AuthenticationSecretEnabledException::class,
        InvalidAccessTokenException::class,
        InvalidDataOrSignatureForPersonDataException::class,
        MyInfoPersonDataNotFoundException::class,
        OAuthServerException::class,
        SubNotFoundException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'auth_code',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'password',
        'password_confirmation',
        'recovery_code',
    ];

    public function report(\Exception $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }
}
