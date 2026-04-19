<?php
use App\Services\SearchService;
use Livewire\Component;
use Illuminate\Support\Facades\Redis;
use Livewire\Attributes\On;

new class extends Component
{
    public int $activeTab = 0;
    public string $paneWidth = '50%';
    public string $searchQuery = '';
    public array $results = [];
    public array $searchStats = ['processing_time_ms' => 0, 'total_clusters' => 0];
    public mixed $taxonomies = [];
    
    // Selection state
    public ?string $selectedSentenceId = null;
    public ?array $selectedSentence = null;
    public ?array $selectedRoot = null;

    protected function getUserId(): string
    {
        return auth()->id() ?? session()->getId();
    }

    public function mount()
    {
        $this->taxonomies = \App\Models\Taxonomy::whereNull('parent_id')->with('children')->get();

        try {
            $userId = $this->getUserId();
            $cached = Redis::get("user_workspace_{$userId}");
            if ($cached) {
                $data = json_decode($cached, true);
                $this->activeTab = $data['activeTab'] ?? 0;
                $this->paneWidth = $data['paneWidth'] ?? '50%';
            }
        } catch (\Exception $e) {
            // Redis container might not be running right now, catch & continue
        }
    }

    #[On('scholar:results-updated')]
    public function onSearchUpdated($query, SearchService $searchService)
    {
        $this->searchQuery = $query;
        
        // Use the new Clustered Tree Search Architecture
        $treeOutput = $searchService->clusteredSearch($query);
        
        $this->results = $treeOutput['tree'];
        $this->searchStats = [
            'processing_time_ms' => $treeOutput['processing_time_ms'],
            'total_clusters' => count($treeOutput['tree'])
        ];

        // Auto-select first result from the first cluster if available
        if (!empty($this->results) && !empty($this->results[0]['documents'])) {
            $this->selectSentence($this->results[0]['documents'][0]['id']);
        }
    }

    public function selectSentence(string $id)
    {
        $this->selectedSentenceId = $id;
        $sentence = \App\Models\Sentence::with(['translations', 'words.root', 'entities'])->find($id);
        
        if ($sentence) {
            $this->selectedSentence = $sentence->toArray();
            
            // Auto-select the first root found in the sentence
            $firstWord = $sentence->words->first();
            if ($firstWord && $firstWord->root) {
                $this->selectedRoot = $firstWord->root->toArray();
                
                // Add some dynamic stats for the root
                $this->selectedRoot['mentions_count'] = \App\Models\Sentence::whereHas('words', function($q) use ($firstWord) {
                    $q->where('root_id', $firstWord->root_id);
                })->count();
            } else {
                $this->selectedRoot = null;
            }
        }
    }

    #[On('updateWorkspaceState')]
    public function updateWorkspaceState($state)
    {
        $this->activeTab = $state['activeTab'] ?? 0;
        $this->paneWidth = $state['paneWidth'] ?? '50%';

        try {
            $userId = $this->getUserId();
            Redis::set("user_workspace_{$userId}", json_encode([
                'activeTab' => $this->activeTab,
                'paneWidth' => $this->paneWidth,
                'updated_at' => now()->toDateTimeString(),
            ]));
        } catch (\Exception $e) {
            // Ignore Redis exception locally if not running
        }
    }
};
?>

<div x-data="{
        paneWidth: @entangle('paneWidth'),
        activeTab: @entangle('activeTab'),
        isDragging: false,
        startX: 0,
        startWidth: 0,
        initDrag(e) {
            this.isDragging = true;
            this.startX = e.clientX;
            this.startWidth = this.$refs.leftPane.getBoundingClientRect().width / this.$refs.container.getBoundingClientRect().width * 100;
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        },
        doDrag(e) {
            if (!this.isDragging) return;
            let offset = (e.clientX - this.startX) / this.$refs.container.getBoundingClientRect().width * 100;
            let newWidth = this.startWidth + offset;
            
            if (newWidth >= 20 && newWidth <= 80) {
                this.paneWidth = newWidth + '%';
            }
        },
        stopDrag() {
            if (this.isDragging) {
                this.isDragging = false;
                document.body.style.cursor = 'default';
                document.body.style.userSelect = 'auto';
                
                $wire.updateWorkspaceState({
                    paneWidth: this.paneWidth,
                    activeTab: this.activeTab,
                });
            }
        }
    }" 
    x-on:mousemove.window="doDrag" 
    x-on:mouseup.window="stopDrag"
    class="flex h-screen w-screen overflow-hidden bg-base-100 text-base-content" data-theme="scholar">
    
    <!-- Activity Bar (Far Left Strip) -->
    <div class="w-[50px] bg-neutral text-neutral-content flex flex-col items-center py-4 flex-shrink-0 z-20">
        <div class="tooltip tooltip-right mb-4" data-tip="Explorer">
            <button class="btn btn-square btn-ghost btn-sm text-neutral-content opacity-100">
                <x-heroicon-o-folder class="w-5 h-5"/>
            </button>
        </div>
        <div class="tooltip tooltip-right mb-4" data-tip="Search">
            <button class="btn btn-square btn-ghost btn-sm text-neutral-content opacity-50 hover:opacity-100">
                <x-heroicon-o-magnifying-glass class="w-5 h-5"/>
            </button>
        </div>
        <div class="tooltip tooltip-right mb-4" data-tip="Lexicon">
            <button class="btn btn-square btn-ghost btn-sm text-neutral-content opacity-50 hover:opacity-100">
                <x-heroicon-o-book-open class="w-5 h-5"/>
            </button>
        </div>
        
        <div class="flex-grow"></div>
        
        <div class="tooltip tooltip-right mt-4" data-tip="Settings">
            <button class="btn btn-square btn-ghost btn-sm text-neutral-content opacity-50 hover:opacity-100">
                <x-heroicon-o-cog-6-tooth class="w-5 h-5"/>
            </button>
        </div>
    </div>

    <!-- Explorer (Drawer Sidebar) -->
    <div class="w-64 bg-base-200 flex flex-col border-r border-base-300 flex-shrink-0 z-10">
        <div class="p-4 border-b border-base-300 font-semibold text-xs uppercase tracking-wider text-base-content/70">
            Explorer
        </div>
        <div class="overflow-y-auto flex-grow p-2">
            <ul class="menu menu-sm bg-base-200 w-full rounded-box">
              @foreach($taxonomies as $tax)
                <li>
                  @if($tax->children->count())
                    <details open>
                      <summary><x-heroicon-s-folder class="w-4 h-4 text-warning inline"/> {{ $tax->name }}</summary>
                      <ul>
                        @foreach($tax->children as $child)
                          <li><a><x-heroicon-o-document-text class="w-4 h-4 inline"/> {{ $child->name }}</a></li>
                        @endforeach
                      </ul>
                    </details>
                  @else
                    <a><x-heroicon-o-document-text class="w-4 h-4 inline"/> {{ $tax->name }}</a>
                  @endif
                </li>
              @endforeach
            </ul>
        </div>
    </div>

    <!-- Main Workspace (Editor Canvas format) -->
    <div class="flex-1 flex flex-col min-w-0" x-ref="container">
        <!-- Editor Tabs -->
        <div class="bg-base-200/50 flex pt-2 px-2 overflow-x-auto border-b border-base-300">
            <div class="tabs tabs-lifted">
              <a class="tab" :class="activeTab === 0 ? 'tab-active' : ''" @click="activeTab = 0">
                 <x-heroicon-o-table-cells class="w-4 h-4 mr-2 inline"/>
                 Omni-Search
              </a>
              <a class="tab" :class="activeTab === 1 ? 'tab-active' : ''" @click="activeTab = 1">
                 <x-heroicon-s-book-open class="w-4 h-4 mr-2 text-info inline"/>
                 Al-Baqarah
              </a>
              <a class="tab" :class="activeTab === 2 ? 'tab-active' : ''" @click="activeTab = 2">
                 <x-heroicon-o-cube-transparent class="w-4 h-4 mr-2 text-success inline"/>
                 Root: ع ل م
              </a>
            </div>
        </div>

        <!-- Split Pane Content Area -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Left Pane (Search Results / Reading Canvas) -->
            <div x-ref="leftPane" :style="'width: ' + paneWidth" class="h-full overflow-y-auto min-w-[20%] p-4 bg-base-100 @container">
                
                <!-- Zone 1: Omni Bar -->
                <div class="mb-6 relative z-10">
                    <livewire:scholar.search-panel />
                </div>

                <!-- Breadcrumbs -->
                <div class="text-sm breadcrumbs mb-4 text-base-content/70 px-2 flex justify-between items-center">
                  <ul>
                    <li><a><x-heroicon-o-magnifying-glass class="w-4 h-4 mr-1 inline"/> {{ $searchQuery ?: 'Recent Searches' }}</a></li>
                    @if($searchQuery)
                        <li><span class="inline-flex items-center"><x-heroicon-s-rectangle-stack class="w-4 h-4 mr-1 text-primary inline"/> Clusters: {{ $searchStats['total_clusters'] }}</span></li>
                    @endif
                  </ul>
                  @if($searchQuery)
                    <div class="text-[10px] uppercase tracking-widest opacity-40 font-mono">
                        Generated in {{ $searchStats['processing_time_ms'] }}ms | Zero LLm Architecture
                    </div>
                  @endif
                </div>

                <!-- Results Feed (Knowledge Tree Architecture) -->
                <div class="space-y-4">
                    @forelse($results as $cluster)
                        <div x-data="{ open: true }" class="collapse collapse-arrow bg-base-200 border border-base-300 shadow-sm rounded-xl overflow-visible">
                            <input type="checkbox" x-model="open" /> 
                            <div class="collapse-title flex items-center justify-between pr-12">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-primary/10 rounded-lg">
                                        <x-heroicon-s-folder class="w-5 h-5 text-primary" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-lg leading-tight">{{ $cluster['node_title'] }}</h3>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs font-semibold opacity-60">{{ $cluster['children_count'] }} Related Texts</span>
                                            @foreach($cluster['common_entities'] as $tag)
                                                <span class="badge badge-primary badge-outline badge-xs text-[10px]">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="collapse-content px-0">
                                <div class="grid grid-cols-1 @4xl:grid-cols-2 gap-3 p-3 border-t border-base-300 bg-base-100/30">
                                    @foreach($cluster['documents'] as $doc)
                                        <div 
                                            wire:click="selectSentence('{{ $doc['id'] }}')"
                                            class="card bg-base-100 shadow-sm border-2 @if($selectedSentenceId === $doc['id']) border-primary @else border-base-200 @endif w-full hover:border-primary transition-all cursor-pointer group">
                                            <div class="card-body p-3 relative">
                                                <div class="text-right text-base font-arabic mb-2 leading-relaxed" dir="rtl">
                                                    {{ $doc['text'] }}
                                                </div>
                                                <p class="text-[11px] leading-tight opacity-70 line-clamp-2">
                                                    {{ $doc['translation'] }}
                                                </p>
                                                
                                                @if(isset($doc['metadata']['ayah_number']))
                                                    <div class="mt-2 flex justify-end">
                                                        <span class="text-[9px] opacity-40 font-mono">Verse {{ $doc['metadata']['ayah_number'] }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        @if($searchQuery)
                            <div class="py-20 text-center">
                                <x-heroicon-o-face-frown class="w-12 h-12 mx-auto mb-4 opacity-20"/>
                                <p class="text-base-content/50 italic">No clusters found for "{{ $searchQuery }}".</p>
                            </div>
                        @else
                            <!-- Placeholder -->
                            <div class="flex flex-col items-center justify-center py-32 opacity-20">
                                <x-heroicon-o-magnifying-glass-circle class="w-24 h-24 mb-4"/>
                                <p class="text-xl font-bold">Knowledge Discovery Mode</p>
                                <p class="text-sm">Search to synthesize results into a Knowledge Tree.</p>
                            </div>
                        @endif
                    @endforelse
                </div>

            </div>

            <!-- Resize Drag Handle (Divider) -->
            <div class="w-1 cursor-col-resize hover:bg-primary/50 bg-base-300 h-full flex-shrink-0 select-none z-10 transition-colors" @mousedown="initDrag"></div>

            <!-- Right Pane (Intelligence Panel / Context) -->
            <div class="flex-1 min-w-[20%] bg-base-100 h-full flex flex-col border-l border-base-200">
                <!-- Top Tabs -->
                <div class="tabs tabs-bordered bg-base-200/30 pt-2 px-2 whitespace-nowrap overflow-x-auto flex-nowrap">
                  <a class="tab tab-active"><x-heroicon-s-light-bulb class="w-4 h-4 mr-2 text-warning inline"/> التدبر (Tadabbur)</a>
                  <a class="tab"><x-heroicon-o-clipboard-document-check class="w-4 h-4 mr-2 inline"/> العمل (Action)</a>
                  <a class="tab"><x-heroicon-o-book-open class="w-4 h-4 mr-2 inline"/> التفاسير (Tafsir)</a>
                </div>
                
                <div class="flex-1 p-6 overflow-y-auto w-full">
                    @if($selectedRoot)
                        <div class="flex justify-between items-center mb-4">
                          <h3 class="text-lg font-bold font-arabic">{{ $selectedRoot['root_value'] }}</h3>
                          <button class="btn btn-xs btn-primary">Search this Root</button>
                        </div>
                        
                        <div class="stats stats-vertical xl:stats-horizontal shadow bg-base-100 border border-base-200 w-full mb-6 max-w-full overflow-hidden">
                          <div class="stat p-3">
                            <div class="stat-title text-xs">Quran Mentions</div>
                            <div class="stat-value text-xl text-primary">{{ $selectedRoot['mentions_count'] ?? 0 }}</div>
                          </div>
                          <div class="stat p-3">
                            <div class="stat-title text-xs">Lexicon Form</div>
                            <div class="stat-value text-sm font-arabic mt-1">{{ $selectedRoot['metadata']['form'] ?? 'Standard' }}</div>
                          </div>
                        </div>
    
                        <div class="prose prose-sm text-base-content max-w-none">
                            <h4 class="text-base font-bold m-0 mb-1">Semantic Concept</h4>
                            <p class="mt-0">{{ $selectedRoot['metadata']['description'] ?? 'No extended lexicon definition available for this root.' }}</p>
                            @if(isset($selectedSentence['metadata']['tadabbur']))
                                <div class="divider my-4"></div>
                                <h4 class="text-base font-bold m-0 mb-2">Tadabbur Directives</h4>
                                <blockquote class="pl-4 border-l-4 border-primary italic m-0">
                                    {{ $selectedSentence['metadata']['tadabbur'] }}
                                </blockquote>
                            @endif
                        </div>
                    @elseif($selectedSentence)
                        <div class="text-center py-20 opacity-50">
                            <x-heroicon-o-beaker class="w-12 h-12 mx-auto mb-4"/>
                            <p>Select a result with recognized linguistic roots to view detailed lexicon analysis.</p>
                        </div>
                    @else
                        <div class="text-center py-20 opacity-30">
                            <x-heroicon-o-cursor-arrow-ripple class="w-12 h-12 mx-auto mb-4"/>
                            <p>Select a search result to begin in-depth study.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>