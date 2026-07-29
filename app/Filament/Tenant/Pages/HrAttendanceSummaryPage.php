<?php

namespace App\Filament\Tenant\Pages;

use App\Enums\Hr\AttendanceStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\HrAttendanceRecord;
use App\Models\Tenant\HrEmployee;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class HrAttendanceSummaryPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?string $slug = 'hr-attendance-summary';

    protected static ?int $navigationSort = 520;

    protected string $view = 'filament.tenant.pages.hr-attendance-summary';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('hr.nav.hr');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr.reports.attendance_summary');
    }

    public function getTitle(): string|Htmlable
    {
        return __('hr.reports.attendance_summary');
    }

    public static function canAccess(): bool
    {
        return Auth::user()->can('hr.reports.view');
    }

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
            'employee_id' => null,
            'branch_id' => null,
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('form'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make(__('hr.reports.filters'))
                        ->columns(4)
                        ->schema([
                            DatePicker::make('start_date')
                                ->label(__('hr.fields.start_date'))
                                ->required()
                                ->live(),
                            DatePicker::make('end_date')
                                ->label(__('hr.fields.end_date'))
                                ->required()
                                ->live(),
                            Select::make('employee_id')
                                ->label(__('hr.fields.employee'))
                                ->options(fn () => HrEmployee::query()->orderBy('full_name')->pluck('full_name', 'id'))
                                ->searchable()
                                ->native(false)
                                ->nullable()
                                ->live(),
                            Select::make('branch_id')
                                ->label(__('hr.fields.branch'))
                                ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->native(false)
                                ->nullable()
                                ->live(),
                        ]),
                ])
                    ->livewireSubmitHandler('noop')
                    ->statePath('data'),
            ])
            ->statePath('data');
    }

    public function noop(): void
    {
        // Filters update via live(); no submit action required.
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getRowsProperty(): Collection
    {
        $start = $this->data['start_date'] ?? now()->startOfMonth()->toDateString();
        $end = $this->data['end_date'] ?? now()->toDateString();
        $employeeId = $this->data['employee_id'] ?? null;
        $branchId = $this->data['branch_id'] ?? null;

        $query = HrAttendanceRecord::query()
            ->with('employee.branch')
            ->whereBetween('attendance_date', [$start, $end]);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($branchId) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $branchId));
        }

        return $query->get()
            ->groupBy('employee_id')
            ->map(function (Collection $records) {
                /** @var HrEmployee|null $employee */
                $employee = $records->first()?->employee;

                return [
                    'employee' => $employee?->full_name ?? '—',
                    'employee_number' => $employee?->employee_number ?? '—',
                    'branch' => $employee?->branch?->name ?? '—',
                    'present_days' => $records->whereIn('status', [
                        AttendanceStatus::Present,
                        AttendanceStatus::Late,
                        AttendanceStatus::Manual,
                        AttendanceStatus::Incomplete,
                    ])->count(),
                    'absent_days' => $records->where('status', AttendanceStatus::Absent)->count(),
                    'late_days' => $records->where('status', AttendanceStatus::Late)->count(),
                    'total_late_minutes' => (int) $records->sum('late_minutes'),
                ];
            })
            ->values();
    }
}
