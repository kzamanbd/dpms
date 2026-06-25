<?php

namespace App\Services;

use App\Models\Device;
use InvalidArgumentException;

/**
 * Sends Wake-on-LAN magic packets over UDP.
 *
 * A magic packet is 6 bytes of 0xFF followed by the target MAC repeated 16
 * times, broadcast to the device's subnet. The $broadcast argument is the
 * cross-VLAN strategy hook: a same-subnet wake uses the limited broadcast
 * (255.255.255.255), while an off-subnet wake targets a directed broadcast
 * (e.g. 192.168.20.255) or a per-subnet relay host.
 */
class WakeOnLanService
{
    /**
     * Wake a device, returning the outcome for the action log.
     *
     * @return array{success: bool, broadcast: string, error: string|null}
     */
    public function wake(Device $device): array
    {
        if ($device->mac === null || $device->mac === '') {
            return ['success' => false, 'broadcast' => '', 'error' => 'Device has no MAC address.'];
        }

        $broadcast = $device->wol_broadcast ?: '255.255.255.255';

        try {
            $packet = $this->buildMagicPacket($device->mac);
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'broadcast' => $broadcast, 'error' => $e->getMessage()];
        }

        $sent = $this->sendPacket($packet, $broadcast, $device->wol_port);

        return [
            'success' => $sent,
            'broadcast' => $broadcast,
            'error' => $sent ? null : 'Failed to send magic packet.',
        ];
    }

    /**
     * Build the 102-byte magic packet for a MAC address.
     */
    public function buildMagicPacket(string $mac): string
    {
        $hex = strtoupper((string) preg_replace('/[^0-9A-Fa-f]/', '', $mac));

        if (strlen($hex) !== 12) {
            throw new InvalidArgumentException("Invalid MAC address: {$mac}");
        }

        $bytes = pack('H*', $hex);

        return str_repeat("\xFF", 6).str_repeat($bytes, 16);
    }

    /**
     * Transmit a UDP broadcast packet. Protected so tests can stub the socket.
     */
    protected function sendPacket(string $packet, string $broadcast, int $port): bool
    {
        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($socket === false) {
            return false;
        }

        try {
            socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);

            $sent = @socket_sendto($socket, $packet, strlen($packet), 0, $broadcast, $port);

            return $sent !== false;
        } finally {
            socket_close($socket);
        }
    }
}
