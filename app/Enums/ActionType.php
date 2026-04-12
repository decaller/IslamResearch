<?php

namespace App\Enums;

enum ActionType: string
{
    case SearchQuery = 'search_query';
    case ViewItem = 'view_item';
    case ExploreRoot = 'explore_root';
    case ApplyFilter = 'apply_filter';
    case OpenTab = 'open_tab';
    case CloseTab = 'close_tab';
    case SplitPane = 'split_pane';
}
