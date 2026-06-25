import { Link, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { menu } from '@/lib/menu';
import type { MenuNode } from '@/lib/menu';
import { cn } from '@/lib/utils';

function isActive(href: string | undefined, currentPath: string): boolean {
    return (
        !!href && (currentPath === href || currentPath.startsWith(`${href}/`))
    );
}

export function HorizontalMenu() {
    const currentPath = usePage().url.split('?')[0];
    const items = menu.filter((node) => !node.heading);

    return (
        <nav className="horizontal-menu mx-6 border-b px-0 py-1.5">
            {items.map((node) => {
                const Icon = node.icon;

                if (node.children && node.children.length > 0) {
                    const groupActive = node.children.some((child) =>
                        isActive(child.href, currentPath),
                    );

                    return (
                        <DropdownMenu key={node.label}>
                            <DropdownMenuTrigger asChild>
                                <button
                                    type="button"
                                    className={cn(
                                        'nav-link',
                                        groupActive && 'active',
                                    )}
                                >
                                    {Icon && <Icon className="size-4" />}
                                    <span>{node.label}</span>
                                    <ChevronDown className="tw-arrow size-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" className="w-48">
                                {node.children.map((child: MenuNode) => (
                                    <DropdownMenuItem key={child.label} asChild>
                                        <Link href={child.href ?? '#'} prefetch>
                                            {child.label}
                                        </Link>
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    );
                }

                return (
                    <Link
                        key={node.label}
                        href={node.href ?? '#'}
                        prefetch
                        className={cn(
                            'nav-link',
                            isActive(node.href, currentPath) && 'active',
                        )}
                    >
                        {Icon && <Icon className="size-4" />}
                        <span>{node.label}</span>
                    </Link>
                );
            })}
        </nav>
    );
}
