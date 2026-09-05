@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('app.reports.pnl.line') }}</th>
                <th class="numeric">{{ __('app.reports.pnl.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ __('app.reports.pnl.total_sales') }}</strong></td>
                <td class="numeric"><strong>{{ \App\Support\Money::format($totalSales) }}</strong></td>
            </tr>
            @if ($totalTax > 0)
                <tr>
                    <td>{{ __('app.reports.pnl.tax_collected') }}</td>
                    <td class="numeric">({{ \App\Support\Money::format($totalTax) }})</td>
                </tr>
                <tr>
                    <td><strong>{{ __('app.reports.pnl.net_sales') }}</strong></td>
                    <td class="numeric"><strong>{{ \App\Support\Money::format($netSales) }}</strong></td>
                </tr>
            @endif
            <tr>
                <td colspan="2">{{ __('app.reports.pnl.expenses_heading') }}</td>
            </tr>
            @forelse ($expensesByCategory as $expense)
                <tr>
                    <td>&nbsp;&nbsp;{{ $expense['category'] }}</td>
                    <td class="numeric">({{ \App\Support\Money::format($expense['total']) }})</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">{{ __('app.reports.pnl.no_expenses') }}</td>
                </tr>
            @endforelse
            <tr>
                <td><strong>{{ __('app.reports.pnl.total_expenses') }}</strong></td>
                <td class="numeric"><strong>({{ \App\Support\Money::format($totalExpenses) }})</strong></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td>{{ __('app.reports.pnl.net_profit') }}</td>
                <td class="numeric">{{ \App\Support\Money::format($netProfit) }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
