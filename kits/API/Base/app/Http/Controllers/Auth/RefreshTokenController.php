<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;

#[Group('Authentication')]
#[Authenticated]
class RefreshTokenController extends Controller
{
    #[Endpoint('Refresh Token', 'Revoke the current API token and issue a new one.')]
    #[Response(['token' => 'YOUR_NEW_AUTH_TOKEN'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $current = $user->currentAccessToken();
        $name = $current->name;
        $abilities = $current->abilities ?? ['*'];

        $token = DB::transaction(function () use ($user, $name, $abilities): string {
            $user->currentAccessToken()->delete();

            return $user->createToken($name, $abilities)->plainTextToken;
        });

        return response()->json(['token' => $token]);
    }
}
