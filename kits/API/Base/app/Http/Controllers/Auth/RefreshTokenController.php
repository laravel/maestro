<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[Group('Authentication')]
class RefreshTokenController extends Controller
{
    #[Endpoint(title: 'Refresh Token', description: 'Revoke the current API token and issue a new one.')]
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
