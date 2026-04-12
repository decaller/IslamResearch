<?php

use Livewire\Component;
use Illuminate\Support\Facades\Redis;

new class extends Component
{
    public string $userId = 'guest';
    public int $activeTab = 0;
    public string $paneWidth = '50%'; 

    public function mount()
    {
        try {
            $cached = Redis::get("user_workspace_{$this->userId}");
            if ($cached) {
                $data = json_decode($cached, true);
                $this->activeTab = $data['activeTab'] ?? 0;
                $this->paneWidth = $data['paneWidth'] ?? '50%';
            }
        } catch (\Exception $e) {
            // Redis container might not be running right now, catch & continue
        }
    }

    #[On('updateWorkspaceState')]
    public function updateWorkspaceState($state)
    {
        $this->activeTab = $state['activeTab'] ?? 0;
        $this->paneWidth = $state['paneWidth'] ?? '50%';

        try {
            Redis::set("user_workspace_{$this->userId}", json_encode([
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
            <ul class="menu menu-xs bg-base-200 w-full rounded-box">
              <li>
                <details open>
                  <summary><x-heroicon-s-folder class="w-4 h-4 text-warning inline"/> Fiqh</summary>
                  <ul>
                    <li><a><x-heroicon-o-document-text class="w-4 h-4 inline"/> Usul Fiqh</a></li>
                    <li><a><x-heroicon-o-document-text class="w-4 h-4 inline"/> Muamalah</a></li>
                  </ul>
                </details>
              </li>
              <li>
                <details>
                  <summary><x-heroicon-s-folder class="w-4 h-4 text-warning inline"/> Tafsir</summary>
                  <ul>
                    <li><a><x-heroicon-o-document-text class="w-4 h-4 inline"/> Ibn Kathir</a></li>
                  </ul>
                </details>
              </li>
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
                <div class="navbar bg-base-100 shadow-sm border border-base-200 rounded-box mb-6 relative z-10 min-h-0 py-2">
                  <div class="flex-1">
                    <div class="join w-full flex">
                        <select class="select select-bordered join-item select-sm h-10 min-h-0 w-40 shrink-0">
                          <option>✨ Semantic</option>
                          <option>🔤 Exact Match</option>
                          <option>🌱 Root (Jidhr)</option>
                        </select>
                        <div class="relative w-full flex-1">
                            <input type="text" placeholder="Search across dimensions..." class="input input-bordered join-item w-full h-10 min-h-0" />
                            <kbd class="kbd kbd-sm absolute right-3 top-2">⌘K</kbd>
                        </div>
                        <button class="btn btn-primary join-item btn-sm h-10 min-h-0">Search</button>
                    </div>
                  </div>
                </div>

                <!-- Breadcrumbs -->
                <div class="text-sm breadcrumbs mb-4 text-base-content/70 px-2">
                  <ul>
                    <li><a><x-heroicon-o-magnifying-glass class="w-4 h-4 mr-1 inline"/> Zakat Flow</a></li>
                    <li><a><x-heroicon-o-book-open class="w-4 h-4 mr-1 inline"/> Bukhari #142</a></li>
                    <li><span class="inline-flex items-center"><x-heroicon-s-cube-transparent class="w-4 h-4 mr-1 text-success inline"/> Root: ز ك و</span></li>
                  </ul>
                </div>

                <!-- Example Cards Container -->
                <div class="grid grid-cols-1 @4xl:grid-cols-2 gap-4">
                    <div class="card bg-base-100 shadow border border-base-200 w-full hover:border-primary transition-colors cursor-pointer group">
                      <div class="card-body p-4 relative pb-10">
                        <div class="flex justify-between items-start mb-4">
                            <h2 class="card-title text-sm"><span class="badge badge-error badge-sm">Hadith</span> Bukhari #142</h2>
                            <button class="btn btn-ghost btn-xs text-base-content/50 hover:text-primary p-0 h-auto min-h-0">
                                <x-heroicon-o-bookmark class="w-4 h-4 inline"/>
                            </button>
                        </div>
                        <p class="text-sm leading-relaxed mb-4 text-left" dir="ltr">And establish prayer and give <mark class="bg-warning/30 text-warning-content rounded px-1">zakah</mark>, and whatever good you put forward for yourselves - you will find it with Allah...</p>
                        <p class="text-right text-lg font-arabic mb-4" dir="rtl">وَأَقِيمُوا الصَّلَاةَ وَآتُوا <mark class="bg-warning/30 text-warning-content rounded px-1">الزَّكَاةَ</mark> ۚ وَمَا تُقَدِّمُوا لِأَنفُسِكُم مِّنْ خَيْرٍ تَجِدُوهُ عِندَ اللَّهِ</p>
                      </div>
                      <div class="absolute bottom-0 w-full h-8 opacity-0 group-hover:opacity-100 transition-opacity bg-base-200 px-4 border-t border-base-200 text-xs text-base-content/50 flex items-center justify-between rounded-b-xl">
                          <span>Relevance: 98%</span>
                          <input type="range" min="0" max="100" value="98" class="range range-xs range-primary w-24" />
                      </div>
                    </div>

                    <!-- Example loading skeleton -->
                    <div class="card shadow border border-base-200 bg-base-100 w-full p-4">
                        <div class="flex items-center gap-4 mb-4">
                          <div class="skeleton h-6 w-24"></div>
                          <div class="skeleton h-4 w-12 ml-auto"></div>
                        </div>
                        <div class="skeleton h-4 w-full mb-2"></div>
                        <div class="skeleton h-4 w-full mb-2"></div>
                        <div class="skeleton h-4 w-3/4"></div>
                        
                        <div class="flex justify-end mt-4">
                            <div class="skeleton h-12 w-full"></div>
                        </div>
                    </div>
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
                    <div class="flex justify-between items-center mb-4">
                      <h3 class="text-lg font-bold font-arabic">الزَّكَاةَ - Root: ز ك و</h3>
                      <button class="btn btn-xs btn-primary">Search this Root</button>
                    </div>
                    
                    <div class="stats stats-vertical xl:stats-horizontal shadow bg-base-100 border border-base-200 w-full mb-6 max-w-full overflow-hidden">
                      <div class="stat p-3">
                        <div class="stat-title text-xs">Quran Mentions</div>
                        <div class="stat-value text-xl text-primary">32</div>
                      </div>
                      <div class="stat p-3">
                        <div class="stat-title text-xs">Hadith Mentions</div>
                        <div class="stat-value text-xl">1,240</div>
                      </div>
                      <div class="stat p-3">
                        <div class="stat-title text-xs">Lexicon Form</div>
                        <div class="stat-value text-sm font-arabic mt-1">Noun</div>
                      </div>
                    </div>

                    <div class="prose prose-sm text-base-content max-w-none">
                        <h4 class="text-base font-bold m-0 mb-1">Semantic Concept</h4>
                        <p class="mt-0">The root <span class="badge badge-outline shadow-sm font-arabic mx-1">ز ك و</span> indicates purification, growth, and blessing. It is intricately connected to the linguistic flow of wealth purification...</p>
                        <div class="divider my-4"></div>
                        <h4 class="text-base font-bold m-0 mb-2">Tadabbur Directives</h4>
                        <blockquote class="pl-4 border-l-4 border-primary italic m-0">Look closely at how Salah is paired with Zakah in this verse. It balances physical dedication with societal financial cleansing.</blockquote>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>