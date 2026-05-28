<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\MessageResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[Group('Authentication')]
class SendVerificationNotificationController extends Controller
{
    #[Endpoint(title: 'Resend Verification Email', description: 'Send a new email verification notification.')]
    #[ScrambleResponse(Response::HTTP_OK, 'Email already verified.', type: 'array{message: string}')]
    #[ScrambleResponse(Response::HTTP_ACCEPTED, 'Verification link sent.', type: 'array{message: string}')]
    public function __invoke(Request $request): MessageResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return new MessageResponse('Already verified.');
        }

        $request->user()->sendEmailVerificationNotification();

        return new MessageResponse('Verification link sent.', Response::HTTP_ACCEPTED);
    }
}
