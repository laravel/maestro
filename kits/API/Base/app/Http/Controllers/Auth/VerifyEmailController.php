<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;

#[Group('Authentication')]
#[Authenticated]
class VerifyEmailController extends Controller
{
    #[Endpoint('Verify Email', "Mark the authenticated user's email as verified.")]
    #[Response(['message' => 'Email verified successfully.'], description: 'Email verified')]
    #[Response(['message' => 'Email already verified.'], description: 'Email was already verified')]
    public function __invoke(EmailVerificationRequest $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => __('Email already verified.')]);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return response()->json(['message' => __('Email verified successfully.')]);
    }
}
