import { useId } from 'react';

/**
 * Vantyx brand mark — a gradient badge with a stylised "V".
 */
export function AppBrandLogo({ className = 'size-8' }: { className?: string }) {
    const gradientId = useId();

    return (
        <svg viewBox="0 0 64 64" fill="none" className={className} aria-hidden>
            <defs>
                <linearGradient
                    id={gradientId}
                    x1="0"
                    y1="0"
                    x2="64"
                    y2="64"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop stopColor="#FF8A3D" />
                    <stop offset="1" stopColor="#FC5800" />
                </linearGradient>
            </defs>
            <rect width="64" height="64" rx="15" fill={`url(#${gradientId})`} />
            <path
                d="M19 21L32 45L45 21"
                fill="none"
                stroke="#ffffff"
                strokeWidth="8"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
