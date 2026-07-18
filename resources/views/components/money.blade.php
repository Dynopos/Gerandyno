@props(['amount'])

<span {{ $attributes }}>{{ \App\Support\Money::format($amount) }}</span>
