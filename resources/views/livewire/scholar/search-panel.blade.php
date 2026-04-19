<div class="relative w-full max-w-xl" x-data="{ showSuggestions: true }" @click.outside="showSuggestions = false">
    <!-- Search Input Group -->
    <div class="join w-full bg-base-200 rounded-lg focus-within:ring-2 focus-within:ring-primary shadow-sm hover:shadow relative">
        <input 
            type="text" 
            wire:model.live.debounce.300ms="query" 
            wire:keydown.enter="executeSearch"
            @focus="showSuggestions = true"
            dir="auto"
            class="input input-ghost join-item w-full !outline-none text-lg rtl:text-right font-arabic" 
            placeholder="Search Quran, Hadith, Lexicon (e.g. #root:كتب)" 
        />
        
        @if($intentBadge)
            <div class="absolute right-12 top-1/2 -translate-y-1/2 flex items-center pr-2">
                <span class="badge badge-primary badge-sm whitespace-nowrap">{{ $intentBadge }}</span>
            </div>
        @endif

        <button wire:click="executeSearch" class="btn btn-ghost join-item rounded-r-lg">
            <x-heroicon-o-magnifying-glass class="w-5 h-5 text-base-content/70" />
        </button>
    </div>

    <!-- Autocomplete Dropdown -->
    @if(!empty($suggestions['roots']) || !empty($suggestions['sentences']) || !empty($suggestions['entities']))
        <div x-show="showSuggestions" class="absolute z-50 w-full mt-1 bg-base-100 border border-base-300 rounded-lg shadow-xl overflow-hidden shadow-2xl">

            @if(!empty($suggestions['entities']))
                <div class="p-2 border-b border-base-200">
                    <h3 class="text-xs font-semibold text-primary uppercase px-2 mb-1 flex items-center gap-1">
                        <x-heroicon-o-sparkles class="w-3 h-3" />
                        Did you mean?
                    </h3>
                    <ul class="menu menu-compact bg-base-100 w-full p-0">
                        @foreach($suggestions['entities'] as $entity)
                            <li>
                                <a wire:click="$set('query', '#entity:{{ $entity['id'] }}'); executeSearch()" class="flex flex-col items-start hover:bg-base-200 py-2">
                                    <div class="flex items-center gap-2 w-full">
                                        <span class="font-bold text-base-content">{{ $entity['canonical_name'] }}</span>
                                        <span class="badge badge-sm badge-outline opacity-70">{{ $entity['entity_type'] }}</span>
                                    </div>
                                    @if($entity['description'])
                                        <p class="text-xs opacity-60 line-clamp-1 mt-0.5">{{ $entity['description'] }}</p>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @if(!empty($suggestions['roots']))
                <div class="p-2 border-b border-base-200">
                    <h3 class="text-xs font-semibold text-base-content/50 uppercase px-2 mb-1">Lexicon Roots</h3>
                    <ul class="menu menu-compact bg-base-100 w-full p-0">
                        @foreach($suggestions['roots'] as $root)
                            <li>
                                <a wire:click="$set('query', '#root:{{ $root['root_value'] }}'); executeSearch()" class="flex justify-between hover:bg-base-200">
                                    <span class="font-arabic text-lg text-primary">{{ $root['root_value'] }}</span>
                                    <span class="text-sm opacity-70">{{ $root['metadata']['meaning_en'] ?? '' }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($suggestions['sentences']))
                <div class="p-2">
                    <h3 class="text-xs font-semibold text-base-content/50 uppercase px-2 mb-1">Sentences</h3>
                    <ul class="menu menu-compact bg-base-100 w-full p-0">
                        @foreach($suggestions['sentences'] as $sentence)
                            <li>
                                <a wire:click="$set('query', '{{ $sentence['sentence_text'] }}'); executeSearch()" class="block hover:bg-base-200">
                                    <div class="font-arabic text-lg leading-relaxed text-right w-full line-clamp-1 truncate block" dir="rtl">
                                        {{ $sentence['sentence_text'] }}
                                    </div>
                                    @if(isset($sentence['metadata']['surah_name']))
                                        <div class="text-xs text-base-content/60 mt-1 flex gap-2">
                                            <span class="badge badge-accent badge-outline badge-xs">Quran</span>
                                            <span>Surah {{ $sentence['metadata']['surah_name'] }}</span>
                                        </div>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

        </div>
    @endif
</div>
