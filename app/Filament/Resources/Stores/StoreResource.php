<?php

namespace App\Filament\Resources\Stores;

use App\Filament\Resources\Stores\Pages\CreateStore;
use App\Filament\Resources\Stores\Pages\EditStore;
use App\Filament\Resources\Stores\Pages\ListStores;
use App\Models\Merchant;
use App\Models\Store;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class StoreResource extends Resource
{
    use \App\Filament\Concerns\EnforcesPlanLimit;

    protected static ?string $model = Store::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Filiallar';

    protected static string|\UnitEnum|null $navigationGroup = 'Müəssisə';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): string
    {
        return 'Filial';
    }

    public static function getPluralLabel(): string
    {
        return 'Filiallar';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('merchant_id')
                ->label('Müəssisə')
                ->options(Merchant::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->visible(fn () => Filament::auth()->user()?->is_admin ?? false),

            TextInput::make('name')
                ->label('Ad')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state) . '-' . Str::lower(Str::random(4)))),

            TextInput::make('slug')
                ->label('Slug (QR üçün)')
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('address')
                ->label('Ünvan')
                ->nullable(),

            Select::make('status')
                ->label('Status')
                ->options([
                    'active'   => 'Aktiv',
                    'inactive' => 'Deaktiv',
                ])
                ->default('active')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('merchant.name')
                    ->label('Müəssisə')
                    ->visible(fn () => Filament::auth()->user()?->is_admin ?? false),
                TextColumn::make('slug'),
                TextColumn::make('address')->label('Ünvan')->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return static::canCreateWithinPlan('stores');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = Filament::auth()->user();

        if ($user?->is_admin) {
            return $query;
        }

        return $query->where('merchant_id', $user?->merchant_id);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListStores::route('/'),
            'create' => CreateStore::route('/create'),
            'edit'   => EditStore::route('/{record}/edit'),
        ];
    }
}
