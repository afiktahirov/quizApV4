<?php

namespace App\Filament\Pages;

use App\Models\Merchant;
use App\Models\Plan;
use App\Models\SubscriptionRequest;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentService;
use App\Services\SubscriptionService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * merchant_admin üçün "Abunəliyim" — cari paketinə baxır, paket seçib/uzadıb
 * onlayn ödəniş (Kapital Bank / Birbank Business) ilə dərhal ödəyir.
 * Bank dəyişəndə/əlavə olunanda burada dəyişiklik lazım deyil —
 * bax: PaymentGatewayManager, config/payments.php.
 */
class MySubscription extends Page
{
    protected static ?string $slug = 'abuneliyim';

    protected static ?string $navigationLabel = 'Abunəliyim';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Müəssisə';

    protected static ?int $navigationSort = 2;

    public ?Merchant $record = null;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->role === 'merchant_admin' && $user->merchant_id !== null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->record = Merchant::with('plan')->findOrFail(Filament::auth()->user()->merchant_id);

        $this->notifyPaymentReturn();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Abunəliyim';
    }

    protected function getPendingRequest()
    {
        return $this->record->subscriptionRequests()->where('status', 'pending')->latest()->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('requestUpgrade')
                ->label('Paket seç / Uzat')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('primary')
                ->visible(fn () => ! $this->getPendingRequest())
                ->schema([
                    Select::make('plan_id')
                        ->label('Paket')
                        ->options(Plan::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                        ->required()
                        ->native(false),
                    TextInput::make('periods')
                        ->label('Neçə dövr (ay/il)')
                        ->numeric()->minValue(1)->default(1)->required()
                        ->helperText('Paketin dövrünə görə (aylıq paket → ay sayı, illik → il sayı)'),
                ])
                ->action(function (array $data) {
                    $plan = Plan::findOrFail($data['plan_id']);

                    try {
                        $request = app(SubscriptionService::class)->requestUpgrade($this->record, $plan, (int) $data['periods']);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('Sorğu göndərilmədi')
                            ->body(collect($e->errors())->flatten()->first())
                            ->danger()
                            ->send();

                        return;
                    }

                    return $this->startPayment($request);
                }),

            Action::make('completePayment')
                ->label('Ödənişi tamamla')
                ->icon('heroicon-o-credit-card')
                ->color('success')
                ->visible(fn () => (bool) $this->getPendingRequest())
                ->action(fn () => $this->startPayment($this->getPendingRequest())),

            Action::make('cancelRequest')
                ->label('Sorğunu ləğv et')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => (bool) $this->getPendingRequest())
                ->requiresConfirmation()
                ->action(function () {
                    $pending = $this->getPendingRequest();

                    if ($pending) {
                        app(SubscriptionService::class)->cancelRequest($pending);

                        Notification::make()->title('Sorğu ləğv edildi')->success()->send();
                    }
                }),
        ];
    }

    protected function startPayment(?SubscriptionRequest $request)
    {
        if (! $request) {
            return;
        }

        try {
            $session = app(PaymentService::class)->initiate($request);
        } catch (PaymentGatewayException $e) {
            Notification::make()
                ->title('Ödəniş başladıla bilmədi')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        return redirect($session->redirectUrl);
    }

    protected function notifyPaymentReturn(): void
    {
        $payment = request()->query('payment');

        match ($payment) {
            'success' => Notification::make()->title('Ödəniş uğurludur')->body('Paketiniz aktivləşdirildi.')->success()->send(),
            'failed'  => Notification::make()->title('Ödəniş uğursuz oldu')->body('Bank ödənişi təsdiqləmədi. Yenidən cəhd edə bilərsiniz.')->danger()->send(),
            'error'   => Notification::make()->title('Ödəniş yoxlanıla bilmədi')->body('Bir az sonra yenidən yoxlayın.')->warning()->send(),
            default   => null,
        };
    }

    public function content(Schema $schema): Schema
    {
        $merchant = $this->record;

        return $schema->components([
            Section::make('Cari abunəlik')
                ->schema([
                    Html::make(fn () => view('filament.pages.partials.subscription-summary', [
                        'merchant' => $merchant,
                        'plan'     => $merchant->plan,
                        'pending'  => $this->getPendingRequest(),
                    ])->render()),
                ]),

            Section::make('Paketlər')
                ->schema([
                    Html::make(fn () => view('filament.pages.partials.plan-cards', [
                        'plans'          => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
                        'currentPlanId'  => $merchant->plan_id,
                    ])->render()),
                ]),

            Section::make('Sorğu tarixçəniz')
                ->schema([
                    Html::make(fn () => view('filament.pages.partials.subscription-requests', [
                        'requests' => $merchant->subscriptionRequests()->latest()->limit(10)->get(),
                    ])->render()),
                ]),
        ]);
    }
}
