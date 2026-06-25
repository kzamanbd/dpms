<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- No-flash theme init: apply dark/light, primary variant and direction from the
             persisted themeConfig before first paint (see resources/js/hooks/use-theme.tsx). --}}
        <script>
            (function() {
                try {
                    var raw = localStorage.getItem('themeConfig');
                    var cfg = raw ? JSON.parse(raw) : null;
                    var theme = (cfg && cfg.theme) || '{{ $appearance ?? "system" }}';
                    var variant = (cfg && cfg.themeVariant) || 'default';
                    var dir = (cfg && cfg.rtlClass) || 'ltr';
                    var isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    var root = document.documentElement;
                    root.classList.toggle('dark', isDark);
                    root.classList.toggle('light', !isDark);
                    root.classList.add('theme-' + variant);
                    root.setAttribute('dir', dir);
                } catch (e) {}
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(0.99 0 0);
            }

            html.dark {
                background-color: oklch(0 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
