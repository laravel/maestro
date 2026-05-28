<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

#[Group('Authentication')]
class RegisterController extends Controller
{
    #[Endpoint(title: 'Register', description: 'Create a new user account and return an API token.')]
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = DB::transaction(function () use ($request): array {
            $user = User::create($request->validated());

            return [$user, $user->createToken($request->string('device_name', 'auth'))->plainTextToken];
        });

        event(new Registered($user));

        return (new UserResource($user))
            ->ignoreFieldsAndIncludesInQueryString()
            ->additional(['meta' => ['token' => $token]])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
