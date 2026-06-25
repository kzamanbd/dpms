import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import type { MenuNode } from '@/lib/menu';
import { cn } from '@/lib/utils';

function isHrefActive(href: string | undefined, currentPath: string): boolean {
    if (!href) {
        return false;
    }

    return currentPath === href || currentPath.startsWith(`${href}/`);
}

function containsActive(node: MenuNode, currentPath: string): boolean {
    if (isHrefActive(node.href, currentPath)) {
        return true;
    }

    return (node.children ?? []).some((child) =>
        containsActive(child, currentPath),
    );
}

function Badge({ text, className }: { text: string; className?: string }) {
    return (
        <span
            className={cn(
                'rounded-full px-1.5 py-0.5 text-[10px] font-semibold tracking-wide',
                className ?? 'bg-primary/15 text-primary',
            )}
        >
            {text}
        </span>
    );
}

export function VerticalMenuNode({
    node,
    level,
    currentPath,
    onNavigate,
}: {
    node: MenuNode;
    level: number;
    currentPath: string;
    onNavigate?: () => void;
}) {
    const isTop = level === 0;

    if (node.heading) {
        return (
            <li className="tw-menu-header">
                <span className="minus-icon">
                    <span className="block h-px w-3 bg-current" />
                </span>
                <span className="minus-label">{node.label}</span>
            </li>
        );
    }

    // Group (has children) — collapsible accordion.
    if (node.children && node.children.length > 0) {
        return (
            <GroupNode
                node={node}
                level={level}
                currentPath={currentPath}
                onNavigate={onNavigate}
            />
        );
    }

    // Leaf link.
    const active = isHrefActive(node.href, currentPath);
    const Icon = node.icon;

    const label = isTop ? (
        <span className="tw-link-label">
            {node.label}
            {node.badge && (
                <Badge
                    text={node.badge.text}
                    className={node.badge.className}
                />
            )}
        </span>
    ) : (
        <span>{node.label}</span>
    );

    return (
        <li className={isTop ? 'tw-menu-item' : 'twd--menu-item'}>
            <Link
                href={node.href ?? '#'}
                onClick={onNavigate}
                prefetch
                aria-current={active ? 'page' : undefined}
                className={cn(
                    isTop ? 'tw-menu-link' : 'twd--link',
                    active && 'active',
                )}
            >
                {isTop && Icon && <Icon />}
                {label}
            </Link>
        </li>
    );
}

function GroupNode({
    node,
    level,
    currentPath,
    onNavigate,
}: {
    node: MenuNode;
    level: number;
    currentPath: string;
    onNavigate?: () => void;
}) {
    const isTop = level === 0;
    const hasActiveChild = containsActive(node, currentPath);
    // Open state follows the active route by default; a manual toggle overrides it.
    const [override, setOverride] = useState<boolean | null>(null);
    const open = override ?? hasActiveChild;
    const Icon = node.icon;

    return (
        <li
            className={cn(
                isTop ? 'tw-menu-item' : 'twd--menu-item',
                open && 'active',
            )}
        >
            <button
                type="button"
                aria-expanded={open}
                onClick={() => setOverride(!open)}
                className={isTop ? 'tw-menu-link' : 'twd--link'}
            >
                {isTop && Icon && <Icon />}
                {isTop ? (
                    <span className="tw-link-label">
                        {node.label}
                        {node.badge && (
                            <Badge
                                text={node.badge.text}
                                className={node.badge.className}
                            />
                        )}
                    </span>
                ) : (
                    <span>{node.label}</span>
                )}
                <ChevronDown className="tw-arrow" />
            </button>

            <div className="twd--submenu">
                <ul className="twd--menu">
                    {node.children!.map((child) => (
                        <VerticalMenuNode
                            key={child.label}
                            node={child}
                            level={level + 1}
                            currentPath={currentPath}
                            onNavigate={onNavigate}
                        />
                    ))}
                </ul>
            </div>
        </li>
    );
}
