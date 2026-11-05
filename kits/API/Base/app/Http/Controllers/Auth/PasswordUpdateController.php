<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use App\Http\Responses\MessageResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Support\Facades\DB;

#[Group('Password')]
class PasswordUpdateController extends Controller
{
    #[Endpoint(title: 'Update Password', description: "Update the authenticated user's password.")]
    public function __invoke(PasswordUpdateRequest $request): MessageResponse
    {
        DB::transaction(function () use ($request): void {
            $user = $request->user();

            $user->update([
                'password' => $request->validated('password'),
            ]);

            $user->tokens()->delete();
        });

        return new MessageResponse('Password updated successfully.');
    }
}
