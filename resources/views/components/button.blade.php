<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-emerald-600 hover:bg-emerald-700 text-white whitespace-nowrap']) }}>
    {{ $slot }}
</button>
