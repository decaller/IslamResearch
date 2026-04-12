<x-filament-panels::page>
    {{-- Import Configuration Form --}}
    <x-filament-panels::form wire:submit="startImport">
        {{ $this->form }}
    </x-filament-panels::form>

    {{-- Live Progress Panel (only shown once import is running) --}}
    @if ($this->isImporting && $this->progressKey)
        @php
            $progress = $this->progress();
            $completed = count($progress['completed'] ?? []);
            $failed    = count($progress['failed'] ?? []);
            $percent   = $this->progressPercent();
            $done      = $completed + $failed >= 114;
        @endphp

        <x-filament::section
            :heading="$done ? '✅ Import Complete' : '⏳ Import In Progress'"
            icon="heroicon-o-arrow-down-tray"
            class="mt-6"
        >
            {{-- Progress bar --}}
            <div class="mb-4">
                <div class="flex items-center justify-between mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>{{ $completed }} / 114 surahs imported</span>
                    <span>{{ $percent }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                    <div
                        class="h-3 rounded-full transition-all duration-500 {{ $failed > 0 ? 'bg-warning-500' : 'bg-success-500' }}"
                        style="width: {{ $percent }}%"
                    ></div>
                </div>
            </div>

            {{-- Stats row --}}
            <div class="flex flex-wrap gap-4 text-sm">
                <div class="flex items-center gap-1.5 text-success-600 dark:text-success-400">
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4" />
                    <span>{{ $completed }} completed</span>
                </div>

                @if ($failed > 0)
                    <div class="flex items-center gap-1.5 text-danger-600 dark:text-danger-400">
                        <x-filament::icon icon="heroicon-o-x-circle" class="h-4 w-4" />
                        <span>{{ $failed }} failed</span>
                    </div>
                @endif

                @if (! $done)
                    <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-o-clock" class="h-4 w-4" />
                        <span>{{ 114 - $completed - $failed }} pending</span>
                    </div>
                @endif
            </div>

            {{-- Failed surah list --}}
            @if (! empty($progress['failed']))
                <div class="mt-4">
                    <p class="text-sm font-medium text-danger-600 dark:text-danger-400 mb-2">
                        Failed Surahs:
                    </p>
                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1 max-h-40 overflow-y-auto">
                        @foreach ($progress['failed'] as $surahNum => $errorMsg)
                            <li class="flex gap-2">
                                <span class="font-semibold">Surah {{ $surahNum }}:</span>
                                <span>{{ $errorMsg }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Actions row --}}
            <div class="mt-6 flex items-center gap-3">
                @if ($done)
                    <x-filament::button
                        wire:click="resetImport"
                        color="gray"
                        icon="heroicon-o-arrow-path"
                    >
                        Reset & Import Again
                    </x-filament::button>
                    <a href="{{ route('filament.admin.resources.source-books.index') }}" class="text-sm text-primary-600 hover:underline dark:text-primary-400">
                        View imported Source Books →
                    </a>
                @else
                    {{-- Auto-poll every 2 seconds while import is running --}}
                    <span
                        wire:poll.2000ms="refreshProgress"
                        class="text-xs text-gray-400 dark:text-gray-500 italic"
                    >
                        Auto-refreshing every 2 seconds…
                    </span>
                @endif
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
