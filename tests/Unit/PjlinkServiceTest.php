<?php

use App\Models\Device;
use App\Services\PjlinkResult;
use App\Services\PjlinkService;

/**
 * Build a PjlinkService whose network layer returns a canned response line,
 * so we can test the auth handshake and response parsing without a projector.
 */
function pjlinkReturning(string|PjlinkResult $raw): PjlinkService
{
    return new class($raw) extends PjlinkService
    {
        public function __construct(private readonly string|PjlinkResult $raw)
        {
            parent::__construct();
        }

        protected function transact(Device $device, string $body): string|PjlinkResult
        {
            return $this->raw;
        }
    };
}

function projector(): Device
{
    return new Device(['ip' => '192.168.10.21', 'pjlink_port' => 4352]);
}

it('computes the MD5 auth digest from seed and password', function () {
    $service = new PjlinkService;

    expect($service->digest('498e4a67', 'JBMIAProjectorLink'))
        ->toBe(md5('498e4a67JBMIAProjectorLink'));
});

it('parses a no-auth greeting', function () {
    $result = (new PjlinkService)->parseGreeting('PJLINK 0', null);

    expect($result['prefix'])->toBe('')
        ->and($result['error'])->toBeNull();
});

it('parses an auth greeting into a command prefix', function () {
    $service = new PjlinkService;
    $result = $service->parseGreeting('PJLINK 1 498e4a67', 'secret');

    expect($result['error'])->toBeNull()
        ->and($result['prefix'])->toBe($service->digest('498e4a67', 'secret'));
});

it('fails an auth greeting when no password is configured', function () {
    $result = (new PjlinkService)->parseGreeting('PJLINK 1 498e4a67', null);

    expect($result['error'])->not->toBeNull();
});

it('reports power on status', function () {
    $result = pjlinkReturning('%1POWR=1')->getPowerStatus(projector());

    expect($result->success)->toBeTrue()
        ->and($result->value['power'])->toBe('on');
});

it('reports warming power status', function () {
    $result = pjlinkReturning('%1POWR=3')->getPowerStatus(projector());

    expect($result->value['power'])->toBe('warming');
});

it('confirms a power-on command', function () {
    $result = pjlinkReturning('%1POWR=OK')->powerOn(projector());

    expect($result->success)->toBeTrue()
        ->and($result->value['power'])->toBe('on');
});

it('parses lamp hours', function () {
    $result = pjlinkReturning('%1LAMP=8262 1')->getLampHours(projector());

    expect($result->success)->toBeTrue()
        ->and($result->value['hours'])->toBe(8262)
        ->and($result->value['lamps'][0]['on'])->toBeTrue();
});

it('parses a clean error status', function () {
    $result = pjlinkReturning('%1ERST=000000')->getErrors(projector());

    expect($result->success)->toBeTrue()
        ->and($result->value['errors']['fan'])->toBe('normal')
        ->and($result->value['errors']['temperature'])->toBe('normal');
});

it('parses a temperature fault from the error status', function () {
    // Positions: fan, lamp, temperature, cover, filter, other.
    $result = pjlinkReturning('%1ERST=002000')->getTemperature(projector());

    expect($result->success)->toBeTrue()
        ->and($result->value['temperature'])->toBe('error');
});

it('surfaces an ERR3 busy response as a failure', function () {
    $result = pjlinkReturning('%1POWR=ERR3')->powerOn(projector());

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('ERR3');
});

it('surfaces an authentication failure', function () {
    $result = pjlinkReturning('PJLINK ERRA')->getPowerStatus(projector());

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('authentication');
});
