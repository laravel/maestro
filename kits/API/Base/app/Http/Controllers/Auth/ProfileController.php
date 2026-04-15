<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
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
}
