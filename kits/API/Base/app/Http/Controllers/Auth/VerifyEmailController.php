<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\MessageResponse;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[Group('Authentication')]
class VerifyEmailController extends Controller
{
    #[Endpoint(title: 'Verify Email', description: "Mark a user's email as verified using the signed verification link.")]
    #[ScrambleResponse(Response::HTTP_OK, 'Email verification result.', type: 'array{message: string}')]
    public function __invoke(Request $request): MessageResponse
    {
        $user = User::query()->find($request->route('id'));

        abort_if($user === null, Response::HTTP_FORBIDDEN);

        abort_unless(
            hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())),
            Response::HTTP_FORBIDDEN,
        );

        if ($user->hasVerifiedEmail()) {
            return new MessageResponse('Email already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return new MessageResponse('Email verified successfully.');
    }
}
