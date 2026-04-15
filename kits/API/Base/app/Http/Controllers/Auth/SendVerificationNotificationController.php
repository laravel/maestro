<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;

#[Group('Authentication')]
#[Authenticated]
class SendVerificationNotificationController extends Controller
{
    #[Endpoint('Resend Verification Email', 'Send a new email verification notification.')]
    #[ScribeResponse(['message' => 'Verification link sent.'], status: Response::HTTP_ACCEPTED, description: 'Verification link sent')]
    #[ScribeResponse(['message' => 'Already verified.'], status: Response::HTTP_OK, description: 'Email already verified')]
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => __('Already verified.')]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => __('Verification link sent.')], Response::HTTP_ACCEPTED);
    }
}
