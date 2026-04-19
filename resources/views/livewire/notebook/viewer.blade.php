<div class="notebook-wrapper">
    @if($notebook->view_mode === 'presentation')
        <div class="presentation-mode bg-black h-screen w-full flex items-center justify-center p-12">
             {{-- Simplified slide rendering for now --}}
             <div class="slides-container prose prose-invert lg:prose-2xl">
                 @foreach($this->slides as $slide)
                     <div class="slide hidden first:block">
                         {!! (new \App\Services\Notebook\LiveEmbedRenderer())->render($slide) !!}
                     </div>
                 @endforeach
             </div>
             <div class="fixed bottom-4 right-4 flex gap-4">
                 <button class="btn btn-circle btn-outline btn-sm">←</button>
                 <button class="btn btn-circle btn-outline btn-sm">→</button>
             </div>
        </div>
    @else
        <div class="article-mode max-w-4xl mx-auto py-12 px-6">
            <header class="mb-12 border-b pb-8">
                <h1 class="text-5xl font-extrabold tracking-tight mb-4">{{ $notebook->title }}</h1>
                <div class="flex items-center gap-4 text-sm opacity-60">
                    <span>By {{ $notebook->user->name }}</span>
                    <span>•</span>
                    <span>{{ $notebook->updated_at->format('M d, Y') }}</span>
                    @if($notebook->is_published)
                        <span class="badge badge-success badge-sm">Published</span>
                    @endif
                </div>
            </header>

            <div class="prose prose-lg dark:prose-invert max-w-none">
                {!! $this->rendered_content !!}
            </div>
        </div>
    @endif
</div>