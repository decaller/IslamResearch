<div class="flex h-screen bg-base-200">
    <!-- Activity Bar -->
    <div class="w-16 bg-base-300 flex flex-col items-center py-4 space-y-4 shadow-xl">
        <div class="tooltip tooltip-right" data-tip="Explorer">
            <button class="btn btn-ghost btn-square">
                <x-heroicon-o-folder class="w-6 h-6" />
            </button>
        </div>
        <div class="tooltip tooltip-right" data-tip="Search">
            <button class="btn btn-primary btn-square">
                <x-heroicon-o-magnifying-glass class="w-6 h-6" />
            </button>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-12 bg-base-100 border-b border-base-300 flex items-center px-4 justify-between">
            <div class="font-bold text-lg">Scholar IDE</div>
            <div class="flex items-center space-x-2">
                <span class="badge badge-outline">Phase 3</span>
            </div>
        </header>

        <main class="flex-1 overflow-auto p-4">
            <livewire:scholar.search-panel />
        </main>
    </div>
</div>
