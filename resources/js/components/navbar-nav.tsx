import { usePage } from '@inertiajs/react';
import { Moon, PanelLeft, Palette, Sun } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { useTheme } from '@/hooks/use-theme';
import type { BreadcrumbItem } from '@/types';

export function NavbarNav({
    breadcrumbs = [],
    onOpenCustomizer,
}: {
    breadcrumbs?: BreadcrumbItem[];
    onOpenCustomizer: () => void;
}) {
    const { navbarClass, settings, setSetting, toggleSidebar } = useTheme();
    const { auth } = usePage().props;
    const getInitials = useInitials();

    const isDark = settings.theme === 'dark';
    const toggleDark = () => setSetting('theme', isDark ? 'light' : 'dark');

    return (
        <header className={navbarClass}>
            <div className="flex min-w-0 items-center gap-1.5">
                <button
                    type="button"
                    className="header-icon horizontal:lg:hidden! collapsed-menu:lg:flex"
                    onClick={toggleSidebar}
                    aria-label="Toggle sidebar"
                >
                    <PanelLeft className="size-5" />
                </button>
                <div className="min-w-0 horizontal:hidden">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            </div>

            <div className="flex items-center gap-1.5">
                <button
                    type="button"
                    className="header-icon"
                    onClick={toggleDark}
                    aria-label={
                        isDark ? 'Switch to light mode' : 'Switch to dark mode'
                    }
                >
                    {isDark ? (
                        <Sun className="size-5" />
                    ) : (
                        <Moon className="size-5" />
                    )}
                </button>

                <button
                    type="button"
                    className="header-icon"
                    onClick={onOpenCustomizer}
                    aria-label="Open theme customizer"
                >
                    <Palette className="size-5" />
                </button>

                {auth.user && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button
                                type="button"
                                className="ml-1 rounded-full outline-hidden focus-visible:ring-2 focus-visible:ring-ring"
                                aria-label="Open user menu"
                            >
                                <Avatar className="size-8">
                                    <AvatarImage
                                        src={auth.user.avatar}
                                        alt={auth.user.name}
                                    />
                                    <AvatarFallback className="bg-primary/10 text-primary">
                                        {getInitials(auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>
        </header>
    );
}
