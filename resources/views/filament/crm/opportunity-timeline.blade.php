<div class="space-y-3">
    <h3 class="text-lg font-semibold">{{ __('dashboard.crm.timeline.title') }}</h3>
    @forelse ($events as $event)
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
            <div class="flex items-center justify-between gap-2">
                <span class="font-medium text-sm">{{ $event['label'] }}</span>
                <span class="text-xs text-gray-500">{{ $event['at']?->format('Y-m-d H:i') }}</span>
            </div>
            @if (! empty($event['description']))
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $event['description'] }}</p>
            @endif
            @if (! empty($event['user']))
                <p class="text-xs text-gray-500 mt-1">{{ $event['user'] }}</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500">{{ __('dashboard.crm.notes.empty') }}</p>
    @endforelse
</div>
