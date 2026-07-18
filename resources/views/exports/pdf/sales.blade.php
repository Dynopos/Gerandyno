@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('app.reports.sales.date') }}</th>
                <th>{{ __('app.reports.sales.receipt_number') }}</th>
                <th class="numeric">{{ __('app.receipt.subtotal') }}</th>
                <th class="numeric">{{ __('app.receipt.discount') }}</th>
                <th class="numeric">{{ __('app.receipt.tax') }}</th>
                <th class="numeric">{{ __('app.reports.sales.total') }}</th>
                <th>{{ __('app.reports.sales.payment_method') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($receipts as $receipt)
                <tr>
                    <td>{{ $receipt->transaction_date->format('Y-m-d H:i') }}</td>
                    <td>{{ $receipt->receipt_number ?? '#'.$receipt->id }}</td>
                    <td class="numeric">{{ \App\Support\Money::format($receipt->subtotal) }}</td>
                    <td class="numeric">{{ \App\Support\Money::format($receipt->discount) }}</td>
                    <td class="numeric">{{ \App\Support\Money::format($receipt->tax) }}</td>
                    <td class="numeric">{{ \App\Support\Money::format($receipt->total) }}</td>
                    <td>{{ $receipt->payments->pluck('payment_method')->map(fn ($m) => ucfirst($m))->implode(', ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">{{ __('app.reports.sales.no_transactions') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if ($receipts->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="5">{{ __('app.reports.sales.total') }}</td>
                    <td class="numeric">{{ \App\Support\Money::format($receipts->sum('total')) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
