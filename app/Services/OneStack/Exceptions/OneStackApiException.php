<?php

namespace App\Services\OneStack\Exceptions;

use RuntimeException;

class OneStackApiException extends RuntimeException
{
    public static function missingApiKey(): self
    {
        return new self('OneStack API key is not configured. Please set ONESTACK_API_KEY in your environment.');
    }

    public static function requestFailed(string $message, int $statusCode = 0, ?\Throwable $previous = null): self
    {
        return new self("OneStack API request failed (HTTP {$statusCode}): {$message}", $statusCode, $previous);
    }

    public static function invalidResponse(string $message): self
    {
        return new self("OneStack API returned an invalid response: {$message}");
    }
}

