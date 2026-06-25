import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import type { ReactNode } from 'react';

export type ThemeMode = 'light' | 'dark' | 'system';
export type ThemeVariant =
    | 'default'
    | 'amber'
    | 'rose'
    | 'purple'
    | 'sky'
    | 'teal';
export type MenuLayout = 'vertical' | 'collapsible' | 'horizontal';
export type NavbarMode = 'navbar-fixed' | 'navbar-static' | 'navbar-hidden';
export type FooterMode = 'footer-fixed' | 'footer-static' | 'footer-hidden';
export type Direction = 'ltr' | 'rtl';

export type ThemeSettings = {
    theme: ThemeMode;
    themeVariant: ThemeVariant;
    rtlClass: Direction;
    menu: MenuLayout;
    navbar: NavbarMode;
    footer: FooterMode;
    semiDark: boolean;
    collapsable: boolean;
};

export const defaultSettings = (): ThemeSettings => ({
    theme: 'system',
    themeVariant: 'default',
    rtlClass: 'ltr',
    menu: 'vertical',
    navbar: 'navbar-fixed',
    footer: 'footer-fixed',
    semiDark: false,
    collapsable: false,
});

const STORAGE_KEY = 'themeConfig';

const prefersDark = (): boolean =>
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-color-scheme: dark)').matches;

const resolveDark = (theme: ThemeMode): boolean =>
    theme === 'dark' || (theme === 'system' && prefersDark());

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.cookie = `${name}=${value};path=/;max-age=${days * 24 * 60 * 60};SameSite=Lax`;
};

const readSettings = (): ThemeSettings => {
    if (typeof window === 'undefined') {
        return defaultSettings();
    }

    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        return raw
            ? { ...defaultSettings(), ...JSON.parse(raw) }
            : defaultSettings();
    } catch {
        return defaultSettings();
    }
};

/**
 * Apply the colour-affecting settings to <html>: theme variant class, dark/light,
 * and text direction. Mirrors the resolved appearance into the `appearance` cookie
 * so the server-rendered <html class> matches on the next request (no flash).
 */
const applyToDom = (settings: ThemeSettings): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;
    const isDark = resolveDark(settings.theme);

    root.classList.toggle('dark', isDark);
    root.classList.toggle('light', !isDark);
    root.style.colorScheme = isDark ? 'dark' : 'light';

    root.classList.forEach((cls) => {
        if (cls.startsWith('theme-')) {
            root.classList.remove(cls);
        }
    });
    root.classList.add(`theme-${settings.themeVariant}`);

    root.setAttribute('dir', settings.rtlClass);

    setCookie('appearance', settings.theme);
};

type ThemeContextValue = {
    settings: ThemeSettings;
    isMobileMenuOpen: boolean;
    wrapperClass: string;
    navbarClass: string;
    footerClass: string;
    verticalMenuClass: string;
    setSetting: <K extends keyof ThemeSettings>(
        key: K,
        value: ThemeSettings[K],
    ) => void;
    setMenuLayout: (value: MenuLayout) => void;
    toggleSidebar: () => void;
    toggleCollapsible: () => void;
    toggleMobileMenu: () => void;
    closeMobileMenu: () => void;
    resetTheme: () => void;
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

export function ThemeProvider({ children }: { children: ReactNode }) {
    const [settings, setSettings] = useState<ThemeSettings>(() =>
        readSettings(),
    );
    const [isMobileMenuOpen, setMobileMenuOpen] = useState(false);

    // Suppress transitions for one frame when a colour-affecting setting changes,
    // so the whole UI recolours instantly instead of animating piecemeal.
    const suppress = useRef<string>(
        [
            settings.theme,
            settings.themeVariant,
            settings.semiDark,
            settings.rtlClass,
        ].join('|'),
    );

    useEffect(() => {
        const key = [
            settings.theme,
            settings.themeVariant,
            settings.semiDark,
            settings.rtlClass,
        ].join('|');

        if (key !== suppress.current) {
            suppress.current = key;
            const root = document.documentElement;
            root.classList.add('theme-no-transition');
            requestAnimationFrame(() => {
                requestAnimationFrame(() =>
                    root.classList.remove('theme-no-transition'),
                );
            });
        }

        applyToDom(settings);

        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
        } catch {
            // ignore quota / private mode failures
        }
    }, [settings]);

    // Re-resolve when the OS theme changes while in "system" mode.
    useEffect(() => {
        if (settings.theme !== 'system') {
            return;
        }

        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        const handler = () => applyToDom(settings);
        mq.addEventListener('change', handler);

        return () => mq.removeEventListener('change', handler);
    }, [settings]);

    // Navbar gets a `scrollable` class once the page scrolls (drives the floating shadow).
    useEffect(() => {
        const handleScroll = () => {
            const navbar = document.querySelector('.navbar-nav');
            navbar?.classList.toggle('scrollable', window.scrollY > 0);
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();

        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    // Keep the mobile/desktop invariants: no collapsed-rail on mobile, no open
    // drawer on desktop.
    useEffect(() => {
        const handleResize = () => {
            if (window.innerWidth < 1024) {
                setSettings((s) =>
                    s.collapsable ? { ...s, collapsable: false } : s,
                );
            } else {
                setMobileMenuOpen(false);
            }
        };

        handleResize();
        window.addEventListener('resize', handleResize);

        return () => window.removeEventListener('resize', handleResize);
    }, []);

    const setSetting = useCallback(
        <K extends keyof ThemeSettings>(key: K, value: ThemeSettings[K]) => {
            setSettings((s) => {
                const next = { ...s, [key]: value };

                // semi-dark only reads in light/system; flip to system so it is visible.
                if (key === 'semiDark' && value) {
                    next.theme = 'system';
                }

                return next;
            });
        },
        [],
    );

    const setMenuLayout = useCallback((value: MenuLayout) => {
        setSettings((s) => ({
            ...s,
            menu: value,
            collapsable: value === 'collapsible',
        }));
    }, []);

    const toggleMobileMenu = useCallback(
        () => setMobileMenuOpen((open) => !open),
        [],
    );
    const closeMobileMenu = useCallback(() => setMobileMenuOpen(false), []);

    const toggleCollapsible = useCallback(() => {
        setSettings((s) => ({ ...s, collapsable: !s.collapsable }));
    }, []);

    const toggleSidebar = useCallback(() => {
        if (window.innerWidth < 1024) {
            setMobileMenuOpen((open) => !open);
        } else {
            setSettings((s) => ({ ...s, collapsable: !s.collapsable }));
        }
    }, []);

    const resetTheme = useCallback(() => {
        setSettings(defaultSettings());
        setMobileMenuOpen(false);
    }, []);

    const value = useMemo<ThemeContextValue>(() => {
        const wrapperClass = [
            'tw--wrapper',
            settings.collapsable && 'collapsed-menu',
            settings.menu === 'horizontal' && 'horizontal',
            (settings.menu === 'vertical' || settings.menu === 'collapsible') &&
                'vertical',
        ]
            .filter(Boolean)
            .join(' ');

        return {
            settings,
            isMobileMenuOpen,
            wrapperClass,
            navbarClass: `navbar-nav ${settings.navbar}`,
            footerClass: `footer ${settings.footer}`,
            verticalMenuClass:
                `vertical-menu ${settings.semiDark ? 'semi-dark' : ''} ${isMobileMenuOpen ? 'expanded' : ''}`.trim(),
            setSetting,
            setMenuLayout,
            toggleSidebar,
            toggleCollapsible,
            toggleMobileMenu,
            closeMobileMenu,
            resetTheme,
        };
    }, [
        settings,
        isMobileMenuOpen,
        setSetting,
        setMenuLayout,
        toggleSidebar,
        toggleCollapsible,
        toggleMobileMenu,
        closeMobileMenu,
        resetTheme,
    ]);

    return (
        <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
    );
}

export function useTheme(): ThemeContextValue {
    const ctx = useContext(ThemeContext);

    if (!ctx) {
        throw new Error('useTheme must be used within a ThemeProvider');
    }

    return ctx;
}

/**
 * The currently rendered appearance ('light' | 'dark'), resolving the "system"
 * setting against the OS preference and updating when either changes.
 */
export function useResolvedAppearance(): 'light' | 'dark' {
    const { settings } = useTheme();
    const [resolved, setResolved] = useState<'light' | 'dark'>(() =>
        resolveDark(settings.theme) ? 'dark' : 'light',
    );

    useEffect(() => {
        const update = () =>
            setResolved(resolveDark(settings.theme) ? 'dark' : 'light');
        update();

        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        mq.addEventListener('change', update);

        return () => mq.removeEventListener('change', update);
    }, [settings.theme]);

    return resolved;
}
