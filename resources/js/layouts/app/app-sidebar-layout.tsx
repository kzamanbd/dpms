import { useState } from 'react';
import { HorizontalMenu } from '@/components/horizontal-menu';
import { NavbarNav } from '@/components/navbar-nav';
import { TweaksPanel } from '@/components/tweaks-panel';
import { VerticalMenu } from '@/components/vertical-menu';
import { useTheme } from '@/hooks/use-theme';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const { wrapperClass, footerClass, settings } = useTheme();
    const [customizerOpen, setCustomizerOpen] = useState(false);

    return (
        <div className={wrapperClass}>
            <a href="#main-content" className="skip-to-content">
                Skip to content
            </a>

            <VerticalMenu />

            <div className="relative flex min-h-screen flex-col print:m-0 vertical:transition-[margin] vertical:duration-300 vertical:ease-in-out vertical:ltr:lg:ml-64 vertical:rtl:lg:mr-64 collapsed-menu:ltr:lg:ml-17.5 collapsed-menu:rtl:lg:mr-17.5">
                <NavbarNav
                    breadcrumbs={breadcrumbs}
                    onOpenCustomizer={() => setCustomizerOpen(true)}
                />

                {settings.menu === 'horizontal' && <HorizontalMenu />}

                <main
                    id="main-content"
                    tabIndex={-1}
                    className="relative mx-3 mb-3 flex flex-1 flex-col pt-4 sm:mx-6 dark:text-foreground print:m-0 print:p-0"
                >
                    {children}
                </main>

                <footer className={footerClass}>
                    <span className="text-sm text-muted-foreground">
                        © {new Date().getFullYear()} DPMS
                    </span>
                    <span className="hidden text-sm text-muted-foreground md:block">
                        Device Power Management System
                    </span>
                </footer>
            </div>

            <TweaksPanel
                open={customizerOpen}
                onOpenChange={setCustomizerOpen}
            />
        </div>
    );
}
