<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;

#[Group('Authentication')]
class RegisterController extends Controller
{
    #[Endpoint('Register', 'Create a new user account and return an API token.')]
    #[BodyParam('password_confirmation', 'string', required: true, description: 'Must match the password field.', example: 'password')]
    #[ScribeResponse(['user_id' => 1, 'token' => 'YOUR_AUTH_TOKEN'], status: Response::HTTP_CREATED)]
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        event(new Registered($user));

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user_id' => $user->id,
            'token' => $token,
        ], Response::HTTP_CREATED);
    }
}
