@extends('exports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>{{ __('app.reports.products.product_name') }}</th>
                <th class="numeric">{{ __('app.reports.products.quantity_sold') }}</th>
                <th class="numeric">{{ __('app.reports.products.total_sales') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product['product_name'] }}</td>
                    <td class="numeric">{{ rtrim(rtrim(number_format($product['quantity_sold'], 2), '0'), '.') }}</td>
                    <td class="numeric">{{ \App\Support\Money::format($product['total_sales']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">{{ __('app.reports.products.no_data') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if ($products->isNotEmpty())
            <tfoot>
                <tr>
                    <td>{{ __('app.reports.products.total_sales') }}</td>
                    <td></td>
                    <td class="numeric">{{ \App\Support\Money::format($products->sum('total_sales')) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
