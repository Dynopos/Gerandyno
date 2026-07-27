<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerReportController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->withCount('receipts')
            ->withSum('receipts', 'total')
            ->withMax('receipts', 'transaction_date')
            ->when($request->string('q')->trim()->isNotEmpty(), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('phone', 'like', $term));
            })
            ->orderByDesc('receipts_sum_total')
            ->paginate(20)
            ->withQueryString();

        return view('reports.customers.index', [
            'customers' => $customers,
            'search' => $request->string('q')->toString(),
        ]);
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $receipts = $customer->receipts()
            ->with('payments')
            ->orderByDesc('transaction_date')
            ->paginate(20)
            ->withQueryString();

        return view('reports.customers.show', [
            'customer' => $customer,
            'receipts' => $receipts,
            'totalSpent' => (float) $customer->receipts()->sum('total'),
            'totalTransactions' => $customer->receipts()->count(),
        ]);
    }
}
