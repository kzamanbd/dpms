<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Seed the small, representative POC device set.
     *
     * Replace the IPs / MACs / VLANs with the real lab values before kickoff
     * (see DPMS POC PRD §3.3). This default set covers every hypothesis:
     * 2 PJLink projectors (H2), 2 same-VLAN WoL PCs (H3) and 1 cross-VLAN
     * WoL PC (H4).
     */
    public function run(): void
    {
        $devices = [
            [
                'name' => 'Auditorium Projector',
                'type' => 'projector',
                'ip' => '192.168.10.21',
                'mac' => null,
                'vlan' => '10',
                'monitor_port' => 4352,
                'pjlink_port' => 4352,
                'pjlink_password' => null,
            ],
            [
                'name' => 'Conference Room A Projector',
                'type' => 'projector',
                'ip' => '192.168.10.22',
                'mac' => null,
                'vlan' => '10',
                'monitor_port' => 4352,
                'pjlink_port' => 4352,
                'pjlink_password' => 'pjlink',
            ],
            [
                'name' => 'Front Desk PC',
                'type' => 'pc',
                'ip' => '192.168.10.51',
                'mac' => '1C:1B:0D:11:22:33',
                'vlan' => '10',
                'monitor_port' => 3389,
            ],
            [
                'name' => 'Lab PC 1',
                'type' => 'pc',
                'ip' => '192.168.10.52',
                'mac' => '1C:1B:0D:44:55:66',
                'vlan' => '10',
                'monitor_port' => 3389,
            ],
            [
                'name' => 'Remote Office PC (cross-VLAN)',
                'type' => 'pc',
                'ip' => '192.168.20.50',
                'mac' => '1C:1B:0D:77:88:99',
                'vlan' => '20',
                'monitor_port' => 3389,
                // Cross-VLAN wake target: directed broadcast for VLAN 20.
                // Requires `ip directed-broadcast` on the switch SVI (H4).
                'wol_broadcast' => '192.168.20.255',
            ],
        ];

        foreach ($devices as $device) {
            Device::updateOrCreate(
                ['ip' => $device['ip']],
                $device,
            );
        }
    }
}
