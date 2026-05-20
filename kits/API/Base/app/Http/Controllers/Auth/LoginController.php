<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;

#[Group('Authentication')]
class LoginController extends Controller
{
    private const string DUMMY_PASSWORD_HASH = '$2y$12$OoT8jJofGjVA6q9C3/FvNeplQTQg0JAeDakKw2oy4vBvK/eAEksCW';

    #[Endpoint('Login', 'Authenticate a user and return an API token.')]
    #[Response(['token' => 'YOUR_AUTH_TOKEN'])]
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();
        $passwordHash = $user?->password ?? self::DUMMY_PASSWORD_HASH;
        $passwordMatches = Hash::check($request->password, $passwordHash);

        if (! $user || ! $passwordMatches) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken($request->string('device_name', 'auth'))->plainTextToken;

        return response()->json(['token' => $token]);
    }
}
