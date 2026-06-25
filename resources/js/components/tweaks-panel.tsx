import { Check, RotateCcw, Sparkles } from 'lucide-react';
import { AppBrandLogo } from '@/components/app-brand-logo';
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

const THEME_MODES: { value: ThemeMode; label: string }[] = [
    { value: 'system', label: 'Auto' },
    { value: 'light', label: 'Light' },
    { value: 'dark', label: 'Dark' },
];

const MENU_OPTIONS: { value: MenuLayout; label: string }[] = [
    { value: 'vertical', label: 'Vertical' },
    { value: 'collapsible', label: 'Collapsed' },
    { value: 'horizontal', label: 'Horizontal' },
];

const NAVBAR_OPTIONS: { value: NavbarMode; label: string }[] = [
    { value: 'navbar-static', label: 'Static' },
    { value: 'navbar-fixed', label: 'Fixed' },
    { value: 'navbar-hidden', label: 'Hidden' },
];

const FOOTER_OPTIONS: { value: FooterMode; label: string }[] = [
    { value: 'footer-static', label: 'Static' },
    { value: 'footer-fixed', label: 'Fixed' },
    { value: 'footer-hidden', label: 'Hidden' },
];

function Section({
    title,
    hint,
    icon,
    aside,
    children,
}: {
    title: string;
    hint: string;
    icon?: React.ReactNode;
    aside?: React.ReactNode;
    children: React.ReactNode;
}) {
    return (
        <section className="border-t border-border px-5 py-4 first:border-t-0">
            <div className="mb-1 flex items-center justify-between gap-2">
                <div className="flex items-center gap-1.5">
                    {icon}
                    <h6 className="text-sm font-bold">{title}</h6>
                </div>
                {aside}
            </div>
            <p className="mb-3 text-xs text-muted-foreground">{hint}</p>
            {children}
        </section>
    );
}

/** Small radio-card check indicator (circle that fills when selected). */
function CheckCircle({ selected }: { selected: boolean }) {
    return (
        <span
            className={cn(
                'flex size-4 items-center justify-center rounded-full border transition-colors',
                selected ? 'border-primary bg-primary' : 'border-border',
            )}
        >
            <Check
                className={cn(
                    'size-3 text-white transition-opacity',
                    selected ? 'opacity-100' : 'opacity-0',
                )}
            />
        </span>
    );
}

/** Mini browser mock for the colour-scheme cards. */
function SchemeMock({ dark }: { dark: boolean }) {
    return (
        <div
            className={cn(
                'flex h-14 gap-1 overflow-hidden rounded-lg border p-1',
                dark
                    ? 'border-neutral-700 bg-neutral-900'
                    : 'border-neutral-200 bg-white',
            )}
        >
            <div className="flex flex-col gap-0.5 pt-0.5">
                <span
                    className={cn(
                        'size-1 rounded-full',
                        dark ? 'bg-neutral-600' : 'bg-neutral-300',
                    )}
                />
                <span
                    className={cn(
                        'size-1 rounded-full',
                        dark ? 'bg-neutral-600' : 'bg-neutral-300',
                    )}
                />
            </div>
            <div className="flex-1 space-y-1 pt-0.5">
                <span
                    className={cn(
                        'block h-1 w-3/4 rounded',
                        dark ? 'bg-neutral-700' : 'bg-neutral-200',
                    )}
                />
                <span className="block h-2.5 w-9 rounded bg-primary" />
                <span
                    className={cn(
                        'block h-1 w-1/2 rounded',
                        dark ? 'bg-neutral-700' : 'bg-neutral-200',
                    )}
                />
            </div>
        </div>
    );
}

/** Mini layout mock for the menu-layout cards. */
function LayoutMock({ layout }: { layout: MenuLayout }) {
    return (
        <div className="flex h-14 gap-1 overflow-hidden rounded-lg border border-neutral-200 bg-white p-1">
            {layout !== 'horizontal' && (
                <div
                    className={cn(
                        'flex flex-col gap-0.5 rounded bg-primary/15 p-0.5',
                        layout === 'collapsible' ? 'w-1.5' : 'w-3',
                    )}
                >
                    <span className="block h-1 w-full rounded-full bg-primary/40" />
                    <span className="block h-1 w-full rounded-full bg-primary/40" />
                </div>
            )}
            <div className="flex-1 space-y-1 pt-0.5">
                {layout === 'horizontal' && (
                    <span className="block h-1.5 w-full rounded bg-primary/30" />
                )}
                <span className="block h-1 w-3/4 rounded bg-neutral-200" />
                <span className="block h-1 w-1/2 rounded bg-neutral-200" />
            </div>
        </div>
    );
}

function PreviewCard({
    label,
    selected,
    onClick,
    children,
}: {
    label: string;
    selected: boolean;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={selected}
            className={cn(
                'cursor-pointer rounded-xl border p-1.5 text-left transition-all',
                selected
                    ? 'border-primary ring-1 ring-primary'
                    : 'border-border hover:border-primary/50',
            )}
        >
            {children}
            <div className="mt-1.5 flex items-center justify-between px-0.5">
                <span className="text-xs font-medium">{label}</span>
                <CheckCircle selected={selected} />
            </div>
        </button>
    );
}

/** Segmented pill group (direction / navbar / footer). */
function Segmented<T extends string>({
    options,
    value,
    onChange,
    uppercase,
}: {
    options: { value: T; label: string }[];
    value: T;
    onChange: (value: T) => void;
    uppercase?: boolean;
}) {
    return (
        <div
            className="grid gap-1 rounded-lg bg-muted p-1"
            style={{
                gridTemplateColumns: `repeat(${options.length}, minmax(0, 1fr))`,
            }}
        >
            {options.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    onClick={() => onChange(option.value)}
                    aria-pressed={value === option.value}
                    className={cn(
                        'rounded-md px-2 py-1.5 text-center text-sm font-medium transition-all',
                        uppercase && 'uppercase',
                        value === option.value
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
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
    const activeColorLabel =
        COLORS.find((c) => c.value === settings.themeVariant)?.label ??
        'Indigo';

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="w-full gap-0 overflow-y-auto p-0 sm:max-w-sm"
            >
                <SheetHeader className="flex-row items-center gap-3 bg-linear-to-br from-primary/10 to-purple-500/10 px-5 py-4">
                    <AppBrandLogo className="size-8 shrink-0" />
                    <div>
                        <SheetTitle className="text-base font-bold">
                            Theme customizer
                        </SheetTitle>
                        <SheetDescription className="text-xs">
                            Personalize the look and feel.
                        </SheetDescription>
                    </div>
                </SheetHeader>

                <Section
                    title="Color scheme"
                    hint="Choose a light, dark or system-based appearance."
                    icon={<Sparkles className="size-4 text-primary" />}
                >
                    <div className="grid grid-cols-3 gap-2">
                        {THEME_MODES.map((mode) => (
                            <PreviewCard
                                key={mode.value}
                                label={mode.label}
                                selected={settings.theme === mode.value}
                                onClick={() => setSetting('theme', mode.value)}
                            >
                                <SchemeMock dark={mode.value === 'dark'} />
                            </PreviewCard>
                        ))}
                    </div>

                    <button
                        type="button"
                        onClick={() =>
                            setSetting('semiDark', !settings.semiDark)
                        }
                        aria-pressed={settings.semiDark}
                        className={cn(
                            'mt-3 flex w-full cursor-pointer items-center gap-2.5 rounded-xl border border-dashed p-3 text-left transition-colors',
                            settings.semiDark
                                ? 'border-primary bg-primary/5'
                                : 'border-border hover:border-primary/50',
                        )}
                    >
                        <span
                            className={cn(
                                'flex size-4 shrink-0 items-center justify-center rounded border transition-colors',
                                settings.semiDark
                                    ? 'border-primary bg-primary'
                                    : 'border-border',
                            )}
                        >
                            <Check
                                className={cn(
                                    'size-3 text-white',
                                    settings.semiDark
                                        ? 'opacity-100'
                                        : 'opacity-0',
                                )}
                            />
                        </span>
                        <div>
                            <p className="text-sm font-semibold">
                                Semi-dark sidebar
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Dark sidebar with a light content area.
                            </p>
                        </div>
                    </button>
                </Section>

                <Section
                    title="Primary color"
                    hint="Set the accent color used across the UI."
                    aside={
                        <span className="text-xs font-medium text-muted-foreground">
                            {activeColorLabel}
                        </span>
                    }
                >
                    <div className="flex flex-wrap items-center gap-3">
                        {COLORS.map((color) => {
                            const selected =
                                settings.themeVariant === color.value;

                            return (
                                <button
                                    key={color.value}
                                    type="button"
                                    onClick={() =>
                                        setSetting('themeVariant', color.value)
                                    }
                                    aria-label={color.label}
                                    aria-pressed={selected}
                                    title={color.label}
                                    className={cn(
                                        'flex size-9 cursor-pointer items-center justify-center rounded-full transition-all',
                                        color.swatch,
                                        selected &&
                                            'ring-2 ring-foreground ring-offset-2 ring-offset-background',
                                    )}
                                >
                                    <Check
                                        className={cn(
                                            'size-4 text-white',
                                            selected
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                </button>
                            );
                        })}
                    </div>
                </Section>

                <Section
                    title="Menu layout"
                    hint="Pick how the navigation is arranged."
                >
                    <div className="grid grid-cols-3 gap-2">
                        {MENU_OPTIONS.map((option) => (
                            <PreviewCard
                                key={option.value}
                                label={option.label}
                                selected={settings.menu === option.value}
                                onClick={() => setMenuLayout(option.value)}
                            >
                                <LayoutMock layout={option.value} />
                            </PreviewCard>
                        ))}
                    </div>
                </Section>

                <Section
                    title="Direction"
                    hint="Left-to-right or right-to-left."
                >
                    <Segmented<'ltr' | 'rtl'>
                        options={[
                            { value: 'ltr', label: 'ltr' },
                            { value: 'rtl', label: 'rtl' },
                        ]}
                        value={settings.rtlClass}
                        onChange={(value) => setSetting('rtlClass', value)}
                        uppercase
                    />
                </Section>

                <Section title="Navbar" hint="Top bar behavior on scroll.">
                    <Segmented<NavbarMode>
                        options={NAVBAR_OPTIONS}
                        value={settings.navbar}
                        onChange={(value) => setSetting('navbar', value)}
                    />
                </Section>

                <Section title="Footer" hint="Footer positioning.">
                    <Segmented<FooterMode>
                        options={FOOTER_OPTIONS}
                        value={settings.footer}
                        onChange={(value) => setSetting('footer', value)}
                    />
                </Section>

                <div className="border-t border-border p-4">
                    <button
                        type="button"
                        onClick={resetTheme}
                        className="flex w-full items-center justify-center gap-2 rounded-lg border border-border px-4 py-2.5 text-sm font-semibold transition-colors hover:border-primary hover:bg-primary/5 hover:text-primary"
                    >
                        <RotateCcw className="size-4" />
                        Reset to defaults
                    </button>
                </div>
            </SheetContent>
        </Sheet>
    );
}
