<?php

namespace App\Filament\Pages;

use App\Models\MetaIntegrationResetRun;
use App\Support\MetaReset\MetaIntegrationResetRegistry;
use App\Support\MetaReset\MetaIntegrationResetService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MetaIntegrationsReset extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ExclamationTriangle;

    protected static ?string $slug = 'meta-integrations-reset';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.meta-integrations-reset';

    public string $scope = '';

    public string $confirmationPhrase = '';

    public bool $confirmChecked = false;

    /** @var array<string, mixed>|null */
    public ?array $preview = null;

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.messaging_operations_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.meta_reset_nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('dashboard.meta_reset_title');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = Auth::guard('admin')->user();

        if ($user === null) {
            return false;
        }

        if (config('app.bypass_permissions')) {
            return true;
        }

        return $user->can(config('meta.integration_reset_permission', 'meta.integrations.reset'));
    }

    public function updatedScope(): void
    {
        $this->invalidatePreview();
    }

    public function runPreview(): void
    {
        $this->result = null;

        try {
            if ($this->scope === '') {
                throw new \RuntimeException(__('dashboard.meta_reset_scope_required'));
            }

            $this->preview = app(MetaIntegrationResetService::class)->preview($this->scope);
            $this->confirmationPhrase = '';
            $this->confirmChecked = false;

            Notification::make()
                ->title(__('dashboard.meta_reset_preview_ready'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->preview = null;

            Notification::make()
                ->title(__('dashboard.meta_reset_preview_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function canExecute(): bool
    {
        if ($this->preview === null || $this->scope === '') {
            return false;
        }

        if (! $this->confirmChecked) {
            return false;
        }

        $phrase = app(MetaIntegrationResetService::class)->confirmationPhrase();

        if (trim($this->confirmationPhrase) !== $phrase) {
            return false;
        }

        $expiresAt = $this->preview['expires_at'] ?? null;

        if (! is_string($expiresAt) || now()->greaterThan($expiresAt)) {
            return false;
        }

        return true;
    }

    public function executeReset(): void
    {
        try {
            if (! $this->canExecute()) {
                throw new \RuntimeException(__('dashboard.meta_reset_execute_blocked'));
            }

            $token = (string) ($this->preview['token'] ?? '');

            $this->result = app(MetaIntegrationResetService::class)->execute(
                $this->scope,
                $token,
                $this->confirmationPhrase,
            );

            $this->invalidatePreview();
            $this->confirmationPhrase = '';
            $this->confirmChecked = false;

            $status = $this->result['status'] ?? 'failed';

            Notification::make()
                ->title(__('dashboard.meta_reset_execute_done'))
                ->body(__('dashboard.meta_reset_status_'.$status))
                ->{$status === 'completed' ? 'success' : 'warning'}()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('dashboard.meta_reset_execute_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @return Collection<int, MetaIntegrationResetRun>
     */
    public function getRecentRunsProperty(): Collection
    {
        return MetaIntegrationResetRun::query()
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    public function getConfirmationPhraseProperty(): string
    {
        return app(MetaIntegrationResetService::class)->confirmationPhrase();
    }

    public function getPreservedExamplesProperty(): array
    {
        return app(MetaIntegrationResetRegistry::class)->preservedExamples();
    }

    public function getFeatureEnabledProperty(): bool
    {
        return app(MetaIntegrationResetService::class)->isEnabled();
    }

    protected function invalidatePreview(): void
    {
        $this->preview = null;
        $this->confirmChecked = false;
        $this->confirmationPhrase = '';
    }
}
