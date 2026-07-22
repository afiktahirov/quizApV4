@php
    $isSubscribed = $merchant->isSubscribed();
@endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <span class="text-lg font-semibold">{{ $plan?->name ?? 'Paket seçilməyib' }}</span>
        <span @class([
            'fi-badge inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium',
            'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400' => $isSubscribed,
            'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400' => ! $isSubscribed,
        ])>
            {{ $isSubscribed ? 'Aktiv' : 'Aktiv deyil' }}
        </span>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Qiymət</div>
            <div class="text-sm font-medium">
                @if ($plan)
                    {{ number_format((float) $plan->price, 2) }} {{ $plan->currency }}
                    / {{ $plan->billing_period === 'yearly' ? 'il' : 'ay' }}
                @else
                    -
                @endif
            </div>
        </div>
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Abunəlik bitir</div>
            <div class="text-sm font-medium">
                {{ $merchant->subscription_ends_at?->format('d.m.Y H:i') ?? 'Limitsiz' }}
            </div>
        </div>
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Gözləyən sorğu</div>
            <div class="text-sm font-medium">
                @if ($pending)
                    {{ $pending->plan?->name }} ({{ $pending->periods }} dövr) — baxılır
                @else
                    Yoxdur
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ([
            'quizzes' => 'Kampaniyalar',
            'questions' => 'Öz suallarım',
            'stores' => 'Filiallar',
            'ads' => 'Reklamlar',
        ] as $key => $label)
            @php
                $limit = $merchant->planLimit($key);
                $used = $merchant->usageCount($key);
            @endphp
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="text-sm font-medium">
                    {{ $used }} / {{ $limit === null ? '∞' : $limit }}
                </div>
            </div>
        @endforeach
    </div>
</div>
