import { RotateCcw } from 'lucide-react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useTheme } from '@/hooks/use-theme';
import type {
    FooterMode,
    MenuLayout,
    NavbarMode,
    ThemeMode,
    ThemeVariant,
} from '@/hooks/use-theme';
import { cn } from '@/lib/utils';

const COLORS: { value: ThemeVariant; swatch: string; label: string }[] = [
    { value: 'default', swatch: 'bg-indigo-500', label: 'Indigo' },
    { value: 'amber', swatch: 'bg-amber-500', label: 'Amber' },
    { value: 'rose', swatch: 'bg-rose-500', label: 'Rose' },
    { value: 'purple', swatch: 'bg-purple-500', label: 'Purple' },
    { value: 'sky', swatch: 'bg-sky-500', label: 'Sky' },
    { value: 'teal', swatch: 'bg-teal-500', label: 'Teal' },
];

function Section({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <div className="border-t border-border px-5 py-4">
            <h3 className="mb-3 text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                {title}
            </h3>
            {children}
        </div>
    );
}

function Segmented<T extends string>({
    options,
    value,
    onChange,
}: {
    options: { value: T; label: string }[];
    value: T;
    onChange: (value: T) => void;
}) {
    return (
        <div className="grid grid-cols-3 gap-2">
            {options.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    onClick={() => onChange(option.value)}
                    className={cn(
                        'rounded-md border px-2 py-1.5 text-xs font-medium capitalize transition-colors',
                        value === option.value
                            ? 'border-primary bg-primary/10 text-primary'
                            : 'border-border text-muted-foreground hover:bg-accent hover:text-foreground',
                    )}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}

export function TweaksPanel({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { settings, setSetting, setMenuLayout, resetTheme } = useTheme();

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="w-full gap-0 overflow-y-auto p-0 sm:max-w-sm"
            >
                <SheetHeader className="bg-linear-to-br from-primary/10 to-purple-500/10 px-5 py-4">
                    <SheetTitle>Theme customizer</SheetTitle>
                    <SheetDescription>
                        Preview the multi-theme design options.
                    </SheetDescription>
                </SheetHeader>

                <Section title="Color scheme">
                    <Segmented<ThemeMode>
                        options={[
                            { value: 'system', label: 'System' },
                            { value: 'light', label: 'Light' },
                            { value: 'dark', label: 'Dark' },
                        ]}
                        value={settings.theme}
                        onChange={(value) => setSetting('theme', value)}
                    />
                    <label className="mt-3 flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={settings.semiDark}
                            onChange={(e) =>
                                setSetting('semiDark', e.target.checked)
                            }
                            className="size-4 rounded border-border accent-primary"
                        />
                        Semi-dark sidebar
                    </label>
                </Section>

                <Section title="Primary color">
                    <div className="flex flex-wrap gap-3">
                        {COLORS.map((color) => (
                            <button
                                key={color.value}
                                type="button"
                                onClick={() =>
                                    setSetting('themeVariant', color.value)
                                }
                                aria-label={color.label}
                                title={color.label}
                                className={cn(
                                    'size-8 rounded-full ring-2 ring-offset-2 ring-offset-background transition-transform hover:scale-110',
                                    color.swatch,
                                    settings.themeVariant === color.value
                                        ? 'ring-foreground'
                                        : 'ring-transparent',
                                )}
                            />
                        ))}
                    </div>
                </Section>

                <Section title="Menu layout">
                    <Segmented<MenuLayout>
                        options={[
                            { value: 'vertical', label: 'Vertical' },
                            { value: 'collapsible', label: 'Collapsed' },
                            { value: 'horizontal', label: 'Horizontal' },
                        ]}
                        value={settings.menu}
                        onChange={setMenuLayout}
                    />
                </Section>

                <Section title="Direction">
                    <Segmented<'ltr' | 'rtl'>
                        options={[
                            { value: 'ltr', label: 'LTR' },
                            { value: 'rtl', label: 'RTL' },
                        ]}
                        value={settings.rtlClass}
                        onChange={(value) => setSetting('rtlClass', value)}
                    />
                </Section>

                <Section title="Navbar">
                    <Segmented<NavbarMode>
                        options={[
                            { value: 'navbar-static', label: 'Static' },
                            { value: 'navbar-fixed', label: 'Fixed' },
                            { value: 'navbar-hidden', label: 'Hidden' },
                        ]}
                        value={settings.navbar}
                        onChange={(value) => setSetting('navbar', value)}
                    />
                </Section>

                <Section title="Footer">
                    <Segmented<FooterMode>
                        options={[
                            { value: 'footer-static', label: 'Static' },
                            { value: 'footer-fixed', label: 'Fixed' },
                            { value: 'footer-hidden', label: 'Hidden' },
                        ]}
                        value={settings.footer}
                        onChange={(value) => setSetting('footer', value)}
                    />
                </Section>

                <div className="border-t border-border px-5 py-4">
                    <button
                        type="button"
                        onClick={resetTheme}
                        className="flex w-full items-center justify-center gap-2 rounded-lg border border-border px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-accent"
                    >
                        <RotateCcw className="size-4" />
                        Reset to defaults
                    </button>
                </div>
            </SheetContent>
        </Sheet>
    );
}
