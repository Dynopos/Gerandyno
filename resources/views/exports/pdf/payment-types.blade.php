@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('app.reports.payment_types.payment_method') }}</th>
                <th class="numeric">{{ __('app.reports.payment_types.transactions') }}</th>
                <th class="numeric">{{ __('app.reports.payment_types.total_sales') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($paymentTypes as $paymentType)
                <tr>
                    <td>{{ $paymentType['payment_method'] }}</td>
                    <td class="numeric">{{ number_format($paymentType['transactions']) }}</td>
                    <td class="numeric">{{ \App\Support\Money::format($paymentType['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">{{ __('app.reports.payment_types.no_data') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if ($paymentTypes->isNotEmpty())
            <tfoot>
                <tr>
                    <td>{{ __('app.reports.payment_types.total_sales') }}</td>
                    <td class="numeric">{{ number_format($paymentTypes->sum('transactions')) }}</td>
                    <td class="numeric">{{ \App\Support\Money::format($paymentTypes->sum('total')) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
