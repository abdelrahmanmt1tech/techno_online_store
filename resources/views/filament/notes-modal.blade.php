@php
    $record = $record ?? ($order ?? null);
@endphp
<div class="space-y-4">
    @forelse ($notes as $note)
        <div class="p-3 rounded bg-gray-100 dark:bg-gray-800">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                {{ $note->note }}
                @if ($note->is_private)
                    <span class="ml-1 text-xs text-amber-600 dark:text-amber-400">({{ __('crm.notes.private') }})</span>
                @endif
                <p><small>{{ $note->createdBy?->name }}</small></p>
            </div>
            <div class="text-xs text-gray-500 mt-1">{{ $note->created_at?->format('Y-m-d H:i') }}</div>
        </div>
    @empty
        <div class="text-sm text-gray-500">{{ __('crm.notes.empty') }}</div>
    @endforelse
</div>
