<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Password')]
class PasswordUpdateController extends Controller
{
    #[Endpoint(title: 'Update Password', description: "Update the authenticated user's password.")]
    #[BodyParameter('password_confirmation', description: 'Must match the password field.', required: true, type: 'string')]
    public function __invoke(PasswordUpdateRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return response()->json(['message' => (string) __('Password updated successfully.')]);
    }
}
