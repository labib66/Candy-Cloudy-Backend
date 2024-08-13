<?php

namespace Common\Auth\Fortify;

use Common\Core\Bootstrap\BootstrapData;
use Common\Core\Bootstrap\MobileBootstrapData;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): JsonResponse
    {
        $response = [
            'status' => $request->user()->hasVerifiedEmail()
                ? 'success'
                : 'needs_email_verification',
        ];

        $token_names = $request->get('token_name') ?? "ali";
        $bootstrapData = app(MobileBootstrapData::class)->init();
        $bootstrapData->refreshToken($token_names);
        $response['boostrapData'] = $bootstrapData->get();
        $response['boostrapData'] =$response['boostrapData']['user'] ;

        // for mobile
        // if ($request->has('token_name')) {
        //     $bootstrapData = app(MobileBootstrapData::class)->init();
        //     $bootstrapData->refreshToken($request->get('token_name'));
        //     $response['boostrapData'] = $bootstrapData->get();
        //     $response['boostrapData'] =$response['boostrapData']['user'] ;
        //     // for web
        // } else {
        //     $bootstrapData = app(BootstrapData::class)->init();
        //     $response['bootstrapData'] = $bootstrapData->getEncoded();
        // }

        return response()->json($response);
    }
}
