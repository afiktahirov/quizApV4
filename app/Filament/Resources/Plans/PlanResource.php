<?php

namespace App\Filament\Resources\Plans;

use App\Filament\Resources\Plans\Pages\CreatePlan;
use App\Filament\Resources\Plans\Pages\EditPlan;
use App\Filament\Resources\Plans\Pages\ListPlans;
use App\Models\Plan;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Paketlər';

    protected static string|\UnitEnum|null $navigationGroup = 'Abunəlik';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): string
    {
        return 'Paket';
    }

    public static function getPluralLabel(): string
    {
        return 'Paketlər';
    }

    /** Yalnız super admin paketləri idarə edir */
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
                ->label('Qiymət')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required()
                ->suffix(fn ($get) => $get('currency') ?: 'AZN'),

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
                ])
                ->default('monthly')
                ->required(),

            TextInput::make('max_quizzes')
                ->label('Maks. kampaniya')
                ->numeric()->minValue(0)
                ->helperText('Boş = limitsiz')
                ->nullable(),

            TextInput::make('max_questions')
                ->label('Maks. öz sualı')
                ->numeric()->minValue(0)
                ->helperText('Boş = limitsiz')
                ->nullable(),

            TextInput::make('max_stores')
                ->label('Maks. filial')
                ->numeric()->minValue(0)
                ->helperText('Boş = limitsiz')
                ->nullable(),

            TextInput::make('max_ads')
                ->label('Maks. reklam')
                ->numeric()->minValue(0)
                ->helperText('Boş = limitsiz')
                ->nullable(),

            TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric()->default(0),

            Toggle::make('is_active')
                ->label('Aktiv')
                ->default(true),

            Textarea::make('description')
                ->label('Təsvir')
                ->rows(3)
                ->columnSpanFull()
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable()->sortable(),
                TextColumn::make('price')
                    ->label('Qiymət')
                    ->formatStateUsing(fn ($state, Plan $r) => number_format((float) $state, 2) . ' ' . $r->currency)
                    ->sortable(),
                TextColumn::make('billing_period')
                    ->label('Dövr')
                    ->formatStateUsing(fn (string $state) => $state === 'yearly' ? 'İllik' : 'Aylıq')
                    ->badge(),
                TextColumn::make('max_quizzes')->label('Kampaniya')->placeholder('∞'),
                TextColumn::make('max_questions')->label('Sual')->placeholder('∞'),
                TextColumn::make('max_stores')->label('Filial')->placeholder('∞'),
                TextColumn::make('merchants_count')
                    ->counts('merchants')
                    ->label('Abunə'),
                IconColumn::make('is_active')->label('Aktiv')->boolean(),
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
            'index'  => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'edit'   => EditPlan::route('/{record}/edit'),
        ];
    }
}
