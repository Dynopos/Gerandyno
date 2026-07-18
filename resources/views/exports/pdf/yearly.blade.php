@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('app.reports.yearly.year') }}</th>
                <th class="numeric">{{ __('app.reports.yearly.transactions') }}</th>
                <th class="numeric">{{ __('app.reports.yearly.total_sales') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($years as $year)
                <tr>
                    <td>{{ $year['label'] }}</td>
                    <td class="numeric">{{ number_format($year['transactions']) }}</td>
                    <td class="numeric">{{ \App\Support\Money::format($year['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">{{ __('app.reports.yearly.no_data') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if ($years->isNotEmpty())
            <tfoot>
                <tr>
                    <td>{{ __('app.reports.yearly.total_sales') }}</td>
                    <td class="numeric">{{ number_format($years->sum('transactions')) }}</td>
                    <td class="numeric">{{ \App\Support\Money::format($years->sum('total')) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
