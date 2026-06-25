<?php

namespace App\Services;

/**
 * Outcome of a single PJLink transaction.
 */
class PjlinkResult
{
    /**
     * @param  array<string, mixed>  $value  Parsed, human-meaningful payload (e.g. ['power' => 'on']).
     */
    public function __construct(
        public readonly bool $success,
        public readonly array $value = [],
        public readonly ?string $raw = null,
        public readonly ?string $error = null,
    ) {}

    /**
     * @param  array<string, mixed>  $value
     */
    public static function ok(array $value, ?string $raw = null): self
    {
        return new self(true, $value, $raw);
    }

    public static function fail(string $error, ?string $raw = null): self
    {
        return new self(false, [], $raw, $error);
    }

    /**
     * @return array{success: bool, value: array<string, mixed>, raw: string|null, error: string|null}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'value' => $this->value,
            'raw' => $this->raw,
            'error' => $this->error,
        ];
    }
}
