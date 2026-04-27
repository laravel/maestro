<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Dedoc\Scramble\Attributes\BodyParameter;
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
    #[BodyParameter('password_confirmation', description: 'Must match the password field.', required: true, type: 'string')]
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = DB::transaction(function () use ($request): array {
            $user = User::create($request->validated());

            return [$user, $user->createToken('auth')->plainTextToken];
        });

        event(new Registered($user));

        return response()->json([
            'user_id' => (int) $user->id,
            'token' => $token,
        ], Response::HTTP_CREATED);
    }
}
