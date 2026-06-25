import { router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import DeviceController from '@/actions/App/Http/Controllers/DeviceController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Device } from '@/types';

type DeviceFormData = {
    name: string;
    type: string;
    ip: string;
    mac: string;
    vlan: string;
    monitor_port: string;
    pjlink_port: string;
    pjlink_password: string;
    wol_broadcast: string;
    wol_port: string;
};

const initialData = (device: Device | null): DeviceFormData =>
    device
        ? {
              name: device.name,
              type: device.type,
              ip: device.ip,
              mac: device.mac ?? '',
              vlan: device.vlan ?? '',
              monitor_port: device.monitor_port?.toString() ?? '',
              pjlink_port: device.pjlink_port.toString(),
              pjlink_password: device.pjlink_password ?? '',
              wol_broadcast: device.wol_broadcast ?? '',
              wol_port: device.wol_port.toString(),
          }
        : {
              name: '',
              type: 'projector',
              ip: '',
              mac: '',
              vlan: '',
              monitor_port: '',
              pjlink_port: '4352',
              pjlink_password: '',
              wol_broadcast: '',
              wol_port: '9',
          };

export function DeviceFormModal({
    open,
    onOpenChange,
    device,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    device: Device | null;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                {/* Keyed so useForm re-initialises for each device / create. */}
                <DeviceForm
                    key={device?.id ?? 'new'}
                    device={device}
                    onDone={() => onOpenChange(false)}
                />
            </DialogContent>
        </Dialog>
    );
}

function DeviceForm({
    device,
    onDone,
}: {
    device: Device | null;
    onDone: () => void;
}) {
    const isEditing = device !== null;
    const { data, setData, post, put, processing, errors } =
        useForm<DeviceFormData>(initialData(device));
    const [confirmingDelete, setConfirmingDelete] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: onDone };

        if (isEditing) {
            put(DeviceController.update(device.id).url, options);
        } else {
            post(DeviceController.store().url, options);
        }
    };

    const handleDelete = () => {
        if (!device) {
            return;
        }

        if (!confirmingDelete) {
            setConfirmingDelete(true);

            return;
        }

        router.delete(DeviceController.destroy(device.id).url, {
            preserveScroll: true,
            onSuccess: onDone,
        });
    };

    return (
        <>
            <DialogHeader>
                <DialogTitle>
                    {isEditing ? `Edit ${device.name}` : 'Add device'}
                </DialogTitle>
                <DialogDescription>
                    {isEditing
                        ? 'Update this device’s network and control details.'
                        : 'Register a new device for monitoring and control.'}
                </DialogDescription>
            </DialogHeader>

            <form onSubmit={submit} className="space-y-5">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Auditorium Projector"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="type">Type</Label>
                        <Select
                            value={data.type}
                            onValueChange={(value) => setData('type', value)}
                        >
                            <SelectTrigger id="type">
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="projector">
                                    Projector
                                </SelectItem>
                                <SelectItem value="pc">PC</SelectItem>
                                <SelectItem value="other">Other</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="ip">IP address</Label>
                        <Input
                            id="ip"
                            value={data.ip}
                            onChange={(e) => setData('ip', e.target.value)}
                            placeholder="192.168.10.21"
                            required
                        />
                        <InputError message={errors.ip} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="vlan">VLAN</Label>
                        <Input
                            id="vlan"
                            value={data.vlan}
                            onChange={(e) => setData('vlan', e.target.value)}
                            placeholder="10"
                        />
                        <InputError message={errors.vlan} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="mac">MAC address</Label>
                        <Input
                            id="mac"
                            value={data.mac}
                            onChange={(e) => setData('mac', e.target.value)}
                            placeholder="1C:1B:0D:11:22:33"
                        />
                        <InputError message={errors.mac} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="monitor_port">
                            Monitor port (TCP fallback)
                        </Label>
                        <Input
                            id="monitor_port"
                            type="number"
                            value={data.monitor_port}
                            onChange={(e) =>
                                setData('monitor_port', e.target.value)
                            }
                            placeholder="3389"
                        />
                        <InputError message={errors.monitor_port} />
                    </div>
                </div>

                {data.type === 'projector' && (
                    <div className="grid gap-4 rounded-lg border border-border p-4 sm:grid-cols-2">
                        <p className="text-sm font-medium text-muted-foreground sm:col-span-2">
                            PJLink (projectors)
                        </p>
                        <div className="grid gap-2">
                            <Label htmlFor="pjlink_port">PJLink port</Label>
                            <Input
                                id="pjlink_port"
                                type="number"
                                value={data.pjlink_port}
                                onChange={(e) =>
                                    setData('pjlink_port', e.target.value)
                                }
                                required
                            />
                            <InputError message={errors.pjlink_port} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="pjlink_password">
                                PJLink password
                            </Label>
                            <Input
                                id="pjlink_password"
                                value={data.pjlink_password}
                                onChange={(e) =>
                                    setData('pjlink_password', e.target.value)
                                }
                                placeholder="(none)"
                            />
                            <InputError message={errors.pjlink_password} />
                        </div>
                    </div>
                )}

                <div className="grid gap-4 rounded-lg border border-border p-4 sm:grid-cols-2">
                    <p className="text-sm font-medium text-muted-foreground sm:col-span-2">
                        Wake-on-LAN
                    </p>
                    <div className="grid gap-2">
                        <Label htmlFor="wol_broadcast">
                            Broadcast / relay (cross-VLAN)
                        </Label>
                        <Input
                            id="wol_broadcast"
                            value={data.wol_broadcast}
                            onChange={(e) =>
                                setData('wol_broadcast', e.target.value)
                            }
                            placeholder="192.168.20.255"
                        />
                        <InputError message={errors.wol_broadcast} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="wol_port">WoL port</Label>
                        <Input
                            id="wol_port"
                            type="number"
                            value={data.wol_port}
                            onChange={(e) =>
                                setData('wol_port', e.target.value)
                            }
                            required
                        />
                        <InputError message={errors.wol_port} />
                    </div>
                </div>

                <DialogFooter className="gap-2 sm:justify-between">
                    {isEditing ? (
                        <Button
                            type="button"
                            variant={
                                confirmingDelete ? 'destructive' : 'outline'
                            }
                            onClick={handleDelete}
                        >
                            <Trash2 />
                            {confirmingDelete ? 'Confirm delete' : 'Delete'}
                        </Button>
                    ) : (
                        <span />
                    )}
                    <div className="flex gap-2">
                        <Button type="button" variant="ghost" onClick={onDone}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {isEditing ? 'Save changes' : 'Add device'}
                        </Button>
                    </div>
                </DialogFooter>
            </form>
        </>
    );
}
