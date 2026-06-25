<?php

namespace App\Services;

use App\Models\Device;

/**
 * Minimal PJLink Class 1 client (TCP, default port 4352).
 *
 * Implements the POC command set: power on/off, power status, lamp hours,
 * error status and temperature status. The protocol terminates every line
 * with a carriage return (0x0D) and, when the projector requires it, expects
 * an MD5 authentication digest prefixed to the command body.
 *
 * @see https://pjlink.jbmia.or.jp/english/ — PJLink Class 1 specification
 */
class PjlinkService
{
    public function __construct(
        private readonly int $timeout = 5,
    ) {}

    public function powerOn(Device $device): PjlinkResult
    {
        return $this->command($device, 'POWR 1', fn (string $r) => $this->parseSetResponse($r, ['power' => 'on']));
    }

    public function powerOff(Device $device): PjlinkResult
    {
        return $this->command($device, 'POWR 0', fn (string $r) => $this->parseSetResponse($r, ['power' => 'off']));
    }

    public function getPowerStatus(Device $device): PjlinkResult
    {
        return $this->command($device, 'POWR ?', fn (string $r) => $this->parsePowerStatus($r));
    }

    public function getLampHours(Device $device): PjlinkResult
    {
        return $this->command($device, 'LAMP ?', fn (string $r) => $this->parseLamp($r));
    }

    public function getErrors(Device $device): PjlinkResult
    {
        return $this->command($device, 'ERST ?', fn (string $r) => $this->parseErrors($r));
    }

    /**
     * PJLink Class 1 exposes temperature only as a fault flag inside the error
     * status (ERST) response — there is no numeric temperature query. We surface
     * that flag here; numeric temperature would require a Class 2 / vendor command.
     */
    public function getTemperature(Device $device): PjlinkResult
    {
        return $this->command($device, 'ERST ?', fn (string $r) => $this->parseTemperature($r));
    }

    /**
     * The projector's configured name (PJLink Class 2 `NAME`).
     */
    public function getName(Device $device): PjlinkResult
    {
        return $this->command($device, 'NAME ?', fn (string $r) => PjlinkResult::ok(['name' => $this->responsePayload($r, 'NAME') ?? ''], $r));
    }

    /**
     * Manufacturer + product name (PJLink Class 1 `INF1` + `INF2`), e.g.
     * "EPSON EB-2250U".
     */
    public function getProductName(Device $device): PjlinkResult
    {
        $maker = $this->command($device, 'INF1 ?', fn (string $r) => PjlinkResult::ok(['manufacturer' => $this->responsePayload($r, 'INF1') ?? ''], $r));
        $product = $this->command($device, 'INF2 ?', fn (string $r) => PjlinkResult::ok(['product' => $this->responsePayload($r, 'INF2') ?? ''], $r));

        $label = trim(($maker->value['manufacturer'] ?? '').' '.($product->value['product'] ?? ''));

        return $label === '' ? PjlinkResult::fail('Product info unavailable.') : PjlinkResult::ok(['product_name' => $label]);
    }

    /**
     * Best-effort human name for device discovery: the configured projector
     * name, else manufacturer + model. Null when neither is available (no auth,
     * unsupported command, or unreachable).
     */
    public function resolveDisplayName(Device $device): ?string
    {
        $name = $this->getName($device);
        if ($name->success && ($name->value['name'] ?? '') !== '') {
            return $name->value['name'];
        }

        $product = $this->getProductName($device);

        return $product->success ? $product->value['product_name'] : null;
    }

    /**
     * Run a full command query and pass the parsed body to $parser.
     */
    private function command(Device $device, string $body, callable $parser): PjlinkResult
    {
        $raw = $this->transact($device, $body);

        if ($raw instanceof PjlinkResult) {
            return $raw; // transport-level failure
        }

        $error = $this->protocolError($raw);

        if ($error !== null) {
            return PjlinkResult::fail($error, $raw);
        }

        return $parser($raw);
    }

    /**
     * Open the socket, perform the auth handshake and exchange one command.
     *
     * Returns the raw response line, or a PjlinkResult on transport failure.
     * Marked protected so feature tests can stub the network layer.
     */
    protected function transact(Device $device, string $body): string|PjlinkResult
    {
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($device->ip, $device->pjlink_port, $errno, $errstr, $this->timeout);

        if ($socket === false) {
            return PjlinkResult::fail("Connection to {$device->ip}:{$device->pjlink_port} failed: {$errstr}");
        }

        stream_set_timeout($socket, $this->timeout);

        try {
            $greeting = stream_get_line($socket, 64, "\r");
            $handshake = $this->parseGreeting((string) $greeting, $device->pjlink_password);

            if ($handshake['error'] !== null) {
                return PjlinkResult::fail($handshake['error'], (string) $greeting);
            }

            fwrite($socket, $handshake['prefix']."%1{$body}\r");

            $response = stream_get_line($socket, 256, "\r");

            if ($response === false || $response === '') {
                return PjlinkResult::fail('No response from projector (timeout).');
            }

            return $response;
        } finally {
            fclose($socket);
        }
    }

    /**
     * Parse the PJLink greeting and compute the auth prefix for commands.
     *
     * @return array{prefix: string, error: string|null}
     */
    public function parseGreeting(string $greeting, ?string $password): array
    {
        $greeting = trim($greeting);
        $parts = preg_split('/\s+/', $greeting) ?: [];

        if (($parts[0] ?? '') !== 'PJLINK') {
            return ['prefix' => '', 'error' => "Unexpected PJLink greeting: {$greeting}"];
        }

        // "PJLINK 0" — no authentication required.
        if (($parts[1] ?? '') === '0') {
            return ['prefix' => '', 'error' => null];
        }

        // "PJLINK 1 <seed>" — authentication required.
        if (($parts[1] ?? '') === '1') {
            $seed = $parts[2] ?? '';

            if ($seed === '') {
                return ['prefix' => '', 'error' => 'PJLink auth requested but no seed was provided.'];
            }

            if ($password === null || $password === '') {
                return ['prefix' => '', 'error' => 'Projector requires a PJLink password but none is configured.'];
            }

            return ['prefix' => $this->digest($seed, $password), 'error' => null];
        }

        // "PJLINK ERRA" — authentication already failed.
        if (($parts[1] ?? '') === 'ERRA') {
            return ['prefix' => '', 'error' => 'PJLink authentication failed (bad password).'];
        }

        return ['prefix' => '', 'error' => "Unexpected PJLink greeting: {$greeting}"];
    }

    /**
     * MD5 of seed + password, as required by the PJLink auth handshake.
     */
    public function digest(string $seed, string $password): string
    {
        return md5($seed.$password);
    }

    /**
     * Map a PJLink error token in a response to a message, or null when clean.
     */
    private function protocolError(string $raw): ?string
    {
        $raw = trim($raw);

        if (str_contains($raw, 'ERRA')) {
            return 'PJLink authentication failed (bad password).';
        }

        return match (true) {
            str_ends_with($raw, '=ERR1') => 'Undefined command (ERR1).',
            str_ends_with($raw, '=ERR2') => 'Parameter out of range (ERR2).',
            str_ends_with($raw, '=ERR3') => 'Unavailable time — projector busy, e.g. warming/cooling (ERR3).',
            str_ends_with($raw, '=ERR4') => 'Projector/display failure (ERR4).',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function parseSetResponse(string $raw, array $value): PjlinkResult
    {
        if (str_contains(strtoupper($raw), '=OK')) {
            return PjlinkResult::ok($value, $raw);
        }

        return PjlinkResult::fail("Unexpected response: {$raw}", $raw);
    }

    private function parsePowerStatus(string $raw): PjlinkResult
    {
        $code = $this->responsePayload($raw, 'POWR');

        $status = match ($code) {
            '0' => 'off',
            '1' => 'on',
            '2' => 'cooling',
            '3' => 'warming',
            default => null,
        };

        if ($status === null) {
            return PjlinkResult::fail("Unparseable power status: {$raw}", $raw);
        }

        return PjlinkResult::ok(['power' => $status], $raw);
    }

    private function parseLamp(string $raw): PjlinkResult
    {
        $payload = $this->responsePayload($raw, 'LAMP');

        if ($payload === null) {
            return PjlinkResult::fail("Unparseable lamp response: {$raw}", $raw);
        }

        // "<hours> <on|off>[ <hours> <on|off> ...]" — one pair per lamp.
        $tokens = preg_split('/\s+/', trim($payload)) ?: [];
        $lamps = [];

        for ($i = 0; $i + 1 < count($tokens); $i += 2) {
            if (! is_numeric($tokens[$i])) {
                continue;
            }

            $lamps[] = [
                'hours' => (int) $tokens[$i],
                'on' => $tokens[$i + 1] === '1',
            ];
        }

        if ($lamps === []) {
            return PjlinkResult::fail("No lamp data in response: {$raw}", $raw);
        }

        return PjlinkResult::ok([
            'lamps' => $lamps,
            'hours' => $lamps[0]['hours'],
        ], $raw);
    }

    private function parseErrors(string $raw): PjlinkResult
    {
        $flags = $this->errorFlags($raw);

        if ($flags === null) {
            return PjlinkResult::fail("Unparseable error status: {$raw}", $raw);
        }

        return PjlinkResult::ok(['errors' => $flags], $raw);
    }

    private function parseTemperature(string $raw): PjlinkResult
    {
        $flags = $this->errorFlags($raw);

        if ($flags === null || ! isset($flags['temperature'])) {
            return PjlinkResult::fail("Unparseable temperature status: {$raw}", $raw);
        }

        return PjlinkResult::ok(['temperature' => $flags['temperature']], $raw);
    }

    /**
     * Decode the 6-character ERST payload into named severities.
     *
     * @return array<string, string>|null
     */
    private function errorFlags(string $raw): ?array
    {
        $payload = $this->responsePayload($raw, 'ERST');

        if ($payload === null || ! preg_match('/^[0-2]{6}$/', $payload)) {
            return null;
        }

        $names = ['fan', 'lamp', 'temperature', 'cover', 'filter', 'other'];
        $severities = ['0' => 'normal', '1' => 'warning', '2' => 'error'];

        $flags = [];

        foreach ($names as $index => $name) {
            $flags[$name] = $severities[$payload[$index]];
        }

        return $flags;
    }

    /**
     * Extract the payload after "%1<CMD>=" from a response line.
     */
    private function responsePayload(string $raw, string $command): ?string
    {
        if (! preg_match('/%1'.$command.'=(.*)$/i', trim($raw), $matches)) {
            return null;
        }

        return trim($matches[1]);
    }
}
