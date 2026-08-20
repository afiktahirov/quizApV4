<?php

namespace App\Filament\Resources\CustomerPlans;

use App\Filament\Resources\CustomerPlans\Pages\CreateCustomerPlan;
use App\Filament\Resources\CustomerPlans\Pages\EditCustomerPlan;
use App\Filament\Resources\CustomerPlans\Pages\ListCustomerPlans;
use App\Models\CustomerPlan;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Super admin burada İSTİFADƏÇİ (müştəri) abunəlik paketlərini yaradır.
 * Mağaza paketləri ayrıca resursdadır (PlanResource).
 */
class CustomerPlanResource extends Resource
{
    protected static ?string $model = CustomerPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'İstifadəçi paketləri';

    protected static string|\UnitEnum|null $navigationGroup = 'Abunəlik';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): string
    {
        return 'İstifadəçi paketi';
    }

    public static function getPluralLabel(): string
    {
        return 'İstifadəçi paketləri';
    }

    /** Yalnız super admin */
    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->is_admin ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Ad')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('price')
                ->label('Aylıq qiymət')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required()
                ->suffix(fn ($get) => $get('currency') ?: 'AZN')
                ->helperText('0 yazsanız paket pulsuz sayılır və ödəniş tələb olunmur.'),

            Select::make('currency')
                ->label('Valyuta')
                ->options(['AZN' => 'AZN', 'USD' => 'USD', 'EUR' => 'EUR'])
                ->default('AZN')
                ->required(),

            Select::make('billing_period')
                ->label('Dövr')
                ->options([
                    'monthly' => 'Aylıq',
                    'yearly'  => 'İllik',
                    'trial'   => 'Pulsuz sınaq',
                ])
                ->default('monthly')
                ->live()
                ->required(),

            TextInput::make('trial_days')
                ->label('Sınaq müddəti (gün)')
                ->numeric()->minValue(1)->default(7)
                ->visible(fn ($get) => $get('billing_period') === 'trial')
                ->required(fn ($get) => $get('billing_period') === 'trial'),

            TextInput::make('max_quizzes_per_day')
                ->label('Gündəlik maks. quiz')
                ->numeric()->minValue(1)
                ->helperText('Boş = limitsiz')
                ->nullable(),

            TextInput::make('max_coupons_per_month')
                ->label('Aylıq maks. kupon')
                ->numeric()->minValue(1)
                ->helperText('Boş = limitsiz')
                ->nullable(),

            TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric()->default(0),

            Toggle::make('is_active')
                ->label('Satışdadır')
                ->default(true),

            Textarea::make('description')
                ->label('Qısa təsvir')
                ->rows(2)
                ->columnSpanFull()
                ->nullable(),

            TagsInput::make('features')
                ->label('Üstünlüklər')
                ->placeholder('Yeni maddə əlavə et')
                ->helperText('Tətbiqdə paket kartında siyahı kimi göstərilir.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable()->sortable(),
                TextColumn::make('price')
                    ->label('Qiymət')
                    ->formatStateUsing(fn ($state, CustomerPlan $r) => number_format((float) $state, 2) . ' ' . $r->currency)
                    ->sortable(),
                TextColumn::make('billing_period')
                    ->label('Dövr')
                    ->badge()
                    ->formatStateUsing(fn (string $state, CustomerPlan $r) => match ($state) {
                        'yearly' => 'İllik',
                        'trial'  => 'Sınaq (' . ($r->trial_days ?? '?') . ' gün)',
                        default  => 'Aylıq',
                    })
                    ->color(fn (string $state) => $state === 'trial' ? 'warning' : 'gray'),
                TextColumn::make('max_quizzes_per_day')->label('Gündəlik quiz')->placeholder('∞'),
                TextColumn::make('max_coupons_per_month')->label('Aylıq kupon')->placeholder('∞'),
                TextColumn::make('customers_count')
                    ->counts('customers')
                    ->label('Abunə'),
                IconColumn::make('is_active')->label('Satışda')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCustomerPlans::route('/'),
            'create' => CreateCustomerPlan::route('/create'),
            'edit'   => EditCustomerPlan::route('/{record}/edit'),
        ];
    }
}
