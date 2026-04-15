<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use Illuminate\Http\JsonResponse;
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
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return response()->json(['message' => __('Password updated successfully.')]);
    }
}
