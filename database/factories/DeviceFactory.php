<?php

namespace Database\Factories;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement(DeviceType::cases()),
            'ip' => fake()->localIpv4(),
            'mac' => fake()->macAddress(),
            'vlan' => (string) fake()->numberBetween(10, 99),
            'status' => DeviceStatus::Unknown,
            'last_seen' => null,
            'monitor_port' => null,
            'pjlink_port' => 4352,
            'pjlink_password' => null,
            'wol_broadcast' => null,
            'wol_port' => 9,
        ];
    }

    public function projector(): static
    {
        return $this->state(fn (): array => [
            'type' => DeviceType::Projector,
            'mac' => null,
            'monitor_port' => 4352,
            'pjlink_port' => 4352,
        ]);
    }

    public function pc(): static
    {
        return $this->state(fn (): array => [
            'type' => DeviceType::Pc,
            'monitor_port' => 3389,
        ]);
    }

    public function online(): static
    {
        return $this->state(fn (): array => [
            'status' => DeviceStatus::Online,
            'last_seen' => now(),
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn (): array => [
            'status' => DeviceStatus::Offline,
        ]);
    }
}
