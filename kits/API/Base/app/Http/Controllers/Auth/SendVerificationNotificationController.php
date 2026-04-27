<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[Group('Authentication')]
class SendVerificationNotificationController extends Controller
{
    #[Endpoint(title: 'Resend Verification Email', description: 'Send a new email verification notification.')]
    #[ScrambleResponse(status: Response::HTTP_ACCEPTED, description: 'Verification link sent.')]
    #[ScrambleResponse(status: Response::HTTP_OK, description: 'Email already verified.')]
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => (string) __('Already verified.')]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => (string) __('Verification link sent.')], Response::HTTP_ACCEPTED);
    }
}
