@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm']) }}>
