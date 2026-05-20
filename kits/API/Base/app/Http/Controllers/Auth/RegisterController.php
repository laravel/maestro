<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Authentication')]
class RegisterController extends Controller
{
    #[Endpoint('Register', 'Create a new user account and return an API token.')]
    #[BodyParam('password_confirmation', 'string', required: true, description: 'Must match the password field.', example: 'password')]
    #[ResponseFromApiResource(UserResource::class, User::class, status: Response::HTTP_CREATED, additional: ['token' => 'YOUR_AUTH_TOKEN'])]
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = DB::transaction(function () use ($request): array {
            $user = User::create($request->validated());

            return [$user, $user->createToken('auth')->plainTextToken];
        });

        event(new Registered($user));

        return (new UserResource($user))
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
