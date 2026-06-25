<?php

use App\Models\Device;
use App\Services\ReachabilityService;

/**
 * Build a ReachabilityService with stubbed ICMP/TCP probes.
 */
function reachability(bool $icmp, bool $tcp): ReachabilityService
{
    return new class($icmp, $tcp) extends ReachabilityService
    {
        public function __construct(private readonly bool $icmp, private readonly bool $tcp)
        {
            parent::__construct();
        }

        protected function pingIcmp(string $ip): bool
        {
            return $this->icmp;
        }

        protected function checkTcp(string $ip, int $port): bool
        {
            return $this->tcp;
        }
    };
}

it('builds a single-echo ping command for the host', function () {
    $command = (new ReachabilityService)->buildPingCommand('192.168.10.21');

    expect($command)->toContain("'192.168.10.21'")
        ->and($command)->toStartWith('ping');
});

it('is reachable when ICMP succeeds', function () {
    $device = new Device(['ip' => '192.168.10.21']);

    expect(reachability(icmp: true, tcp: false)->isReachable($device))->toBeTrue();
});

it('falls back to TCP when ICMP is blocked', function () {
    $device = new Device(['ip' => '192.168.10.21', 'monitor_port' => 4352]);

    expect(reachability(icmp: false, tcp: true)->isReachable($device))->toBeTrue();
});

it('is unreachable when both probes fail', function () {
    $device = new Device(['ip' => '192.168.10.21', 'monitor_port' => 4352]);

    expect(reachability(icmp: false, tcp: false)->isReachable($device))->toBeFalse();
});

it('does not attempt a TCP fallback without a monitor port', function () {
    $device = new Device(['ip' => '192.168.10.21', 'monitor_port' => null]);

    expect(reachability(icmp: false, tcp: true)->isReachable($device))->toBeFalse();
});
