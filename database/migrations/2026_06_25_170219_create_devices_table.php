<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('other');
            $table->string('ip');
            $table->string('mac')->nullable();
            $table->string('vlan')->nullable();
            $table->string('status')->default('unknown');
            $table->timestamp('last_seen')->nullable();

            // Reachability: optional TCP port used as an ICMP fallback.
            $table->unsignedInteger('monitor_port')->nullable();

            // PJLink (projectors).
            $table->unsignedInteger('pjlink_port')->default(4352);
            $table->string('pjlink_password')->nullable();

            // Wake-on-LAN. wol_broadcast carries the cross-VLAN strategy
            // (directed broadcast or relay host) when the device is off-subnet.
            $table->string('wol_broadcast')->nullable();
            $table->unsignedInteger('wol_port')->default(9);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
