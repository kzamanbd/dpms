import { Link, usePage } from '@inertiajs/react';
import { X } from 'lucide-react';
import { AppBrandLogo } from '@/components/app-brand-logo';
import { VerticalMenuNode } from '@/components/vertical-menu-node';
import { useTheme } from '@/hooks/use-theme';
import { menu } from '@/lib/menu';
import { dashboard } from '@/routes';

export function VerticalMenu() {
    const {
        verticalMenuClass,
        isMobileMenuOpen,
        toggleMobileMenu,
        closeMobileMenu,
    } = useTheme();
    const currentPath = usePage().url.split('?')[0];

    return (
        <>
            {isMobileMenuOpen && (
                <div
                    className="menu-shadow"
                    onClick={toggleMobileMenu}
                    aria-hidden
                />
            )}

            <aside className={verticalMenuClass}>
                <div className="vertical-content">
                    <div className="tw-brand-logo">
                        <Link
                            href={dashboard()}
                            className="group inline-flex items-center"
                            onClick={closeMobileMenu}
                        >
                            <AppBrandLogo className="size-8 shrink-0" />
                            <span className="app-name">DPMS</span>
                        </Link>
                        <button
                            type="button"
                            className="mini-sidebar lg:hidden"
                            onClick={toggleMobileMenu}
                            aria-label="Close menu"
                        >
                            <X className="size-5" />
                        </button>
                    </div>

                    <div className="min-h-0 flex-1 overflow-y-auto">
                        <ul className="tw-nav-menu">
                            {menu.map((node) => (
                                <VerticalMenuNode
                                    key={node.label}
                                    node={node}
                                    level={0}
                                    currentPath={currentPath}
                                    onNavigate={closeMobileMenu}
                                />
                            ))}
                        </ul>
                    </div>
                </div>
            </aside>
        </>
    );
}
