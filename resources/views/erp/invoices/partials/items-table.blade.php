@php $flags = $data['column_flags']; @endphp
<table class="items-table">
    <thead>
        <tr>
            @if ($flags['sku']) <th>{{ $data['labels']['sku'] }}</th> @endif
            @if ($flags['description']) <th>{{ $data['labels']['description'] }}</th> @endif
            @if ($flags['variation']) <th>{{ $data['labels']['variation'] }}</th> @endif
            @if ($flags['quantity']) <th class="num">{{ $data['labels']['quantity'] }}</th> @endif
            @if ($flags['unit_price']) <th class="num">{{ $data['labels']['unit_price'] }}</th> @endif
            @if ($flags['discount']) <th class="num">{{ $data['labels']['discount'] }}</th> @endif
            @if ($flags['tax']) <th class="num">{{ $data['labels']['tax'] }}</th> @endif
            @if ($flags['line_total']) <th class="num">{{ $data['labels']['line_total'] }}</th> @endif
        </tr>
    </thead>
    <tbody>
        @forelse ($data['items'] as $item)
            <tr>
                @if ($flags['sku']) <td>{{ $item['sku'] }}</td> @endif
                @if ($flags['description']) <td>{{ $item['description'] }}</td> @endif
                @if ($flags['variation']) <td>{{ $item['variation'] }}</td> @endif
                @if ($flags['quantity']) <td class="num">{{ $item['quantity'] }}</td> @endif
                @if ($flags['unit_price']) <td class="num">{{ $item['unit_price'] }}</td> @endif
                @if ($flags['discount']) <td class="num">{{ $item['discount'] }}</td> @endif
                @if ($flags['tax']) <td class="num">{{ $item['tax'] }}</td> @endif
                @if ($flags['line_total']) <td class="num">{{ $item['line_total'] }}</td> @endif
            </tr>
        @empty
            <tr>
                <td colspan="8">—</td>
            </tr>
        @endforelse
    </tbody>
</table>
