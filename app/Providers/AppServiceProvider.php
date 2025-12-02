<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Markdown;

class AppServiceProvider extends ServiceProvider
{
    protected string $highlightTheme = 'material-theme-palenight';

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerMarkdownExtensions();
        $this->registerShiki();
        $this->registerTorchlightEngine();
        $this->registerComputedContent();
    }

    protected function registerMarkdownExtensions(): void
    {
        Markdown::addExtensions(function () {
            return [
                new \League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension,
                new \League\CommonMark\Extension\TableOfContents\TableOfContentsExtension,
                new \App\Markdown\Hint\HintExtension,
            ];
        });
    }

    protected function registerShiki(): void
    {
        if (! config('dok.code_highlighting_enabled') || config('dok.code_highlighter') != 'shiki' || ! class_exists(\Spatie\CommonMarkShikiHighlighter\HighlightCodeExtension::class)) {
            return;
        }

        Markdown::addExtension(function () {
            return new \Spatie\CommonMarkShikiHighlighter\HighlightCodeExtension(theme: $this->highlightTheme);
        });

    }

    protected function registerTorchlightEngine(): void
    {
        if (! config('dok.code_highlighting_enabled') || config('dok.code_highlighter') != 'torchlight-engine' || ! class_exists(\Torchlight\Engine\CommonMark\Extension::class)) {
            return;
        }

        Markdown::addExtension(function () {
            return new \Torchlight\Engine\CommonMark\Extension(theme: $this->highlightTheme);
        });

        \Torchlight\Engine\Options::setDefaultOptionsBuilder(function () {
            return new \Torchlight\Engine\Options(
                lineNumbersEnabled: false,
            );
        });
    }


    protected function registerComputedContent(): void
    {
        $contentComputedCollections = collect(Entry::query()
            ->where('collection', 'releases')
            ->where('content_is_computed', true)
            ->get())
            ->map(function ($entry) {
                return $entry->value('version_collection');
            })
            ->toArray();

        Collection::computed($contentComputedCollections, 'content', function ($entry, $value) {
            if (! $entry->get('resource_location')) {
                return;
            }

            $file = base_path('content/docs/'.$entry->get('resource_location'));

            if (File::exists($file)) {
                return file_get_contents($file);
            }
        });
    }
}
