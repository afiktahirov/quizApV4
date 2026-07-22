<?php

namespace App\Filament\Resources\UiTexts;

use App\Filament\Resources\UiTexts\Pages\CreateUiText;
use App\Filament\Resources\UiTexts\Pages\EditUiText;
use App\Filament\Resources\UiTexts\Pages\ListUiTexts;
use App\Models\UiText;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UiTextResource extends Resource
{
    protected static ?string $model = UiText::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-language';

    protected static ?string $navigationLabel = 'Sayt mətnləri';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'key';

    public static function getLabel(): string
    {
        return 'Sayt mətni';
    }

    public static function getPluralLabel(): string
    {
        return 'Sayt mətnləri';
    }

    /** Yalnız super admin idarə edir */
    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->is_admin ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')
                ->label('Açar (key)')
                ->helperText('Frontend bu açarla mətni tapır. Mövcud açarı dəyişməyin — yalnız tərcümələri redaktə edin.')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Select::make('group')
                ->label('Qrup')
                ->options([
                    'nav'        => 'Naviqasiya',
                    'home'       => 'Ana səhifə',
                    'store'      => 'Mağaza səhifəsi',
                    'play'       => 'Quiz oynama',
                    'coupons'    => 'Kuponlar',
                    'auth'       => 'Giriş / Qeydiyyat',
                    'validation' => 'Validasiya',
                    'errors'     => 'Xətalar',
                    'discount'   => 'Endirim mətnləri',
                ])
                ->nullable(),

            TextInput::make('value')
                ->label('Mətn')
                ->helperText('Mətndə {dəyişən} formasında yer tutucular ola bilər (məs. {discount}) — onları silməyin.')
                ->required()
                ->translatable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Açar')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('group')
                    ->label('Qrup')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('value')
                    ->label('AZ mətn')
                    ->state(fn (UiText $r) => $r->value['az'] ?? '')
                    ->limit(60)
                    ->searchable(query: fn ($query, $search) => $query->where('value', 'like', "%{$search}%")),
                TextColumn::make('updated_at')
                    ->label('Yenilənib')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label('Qrup')
                    ->options([
                        'nav' => 'Naviqasiya', 'home' => 'Ana səhifə', 'store' => 'Mağaza',
                        'play' => 'Quiz', 'coupons' => 'Kuponlar', 'auth' => 'Giriş/Qeydiyyat',
                        'validation' => 'Validasiya', 'errors' => 'Xətalar', 'discount' => 'Endirim',
                    ]),
            ])
            ->defaultSort('key')
            ->paginated([25, 50, 100])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUiTexts::route('/'),
            'create' => CreateUiText::route('/create'),
            'edit'   => EditUiText::route('/{record}/edit'),
        ];
    }
}
