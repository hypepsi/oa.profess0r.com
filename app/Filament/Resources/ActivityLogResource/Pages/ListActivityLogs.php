<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Widgets\ActivityLogStatsWidget;
use App\Models\ActivityLog;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    // No page-level header actions — all management actions live in the table header.
    protected function getHeaderActions(): array
    {
        return [];
    }

    // Stats widget rendered above the table.
    protected function getHeaderWidgets(): array
    {
        return [
            ActivityLogStatsWidget::class,
        ];
    }

    // =========================================================================
    // Category Tabs — auto-generated from ActivityLog::getCategoryOptions().
    //
    // Adding a new category requires NO changes here.
    // Just update ActivityLog::categoryRegistry() and the tabs appear automatically.
    // =========================================================================
    public function getTabs(): array
    {
        // Single aggregation query — one DB round-trip for all counts.
        $counts = ActivityLog::query()
            ->selectRaw('category, COUNT(*) as cnt')
            ->groupBy('category')
            ->pluck('cnt', 'category')
            ->toArray();

        $badge = fn (string $category): ?string =>
            ($counts[$category] ?? 0) > 0
                ? number_format($counts[$category])
                : null;

        // "All" tab always first.
        $tabs = [
            'all' => Tab::make('All')
                ->icon('heroicon-o-bars-3')
                ->badge(number_format(array_sum($counts))),
        ];

        // One tab per category — derived from the registry, no hardcoding.
        foreach (ActivityLog::getCategoryOptions() as $key => $label) {
            $tabs[$key] = Tab::make($label)
                ->icon(ActivityLog::getCategoryIcon($key))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', $key))
                ->badge($badge($key));
        }

        return $tabs;
    }
}
