<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;

#[Group('Password')]
#[Authenticated]
class PasswordUpdateController extends Controller
{
    #[Endpoint('Update Password', "Update the authenticated user's password.")]
    #[BodyParam('password_confirmation', 'string', required: true, description: 'Must match the password field.', example: 'new-password')]
    #[Response(['message' => 'Password updated successfully.'])]
    public function __invoke(PasswordUpdateRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request): void {
            $user = $request->user();

            $user->update([
                'password' => $request->validated('password'),
            ]);

            $user->tokens()->delete();
        });

        return response()->json(['message' => __('Password updated successfully.')]);
    }
}
