<?php

namespace App\Livewire\Notebook;

use App\Models\UserNotebook;
use App\Services\Notebook\LiveEmbedRenderer;
use Livewire\Component;

class NotebookViewer extends Component
{
    public UserNotebook $notebook;

    public function mount(UserNotebook $notebook)
    {
        $this->notebook = $notebook;
    }

    public function getRenderedContentProperty()
    {
        $renderer = new LiveEmbedRenderer;

        return $renderer->render($this->notebook->content_md ?? '');
    }

    public function getSlidesProperty()
    {
        if ($this->notebook->view_mode !== 'presentation') {
            return [];
        }

        return explode('---', $this->notebook->content_md);
    }

    public function render()
    {
        return view('livewire.notebook.viewer')
            ->layout('layouts.app'); // Assuming a default app layout exists
    }
}
