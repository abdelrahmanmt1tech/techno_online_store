<?php

namespace App\Observers\Tenant;

use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\Actions\SyncWhatsAppNumberRegistryAction;

class WhatsAppNumberObserver
{
    public function __construct(
        protected SyncWhatsAppNumberRegistryAction $syncAction,
    ) {}

    public function created(WhatsAppNumber $number): void
    {
        $this->syncAction->execute($number);
    }

    public function updated(WhatsAppNumber $number): void
    {
        $this->syncAction->execute($number);
    }

    public function deleted(WhatsAppNumber $number): void
    {
        $this->syncAction->deleteFromRegistry($number);
    }

    public function restored(WhatsAppNumber $number): void
    {
        $this->syncAction->execute($number);
    }

    public function forceDeleted(WhatsAppNumber $number): void
    {
        $this->syncAction->deleteFromRegistry($number);
    }
}
