<?php

namespace App\Services\Notebook;

use App\Models\Sentence;

// Assuming this is for roots

class LiveEmbedRenderer
{
    /**
     * Parse and render all dynamic embeds in the markdown content.
     */
    public function render(string $markdown): string
    {
        return preg_replace_callback('/\{\{\s*embed:(.*?)\s*\}\}/', function ($matches) {
            return $this->resolveEmbed($matches[1]);
        }, $markdown);
    }

    /**
     * Resolve a single embed tag.
     * Format: type:identifier|options
     */
    protected function resolveEmbed(string $embedString): string
    {
        $parts = explode('|', $embedString);
        $main = trim($parts[0]);
        $options = $this->parseOptions(array_slice($parts, 1));

        $segments = explode(':', $main);
        $type = $segments[0] ?? null;
        $id = $segments[1] ?? null;

        return match ($type) {
            'hadith', 'sentence' => $this->renderSentenceEmbed($id, $options),
            'root' => $this->renderRootEmbed($id, $options),
            'query' => $this->renderQueryEmbed($id, $options),
            default => "<!-- Unknown embed type: $type -->",
        };
    }

    protected function parseOptions(array $optionStrings): array
    {
        $options = [];
        foreach ($optionStrings as $s) {
            if (str_contains($s, '=')) {
                [$key, $val] = explode('=', $s, 2);
                $options[trim($key)] = trim($val);
            } else {
                $options[trim($s)] = true;
            }
        }

        return $options;
    }

    protected function renderSentenceEmbed(?string $id, array $options): string
    {
        if (! $id) {
            return '';
        }

        $sentence = Sentence::with(['sourceBook'])->find($id);
        if (! $sentence) {
            return "<!-- Sentence not found: $id -->";
        }

        $citation = '';
        if ($options['show_breadcrumb'] ?? false) {
            // In a real app, we'd fetch the discovery trail from user_journeys
            $citation = '<div class="text-xs opacity-50 mt-2">🔗 Discovered via search</div>';
        }

        return <<<HTML
        <div class="my-6 p-4 border-l-4 border-primary bg-base-200 rounded-r-lg shadow-sm group">
            <div class="flex justify-between items-start mb-2">
                <span class="text-xs font-bold uppercase tracking-widest opacity-50">{$sentence->resource_type}</span>
                <span class="text-xs opacity-50">{$sentence->sourceBook?->title}</span>
            </div>
            <p class="text-xl font-arabic text-right leading-loose mb-3" dir="rtl">{$sentence->sentence_text}</p>
            <div class="prose prose-sm max-w-none opacity-80">
                <!-- Translation would go here -->
            </div>
            {$citation}
        </div>
        HTML;
    }

    protected function renderRootEmbed(?string $id, array $options): string
    {
        if (! $id) {
            return '';
        }

        // Lookup root logic here...
        return <<<HTML
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-secondary/10 text-secondary border border-secondary/20 rounded-full cursor-help hover:bg-secondary/20 transition-all">
            <span class="font-arabic text-lg">{$id}</span>
            <span class="text-xs font-medium uppercase">Root</span>
        </div>
        HTML;
    }

    protected function renderQueryEmbed(?string $query, array $options): string
    {
        if (! $query) {
            return '';
        }

        $limit = $options['limit'] ?? 3;

        return <<<HTML
        <div class="my-8 p-6 bg-base-300 rounded-xl border-t-2 border-accent">
            <div class="flex items-center gap-2 mb-4">
                <div class="badge badge-accent">Live Feed</div>
                <span class="text-sm font-semibold italic">"{$query}"</span>
            </div>
            <div class="space-y-4">
                <div class="animate-pulse flex space-x-4">
                    <div class="flex-1 space-y-6 py-1">
                        <div class="h-2 bg-slate-700 rounded"></div>
                        <div class="space-y-3">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="h-2 bg-slate-700 rounded col-span-2"></div>
                                <div class="h-2 bg-slate-700 rounded col-span-1"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-center opacity-40 uppercase tracking-tighter">Real-time search results would populate here</p>
            </div>
        </div>
        HTML;
    }
}
