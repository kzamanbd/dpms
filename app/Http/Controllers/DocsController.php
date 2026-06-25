<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class DocsController extends Controller
{
    /**
     * Render the project README as the in-app user guide.
     */
    public function index(): Response
    {
        $path = base_path('README.md');
        $markdown = File::exists($path) ? File::get($path) : '# User guide';

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        return Inertia::render('docs/index', [
            'html' => (string) $converter->convert($markdown),
        ]);
    }
}
