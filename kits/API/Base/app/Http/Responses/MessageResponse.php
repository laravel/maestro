<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class MessageResponse implements Responsable
{
    /**
     * @param  array<string, string|array<int, string>>  $headers
     */
    public function __construct(
        private readonly string $message,
        private readonly int $status = Response::HTTP_OK,
        private readonly array $headers = [],
    ) {
        //
    }

    public function toResponse($request): JsonResponse
    {
        return response()->json(['message' => __($this->message)], $this->status, $this->headers);
    }
}
