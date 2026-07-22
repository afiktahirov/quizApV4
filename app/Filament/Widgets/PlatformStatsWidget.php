<?php

namespace App\Filament\Widgets;

use App\Models\Coupon;
use App\Models\Merchant;
use App\Models\MerchantSubscription;
use App\Models\QuizSession;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    /** Yalnız super admin platforma statistikasını görür */
    public static function canView(): bool
    {
        return Filament::auth()->user()?->is_admin ?? false;
    }

    protected function getStats(): array
    {
        $revenue      = (float) MerchantSubscription::sum('amount');
        $activeSubs   = Merchant::query()->subscribed()->count();
        $totalStores  = Merchant::count();
        $blocked      = Merchant::where('status', 'inactive')->count();
        $sessions     = QuizSession::count();
        $couponsTotal = Coupon::count();
        $couponsUsed  = Coupon::where('status', 'redeemed')->count();

        return [
            Stat::make('Ümumi abunə gəliri', number_format($revenue, 2) . ' AZN')
                ->description('Bütün abunəlik ödənişləri')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Aktiv abunəliklər', (string) $activeSubs)
                ->description($totalStores . ' mağazadan (' . $blocked . ' bloklu)')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('primary'),

            Stat::make('Quiz sessiyaları', (string) $sessions)
                ->description('Ümumi oynanış sayı')
                ->descriptionIcon('heroicon-o-play-circle')
                ->color('info'),

            Stat::make('Kuponlar', $couponsUsed . ' / ' . $couponsTotal)
                ->description('İstifadə olunan / verilən')
                ->descriptionIcon('heroicon-o-ticket')
                ->color('warning'),
        ];
    }
}
