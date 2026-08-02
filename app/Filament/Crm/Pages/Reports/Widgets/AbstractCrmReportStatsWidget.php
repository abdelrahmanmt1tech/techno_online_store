<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Livewire\Attributes\Reactive;

/**
 * Base for CRM report KPI widgets. The report page supplies the already branch-scoped + filtered
 * `summary` array (flat prop, shared by every header widget). `#[Reactive]` keeps the widget in
 * sync when the page filters change. Subclasses implement getStats() using the helpers below.
 */
abstract class AbstractCrmReportStatsWidget extends StatsOverviewWidget
{
    protected int|array|null $columns = 4;

    protected int|string|array $columnSpan = 'full';

    /** @var array<string, mixed> */
    #[Reactive]
    public array $summary = [];

    protected function intValue(string $key): string
    {
        return number_format((int) ($this->summary[$key] ?? 0));
    }

    protected function money(string $key): string
    {
        return number_format((float) ($this->summary[$key] ?? 0), 2);
    }

    protected function percent(string $key): string
    {
        return number_format((float) ($this->summary[$key] ?? 0), 2).'%';
    }
}
