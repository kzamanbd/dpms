<?php

namespace App\Http\Controllers;

use App\Services\ReachabilityService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ToolsController extends Controller
{
    /**
     * System-requirements diagnostics: verify the host can run DPMS and reach
     * devices over ICMP / PJLink (TCP 4352) / Wake-on-LAN (UDP).
     */
    public function index(ReachabilityService $reachability): Response
    {
        $checks = [
            ...$this->runtimeChecks(),
            ...$this->extensionChecks(),
            ...$this->networkChecks($reachability),
            ...$this->appChecks(),
        ];

        return Inertia::render('tools/index', [
            'checks' => $checks,
            'summary' => [
                'ok' => count(array_filter($checks, fn ($c) => $c['status'] === 'ok')),
                'warn' => count(array_filter($checks, fn ($c) => $c['status'] === 'warn')),
                'error' => count(array_filter($checks, fn ($c) => $c['status'] === 'error')),
            ],
        ]);
    }

    /**
     * @return list<array{group: string, label: string, status: string, required: bool, value: string, hint: string}>
     */
    private function runtimeChecks(): array
    {
        $okPhp = version_compare(PHP_VERSION, '8.3.0', '>=');

        return [
            $this->check('PHP', 'PHP version', $okPhp ? 'ok' : 'error', true, PHP_VERSION, 'Requires PHP 8.3 or newer.'),
        ];
    }

    /**
     * @return list<array{group: string, label: string, status: string, required: bool, value: string, hint: string}>
     */
    private function extensionChecks(): array
    {
        $required = [
            'sockets' => 'Wake-on-LAN magic packets (UDP).',
            'pdo_sqlite' => 'Default database driver.',
            'mbstring' => 'String handling (Laravel).',
            'openssl' => 'Encryption (Laravel).',
            'ctype' => 'Input validation (Laravel).',
            'json' => 'JSON encoding (Laravel).',
            'tokenizer' => 'Framework internals (Laravel).',
            'fileinfo' => 'File handling (Laravel).',
            'curl' => 'Outbound HTTP.',
        ];

        $checks = [];

        foreach ($required as $ext => $hint) {
            $loaded = extension_loaded($ext);
            $checks[] = $this->check(
                'PHP extensions',
                "ext-{$ext}",
                $loaded ? 'ok' : 'error',
                true,
                $loaded ? 'loaded' : 'missing',
                $hint,
            );
        }

        return $checks;
    }

    /**
     * @return list<array{group: string, label: string, status: string, required: bool, value: string, hint: string}>
     */
    private function networkChecks(ReachabilityService $reachability): array
    {
        $exec = $this->functionAvailable('exec');
        $fsock = $this->functionAvailable('fsockopen');
        $udp = $this->functionAvailable('socket_create') && extension_loaded('sockets');

        // UDP datagram socket (Wake-on-LAN transport).
        $udpOk = false;
        if ($udp) {
            $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if ($socket !== false) {
                $udpOk = true;
                socket_close($socket);
            }
        }

        // Loopback ping proves the `ping` binary + exec() work for ICMP monitoring.
        // Try absolute paths too: PHP-FPM often has a minimal PATH that omits
        // /sbin, even though the CLI queue worker (which runs the real checks) does
        // not.
        $pingOk = false;
        if ($exec) {
            $base = $reachability->buildPingCommand('127.0.0.1');
            foreach (['ping', '/sbin/ping', '/bin/ping', '/usr/bin/ping'] as $binary) {
                $output = [];
                $code = 1;
                @exec(preg_replace('/^ping/', $binary, $base, 1).' 2>&1', $output, $code);
                if ($code === 0) {
                    $pingOk = true;
                    break;
                }
            }
        }

        return [
            $this->check('Network', 'exec() enabled', $exec ? 'ok' : 'error', true, $exec ? 'available' : 'disabled', 'Needed to run ICMP ping.'),
            $this->check('Network', 'ICMP ping (loopback)', $pingOk ? 'ok' : ($exec ? 'error' : 'warn'), true, $pingOk ? 'reachable' : 'failed', 'Reachability monitoring (H1). Devices also need a monitor_port for TCP fallback.'),
            $this->check('Network', 'fsockopen() (TCP)', $fsock ? 'ok' : 'error', true, $fsock ? 'available' : 'disabled', 'PJLink (TCP 4352) + TCP reachability fallback.'),
            $this->check('Network', 'UDP datagram socket', $udpOk ? 'ok' : 'error', true, $udpOk ? 'created' : 'unavailable', 'Wake-on-LAN magic packets (H3/H4).'),
        ];
    }

    /**
     * @return list<array{group: string, label: string, status: string, required: bool, value: string, hint: string}>
     */
    private function appChecks(): array
    {
        $dbOk = false;
        $dbValue = 'unreachable';
        try {
            DB::connection()->getPdo();
            $dbOk = true;
            $dbValue = (string) config('database.default');
        } catch (Throwable $e) {
            $dbValue = $e->getMessage();
        }

        $storageWritable = is_writable(storage_path());
        $cacheWritable = is_writable(base_path('bootstrap/cache'));
        $keySet = ! empty(config('app.key'));

        return [
            $this->check('Application', 'Database connection', $dbOk ? 'ok' : 'error', true, $dbValue, 'Stores devices and action logs.'),
            $this->check('Application', 'storage/ writable', $storageWritable ? 'ok' : 'error', true, $storageWritable ? 'writable' : 'read-only', 'Logs, cache, sessions.'),
            $this->check('Application', 'bootstrap/cache writable', $cacheWritable ? 'ok' : 'error', true, $cacheWritable ? 'writable' : 'read-only', 'Framework bootstrap cache.'),
            $this->check('Application', 'APP_KEY set', $keySet ? 'ok' : 'error', true, $keySet ? 'set' : 'missing', 'Run php artisan key:generate.'),
            $this->check('Application', 'Queue driver', 'ok', false, (string) config('queue.default'), 'Reachability checks run as queued jobs — a worker must be running.'),
            $this->check('Application', 'Scheduler', 'warn', false, 'verify externally', 'Run php artisan schedule:work (or a cron entry) for the 30s monitor loop.'),
        ];
    }

    private function functionAvailable(string $function): bool
    {
        if (! function_exists($function)) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array($function, $disabled, true);
    }

    /**
     * @return array{group: string, label: string, status: string, required: bool, value: string, hint: string}
     */
    private function check(string $group, string $label, string $status, bool $required, string $value, string $hint): array
    {
        return compact('group', 'label', 'status', 'required', 'value', 'hint');
    }
}
