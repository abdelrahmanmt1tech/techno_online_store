<x-filament-panels::page>
    @php($summary = $this->summary)

    <x-filament-widgets::widgets
        class="mb-6"
        :columns="1"
        :widgets="$this->getHeaderWidgets()"
        :data="$this->getHeaderWidgetsData()"
    />

    @if (! empty($summary['rankings']['by_won']))
        <x-filament::section class="mb-6">
            <x-slot name="heading">{{ __('dashboard.crm.reports.employee.rankings.title') }}</x-slot>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="mb-2 text-sm font-medium">{{ __('dashboard.crm.reports.employee.rankings.by_won') }}</p>
                    <ul class="list-inside list-disc text-sm">
                        @foreach ($summary['rankings']['by_won'] as $name)
                            <li>{{ $name }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium">{{ __('dashboard.crm.reports.employee.rankings.by_agreed_amount') }}</p>
                    <ul class="list-inside list-disc text-sm">
                        @foreach ($summary['rankings']['by_agreed_amount'] as $name)
                            <li>{{ $name }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium">{{ __('dashboard.crm.reports.employee.rankings.by_conversion') }}</p>
                    <ul class="list-inside list-disc text-sm">
                        @foreach ($summary['rankings']['by_conversion'] as $name)
                            <li>{{ $name }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium">{{ __('dashboard.crm.reports.employee.rankings.by_follow_up_completion') }}</p>
                    <ul class="list-inside list-disc text-sm">
                        @foreach ($summary['rankings']['by_follow_up_completion'] as $name)
                            <li>{{ $name }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </x-filament::section>
    @endif

    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
