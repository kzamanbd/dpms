<?php

namespace App\Services;

/**
 * Best-effort vendor name from a MAC address OUI (first 3 octets).
 *
 * Backed by a curated subset of common AV, networking, and computer vendors —
 * not the full IEEE registry — so an unknown prefix simply returns null and the
 * caller falls back to another naming source. Randomized (locally administered)
 * MACs, common on modern phones, carry no real vendor and return null.
 */
class MacVendorLookup
{
    /**
     * OUI prefix (upper-case, no separators) → vendor label.
     *
     * @var array<string, string>
     */
    private const OUI = [
        // Projector / AV manufacturers
        '0000F0' => 'Samsung',
        '001CB3' => 'Apple',
        '000048' => 'Seiko Epson',
        '0026AB' => 'Seiko Epson',
        '04A91A' => 'Seiko Epson',
        'A4EE57' => 'Seiko Epson',
        '00000E' => 'Fujitsu',
        '0080F0' => 'Panasonic',
        '002419' => 'Panasonic',
        '08EDB9' => 'Panasonic',
        '0014B9' => 'NEC',
        '00164E' => 'NEC',
        '000B97' => 'Sony',
        '001A80' => 'Sony',
        '0024BE' => 'Sony',
        '3C0771' => 'Sony',
        '008045' => 'BenQ',
        '001E0B' => 'Hewlett Packard',
        '3CD92B' => 'Hewlett Packard',
        // Network / infrastructure
        '001018' => 'Broadcom',
        '0050BA' => 'D-Link',
        '1CBDB9' => 'D-Link',
        '000C43' => 'Ralink',
        '50C7BF' => 'TP-Link',
        'A42BB0' => 'TP-Link',
        'EC086B' => 'TP-Link',
        '001575' => 'Ubiquiti',
        '24A43C' => 'Ubiquiti',
        '802AA8' => 'Ubiquiti',
        '00000C' => 'Cisco',
        '00010F' => 'Cisco',
        'F09FC2' => 'Ubiquiti',
        // Computer / mobile manufacturers
        '000D93' => 'Apple',
        '0017F2' => 'Apple',
        'A4831E' => 'Apple',
        'AC1F74' => 'Apple',
        'F0189E' => 'Apple',
        'B827EB' => 'Raspberry Pi',
        'DCA632' => 'Raspberry Pi',
        'E45F01' => 'Raspberry Pi',
        '000BDB' => 'Dell',
        '00188B' => 'Dell',
        '180373' => 'Dell',
        'D4BED9' => 'Dell',
        '0021CC' => 'Lenovo',
        '00059A' => 'Lenovo',
        '00037A' => 'Asustek',
        '2C56DC' => 'Asustek',
        '001A92' => 'Asustek',
        '000C29' => 'VMware',
        '005056' => 'VMware',
        '080027' => 'VirtualBox',
        '52540A' => 'QEMU',
        '0003FF' => 'Microsoft',
        '0017FA' => 'Microsoft',
        '001124' => 'Microsoft',
    ];

    /**
     * Resolve the vendor for a MAC, or null when unknown or randomized.
     */
    public function lookup(?string $mac): ?string
    {
        if ($mac === null) {
            return null;
        }

        $hex = strtoupper((string) preg_replace('/[^0-9A-Fa-f]/', '', $mac));

        if (strlen($hex) < 6) {
            return null;
        }

        // The low bit of the first octet's second nibble marks a locally
        // administered (randomized) address — no real vendor to report.
        if ((hexdec(substr($hex, 0, 2)) & 0x02) === 0x02) {
            return null;
        }

        return self::OUI[substr($hex, 0, 6)] ?? null;
    }
}
