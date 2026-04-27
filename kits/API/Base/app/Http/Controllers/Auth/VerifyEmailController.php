<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[Group('Authentication')]
class VerifyEmailController extends Controller
{
    #[Endpoint(title: 'Verify Email', description: "Mark a user's email as verified using the signed verification link.")]
    #[ScrambleResponse(status: Response::HTTP_OK, description: 'Email verified successfully.')]
    public function __invoke(Request $request): JsonResponse
    {
        $user = User::query()->find($request->route('id'));

        abort_if($user === null, Response::HTTP_FORBIDDEN);

        abort_unless(
            hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())),
            Response::HTTP_FORBIDDEN,
        );

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => (string) __('Email already verified.')]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => (string) __('Email verified successfully.')]);
    }
}
