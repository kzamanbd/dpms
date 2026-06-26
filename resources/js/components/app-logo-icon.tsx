import type { SVGAttributes } from 'react';

/**
 * Monochrome DPMS mark — the universal power glyph, inheriting the current
 * text colour. Used on the auth layouts where a flat single-colour logo reads
 * better than the gradient badge.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 64 64"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M21.1 22A17 17 0 1 0 42.9 22"
                stroke="currentColor"
                strokeWidth="7"
                strokeLinecap="round"
            />
            <path
                d="M32 15V33"
                stroke="currentColor"
                strokeWidth="7"
                strokeLinecap="round"
            />
        </svg>
    );
}
