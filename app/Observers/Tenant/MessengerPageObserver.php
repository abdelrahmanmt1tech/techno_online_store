<?php

namespace App\Observers\Tenant;

use App\Messenger\Actions\SyncMessengerPageRegistryAction;
use App\Models\Tenant\MessengerPage;

class MessengerPageObserver
{
    public function __construct(
        protected SyncMessengerPageRegistryAction $syncAction,
    ) {}

    public function created(MessengerPage $page): void
    {
        $this->syncAction->execute($page);
    }

    public function updated(MessengerPage $page): void
    {
        $this->syncAction->execute($page);
    }

    public function deleted(MessengerPage $page): void
    {
        $this->syncAction->deleteFromRegistry($page);
    }

    public function restored(MessengerPage $page): void
    {
        $this->syncAction->execute($page);
    }

    public function forceDeleted(MessengerPage $page): void
    {
        $this->syncAction->deleteFromRegistry($page);
    }
}
