<?php

namespace App\Services;

class AiRecallResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly ?string $answer,
        public readonly ?string $error,
    ) {}

    public static function success(string $answer): self
    {
        return new self(true, $answer, null);
    }

    public static function error(string $message): self
    {
        return new self(false, null, $message);
    }
}
