<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class PackageDashboardsWidget extends Widget
{
    protected string $view = 'filament.widgets.package-dashboards-widget';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    public function getDashboards(): array
    {
        return [
            [
                'name' => 'Horizon',
                'url' => url('horizon'),
                'icon' => 'heroicon-o-server-stack',
            ],
            [
                'name' => 'Telescope',
                'url' => url('telescope'),
                'icon' => 'heroicon-o-presentation-chart-line',
            ],
            [
                'name' => 'Scholar IDE',
                'url' => url('scholar'),
                'icon' => 'heroicon-o-academic-cap',
            ],
            [
                'name' => 'Meilisearch',
                'url' => 'http://'.request()->getHost().':7700',
                'icon' => 'heroicon-o-magnifying-glass',
            ],
            [
                'name' => 'Prefect',
                'url' => 'http://'.request()->getHost().':4200',
                'icon' => 'heroicon-o-rocket-launch',
            ],
        ];
    }
}
