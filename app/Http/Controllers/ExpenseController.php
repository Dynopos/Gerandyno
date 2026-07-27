<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Support\Reports\ReportPeriodResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $period = ReportPeriodResolver::resolve($request);

        $query = Expense::query()
            ->whereBetween('expense_date', [$period->start, $period->end])
            ->when($request->string('q')->trim()->isNotEmpty(), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn (Builder $q) => $q->where('category', 'like', $term)->orWhere('description', 'like', $term));
            });

        $total = (float) (clone $query)->sum('amount');

        $expenses = $query->orderByDesc('expense_date')->paginate(20)->withQueryString();

        return view('expenses.index', [
            'period' => $period,
            'expenses' => $expenses,
            'search' => $request->string('q')->toString(),
            'filterOptions' => ReportPeriodResolver::options(),
            'total' => $total,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);

        return view('expenses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $validated = $this->validated($request);

        Expense::create($validated + ['created_by' => $request->user()->id]);

        return redirect()->route('expenses.index')
            ->with('status', __('app.expenses.created'));
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);

        return view('expenses.edit', [
            'expense' => $expense,
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $expense->update($this->validated($request));

        return redirect()->route('expenses.index')
            ->with('status', __('app.expenses.updated'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('status', __('app.expenses.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
        ]);
    }
}
