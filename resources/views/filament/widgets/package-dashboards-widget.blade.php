<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            System Links
        </x-slot>

        <div class="flex flex-wrap gap-3">
            @foreach ($this->getDashboards() as $dashboard)
                <x-filament::button
                    href="{{ $dashboard['url'] }}"
                    tag="a"
                    target="_blank"
                    color="gray"
                    outlined
                    icon="{{ $dashboard['icon'] }}"
                    size="sm"
                >
                    {{ $dashboard['name'] }}
                </x-filament::button>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
