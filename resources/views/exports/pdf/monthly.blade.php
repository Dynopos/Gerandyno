@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('app.reports.monthly.month') }}</th>
                <th class="numeric">{{ __('app.reports.monthly.transactions') }}</th>
                <th class="numeric">{{ __('app.reports.monthly.total_sales_col') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($months as $month)
                <tr>
                    <td>{{ $month['label'] }}</td>
                    <td class="numeric">{{ number_format($month['transactions']) }}</td>
                    <td class="numeric">{{ \App\Support\Money::format($month['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>{{ __('app.reports.monthly.total_sales_col') }}</td>
                <td class="numeric">{{ number_format($months->sum('transactions')) }}</td>
                <td class="numeric">{{ \App\Support\Money::format($months->sum('total')) }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
