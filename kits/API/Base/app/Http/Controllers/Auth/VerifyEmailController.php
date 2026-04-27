<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;

#[Group('Authentication')]
class VerifyEmailController extends Controller
{
    #[Endpoint('Verify Email', "Mark a user's email as verified using the signed verification link.")]
    #[ScribeResponse(['message' => 'Email verified successfully.'], description: 'Email verified')]
    #[ScribeResponse(['message' => 'Email already verified.'], description: 'Email was already verified')]
    public function __invoke(Request $request): JsonResponse
    {
        $user = User::query()->find($request->route('id'));

        abort_if($user === null, Response::HTTP_FORBIDDEN);

        abort_unless(
            hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())),
            Response::HTTP_FORBIDDEN,
        );

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => __('Email already verified.')]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => __('Email verified successfully.')]);
    }
}
