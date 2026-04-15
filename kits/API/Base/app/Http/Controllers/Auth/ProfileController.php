<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DeleteUserRequest;
use App\Http\Requests\Auth\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Profile')]
#[Authenticated]
class ProfileController extends Controller
{
    #[Endpoint('Current User', "Get the authenticated user's information.")]
    #[ResponseFromApiResource(UserResource::class, User::class)]
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    #[Endpoint('Update Profile', "Update the authenticated user's name and/or email.")]
    #[ResponseFromApiResource(UserResource::class, User::class)]
    public function update(ProfileUpdateRequest $request): UserResource
    {
        $user = $request->user();
        $user->fill($request->validated());

        if (! $user->isDirty('email') || ! $user instanceof MustVerifyEmail) {
            $user->save();

            return new UserResource($user);
        }

        $user->email_verified_at = null;
        $user->save();
        $user->sendEmailVerificationNotification();

        return new UserResource($user);
    }

    #[Endpoint('Delete Account', "Permanently delete the authenticated user's account and revoke all their tokens.")]
    #[ScribeResponse(status: Response::HTTP_NO_CONTENT, description: 'No Content')]
    public function destroy(DeleteUserRequest $request): Response
    {
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->noContent();
    }
}
