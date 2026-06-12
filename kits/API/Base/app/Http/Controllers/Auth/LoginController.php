<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

#[Group('Authentication')]
class LoginController extends Controller
{
    private const string DUMMY_PASSWORD_HASH = '$2y$12$OoT8jJofGjVA6q9C3/FvNeplQTQg0JAeDakKw2oy4vBvK/eAEksCW';

    #[Endpoint(title: 'Login', description: 'Authenticate a user and return an API token.')]
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', (string) $request->string('email'))
            ->first();
        $password = (string) $request->string('password');

        if (! $user) {
            Hash::check($password, self::DUMMY_PASSWORD_HASH);

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken($request->string('device_name', 'auth'))->plainTextToken;

        return response()->json(['token' => $token]);
    }
}
