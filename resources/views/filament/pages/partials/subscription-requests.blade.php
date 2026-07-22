@if ($requests->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">Hələ sorğu göndərilməyib.</p>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 dark:text-gray-400">
                    <th class="pb-2 pr-4">Paket</th>
                    <th class="pb-2 pr-4">Dövr</th>
                    <th class="pb-2 pr-4">Məbləğ</th>
                    <th class="pb-2 pr-4">Status</th>
                    <th class="pb-2">Tarix</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $request)
                    <tr class="border-t border-gray-100 dark:border-white/5">
                        <td class="py-2 pr-4">{{ $request->plan?->name ?? '-' }}</td>
                        <td class="py-2 pr-4">{{ $request->periods }}</td>
                        <td class="py-2 pr-4">{{ number_format((float) $request->amount, 2) }} {{ $request->currency }}</td>
                        <td class="py-2 pr-4">
                            @php
                                $color = match ($request->status) {
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected', 'cancelled' => 'danger',
                                    default => 'gray',
                                };
                                $label = match ($request->status) {
                                    'pending' => 'Baxılır',
                                    'approved' => 'Təsdiqlənib',
                                    'rejected' => 'Rədd edilib',
                                    'cancelled' => 'Ləğv edilib',
                                    default => $request->status,
                                };
                            @endphp
                            <span @class([
                                'fi-badge inline-flex items-center rounded-md px-2 py-1 text-xs font-medium',
                                "bg-{$color}-50 text-{$color}-700 dark:bg-{$color}-400/10 dark:text-{$color}-400",
                            ])>
                                {{ $label }}
                            </span>
                        </td>
                        <td class="py-2">{{ $request->created_at?->format('d.m.Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
