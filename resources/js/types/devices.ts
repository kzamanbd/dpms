export type DeviceStatus = 'online' | 'offline' | 'unknown';

export type DeviceActionResult = {
    action: string;
    result: string;
    detail: string | null;
    created_at: string | null;
};

export type Device = {
    id: number;
    name: string;
    type: string;
    type_label: string;
    ip: string;
    mac: string | null;
    vlan: string | null;
    status: DeviceStatus;
    last_seen: string | null;
    supports_pjlink: boolean;
    can_wake: boolean;
    is_cross_vlan: boolean;
    last_action: DeviceActionResult | null;
    monitor_port: number | null;
    pjlink_port: number;
    pjlink_password: string | null;
    wol_broadcast: string | null;
    wol_port: number;
};

export type ScanCandidate = {
    ip: string;
    mac: string | null;
    vendor: string | null;
    type: string;
    name: string;
    supports_pjlink: boolean;
    existing: boolean;
};

export type ScanResult = {
    subnet: string | null;
    interface_ip: string | null;
    host_count: number;
    alive_count: number;
    candidates: ScanCandidate[];
    error: string | null;
};

export type ActivityLog = {
    id: number;
    device: string;
    action: string;
    result: string;
    detail: string | null;
    created_at: string | null;
};
