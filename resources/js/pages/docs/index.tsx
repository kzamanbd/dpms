import { Head } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import { useResolvedAppearance } from '@/hooks/use-theme';
import { index as docsIndex } from '@/routes/docs';

type PageProps = {
    html: string;
};

export default function DocsIndex({ html }: PageProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const sourcesRef = useRef<string[] | null>(null);
    const appearance = useResolvedAppearance();

    // Render fenced ```mermaid blocks (left as <pre><code class="language-mermaid">
    // by the server-side markdown) into SVG charts, re-rendering when the theme
    // (light/dark) changes. mermaid is loaded lazily so it only ships with /docs.
    useEffect(() => {
        const root = containerRef.current;

        if (!root) {
            return;
        }

        // Capture the source once, then swap each code block for a placeholder.
        if (sourcesRef.current === null) {
            const blocks = Array.from(
                root.querySelectorAll<HTMLElement>(
                    'pre > code.language-mermaid',
                ),
            );
            sourcesRef.current = blocks.map((code) => code.textContent ?? '');

            blocks.forEach((code, index) => {
                const placeholder = document.createElement('div');
                placeholder.className = 'mermaid-chart';
                placeholder.dataset.index = String(index);
                code.parentElement?.replaceWith(placeholder);
            });
        }

        const sources = sourcesRef.current;

        if (sources.length === 0) {
            return;
        }

        let cancelled = false;

        void (async () => {
            const mermaid = (await import('mermaid')).default;
            mermaid.initialize({
                startOnLoad: false,
                securityLevel: 'loose',
                theme: appearance === 'dark' ? 'dark' : 'default',
            });

            const charts = Array.from(
                root.querySelectorAll<HTMLElement>('.mermaid-chart'),
            );

            for (const chart of charts) {
                const index = Number(chart.dataset.index);
                const code = sources[index];

                if (!code) {
                    continue;
                }

                try {
                    const { svg } = await mermaid.render(
                        `mermaid-${index}-${appearance}`,
                        code,
                    );

                    if (!cancelled) {
                        chart.innerHTML = svg;
                    }
                } catch {
                    if (!cancelled) {
                        chart.innerHTML = `<pre class="mermaid-error">${code.replace(/</g, '&lt;')}</pre>`;
                    }
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [html, appearance]);

    return (
        <>
            <Head title="User guide" />

            <div className="space-y-6 p-4">
                <Heading
                    title="User guide"
                    description="Rendered from the project README.md."
                />

                <Card>
                    <CardContent className="p-6">
                        <div
                            ref={containerRef}
                            className="markdown-body"
                            dangerouslySetInnerHTML={{ __html: html }}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

DocsIndex.layout = {
    breadcrumbs: [
        {
            title: 'User guide',
            href: docsIndex(),
        },
    ],
};
