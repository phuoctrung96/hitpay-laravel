<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use OpenApi as OA;

class Controller extends BaseController
{
    /**
     * @OA\Info(
     *      version="1.0.0",
     *      title="HitPay API Documentation",
     *      description="",
     *      @OA\Contact(
     *          email="admin@hitpay.com"
     *      ),
     *      @OA\License(
     *          name="Apache 2.0",
     *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
     *      )
     * )
     *
     * @OA\Server(
     *      url=L5_SWAGGER_CONST_HOST,
     *      description="API Server"
     * )
     *
     *
     * @OA\Tag(
     *     name="PaymentRequests",
     *     description="API Endpoints of Payment Requests"
     * )
     * 
     * @OA\SecurityScheme(
     *     type="http",
     *     description="Login with email and password to get the authentication token",
     *     name="Token based Based",
     *     in="header",
     *     scheme="bearer",
     *     bearerFormat="JWT",
     *     securityScheme="apiAuth",
     * )
     */

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    

    public function requestHelperForBusinessWith(Request $request, Relation $model)
    {
        if ($with = $request->get('with')) {
            $with = is_array($with) ? $with : explode(',', $with);
            $with = array_map(function ($value) {
                return trim($value);
            }, $with);
            $with = array_unique($with);

            foreach ($with as $index => $relationship) {
                if (!in_array($relationship, static::$relationships)) {
                    unset($with[$index]);
                }
            }

            if (count($with)) {
                $model->with($with);
            }
        }

        return $model;
    }
}
