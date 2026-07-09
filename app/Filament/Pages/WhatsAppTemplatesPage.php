<?php

namespace App\Filament\Pages;

use App\Filament\Shared\WhatsApp\Actions\SyncWhatsAppTemplatesAction;
use App\Filament\Shared\WhatsApp\Concerns\ChecksWhatsAppPermissions;
use App\Filament\Shared\WhatsApp\Tables\WhatsAppTemplatesTable;
use App\Models\Tenant;
use App\Models\Tenant\WhatsAppTemplate;
use App\WhatsApp\Services\WhatsAppTenantContextService;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;

class WhatsAppTemplatesPage extends Page implements HasTable
{
    use ChecksWhatsAppPermissions;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?int $navigationSort = 42;

    protected string $view = 'filament.pages.whatsapp-templates';

    public ?string $selectedTenantId = null;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.whatsapp_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.whatsapp_templates');
    }

    public static function canAccess(): bool
    {
        return static::canWhatsAppPermission('whatsapp.view_templates', 'whatsapp.platform.view_all_templates');
    }

    public function mount(): void
    {
        if (filled($this->selectedTenantId)) {
            $this->initializeTenant();
        }
    }

    public function hydrate(): void
    {
        if (filled($this->selectedTenantId)) {
            $this->initializeTenant();
        }
    }

    public function dehydrate(): void
    {
        $this->endTenantContext();
    }

    public function updatedSelectedTenantId(): void
    {
        if (blank($this->selectedTenantId)) {
            $this->endTenantContext();
            $this->resetTable();

            return;
        }

        $this->initializeTenant();
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return WhatsAppTemplatesTable::configure($table)
            ->headerActions([
                SyncWhatsAppTemplatesAction::make(
                    fn (): bool => filled($this->selectedTenantId)
                        && tenancy()->initialized
                        && (bool) Auth::user()?->can('whatsapp.platform.manage_all_templates'),
                )->after(fn () => $this->resetTable()),
                CreateAction::make()
                    ->url(fn () => $this->selectedTenantId
                        ? route('filament.tenant.resources.whatsapp-templates.create', ['tenant' => tenant()?->domains()->first()?->domain])
                        : null)
                    ->visible(false),
            ]);
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        if (blank($this->selectedTenantId) || ! tenancy()->initialized) {
            return null;
        }

        return WhatsAppTemplate::query();
    }

    public function getTenantsProperty()
    {
        return Tenant::query()->orderBy('name')->get();
    }

    protected function initializeTenant(): void
    {
        if (blank($this->selectedTenantId)) {
            $this->endTenantContext();

            return;
        }

        $tenant = Tenant::query()->find($this->selectedTenantId);

        if ($tenant === null) {
            $this->endTenantContext();

            return;
        }

        $context = app(WhatsAppTenantContextService::class);
        $context->end();
        $context->initializeForTenant($tenant);
    }

    protected function endTenantContext(): void
    {
        app(WhatsAppTenantContextService::class)->end();
    }
}
