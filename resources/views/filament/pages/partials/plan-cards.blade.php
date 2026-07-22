<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($plans as $plan)
        @php $isCurrent = $currentPlanId === $plan->id; @endphp
        <div @class([
            'rounded-xl border p-4',
            'border-primary-500 ring-1 ring-primary-500' => $isCurrent,
            'border-gray-200 dark:border-white/10' => ! $isCurrent,
        ])>
            <div class="flex items-center justify-between">
                <span class="font-semibold">{{ $plan->name }}</span>
                @if ($isCurrent)
                    <span class="fi-badge inline-flex items-center rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">
                        Cari paket
                    </span>
                @endif
            </div>
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ number_format((float) $plan->price, 2) }} {{ $plan->currency }}
                / {{ $plan->billing_period === 'yearly' ? 'il' : 'ay' }}
            </div>
            @if ($plan->description)
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $plan->description }}</p>
            @endif
            <ul class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                <li>Kampaniya: {{ $plan->max_quizzes ?? '∞' }}</li>
                <li>Öz sualım: {{ $plan->max_questions ?? '∞' }}</li>
                <li>Filial: {{ $plan->max_stores ?? '∞' }}</li>
                <li>Reklam: {{ $plan->max_ads ?? '∞' }}</li>
            </ul>
        </div>
    @endforeach
</div>
