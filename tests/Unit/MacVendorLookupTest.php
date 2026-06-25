<?php

use App\Services\MacVendorLookup;

test('it resolves a known OUI prefix to a vendor', function () {
    expect(new MacVendorLookup)->lookup('00:0C:29:11:22:33')->toBe('VMware');
});

test('it ignores separators and case', function () {
    expect(new MacVendorLookup)->lookup('000c29-aabbcc')->toBe('VMware');
});

test('it returns null for a randomized (locally administered) MAC', function () {
    // 0xAA has the locally-administered bit set.
    expect(new MacVendorLookup)->lookup('AA:BB:CC:DD:EE:FF')->toBeNull();
});

test('it returns null for an unassigned prefix and for null', function () {
    // 01:00:00 is a globally-unique but unassigned OUI prefix.
    expect(new MacVendorLookup)->lookup('01:00:00:33:44:55')->toBeNull();
    expect(new MacVendorLookup)->lookup(null)->toBeNull();
});
