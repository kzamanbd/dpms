import { useId } from 'react';

/**
 * DPMS brand mark — a gradient squircle badge carrying the universal power
 * glyph, nodding to the app's device power-management purpose. A soft top-left
 * sheen and a hairline inner ring give the badge a little depth.
 */
export function AppBrandLogo({ className = 'size-8' }: { className?: string }) {
    const fill = useId();
    const sheen = useId();

    return (
        <svg viewBox="0 0 64 64" fill="none" className={className} aria-hidden>
            <defs>
                <linearGradient
                    id={fill}
                    x1="6"
                    y1="4"
                    x2="58"
                    y2="60"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop stopColor="#FF9A4D" />
                    <stop offset="0.55" stopColor="#FC6A12" />
                    <stop offset="1" stopColor="#E84600" />
                </linearGradient>
                <radialGradient
                    id={sheen}
                    cx="0"
                    cy="0"
                    r="1"
                    gradientUnits="userSpaceOnUse"
                    gradientTransform="translate(18 14) rotate(45) scale(46)"
                >
                    <stop stopColor="#ffffff" stopOpacity="0.35" />
                    <stop offset="1" stopColor="#ffffff" stopOpacity="0" />
                </radialGradient>
            </defs>

            <rect width="64" height="64" rx="16" fill={`url(#${fill})`} />
            <rect width="64" height="64" rx="16" fill={`url(#${sheen})`} />
            <rect
                x="0.75"
                y="0.75"
                width="62.5"
                height="62.5"
                rx="15.25"
                stroke="#ffffff"
                strokeOpacity="0.18"
                strokeWidth="1.5"
            />

            {/* Universal power glyph: broken ring + vertical break. */}
            <path
                d="M21.1 22A17 17 0 1 0 42.9 22"
                fill="none"
                stroke="#ffffff"
                strokeWidth="7"
                strokeLinecap="round"
            />
            <path
                d="M32 15V33"
                fill="none"
                stroke="#ffffff"
                strokeWidth="7"
                strokeLinecap="round"
            />
        </svg>
    );
}
