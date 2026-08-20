<?php

namespace App\Filament\Resources\Ads\Schemas;

use App\Models\Merchant;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('merchant_id')
                    ->label('Müəssisə')
                    ->options(Merchant::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->helperText('Boş buraxılsa reklam bütün tətbiqdə (ana səhifədə) göstərilir.')
                    ->visible(fn () => Filament::auth()->user()?->is_admin ?? false),

                // 3 dildə doldurulur — tətbiq istifadəçinin seçdiyi dili göstərir
                TextInput::make('title')
                    ->label('Başlıq')
                    ->required()
                    ->maxLength(255)
                    ->translatable(),

                FileUpload::make('image_path')
                    ->label('Şəkil')
                    ->helperText('Tətbiqdə reklam kartının şəkli. Geniş şəkil tövsiyə olunur (məs. 1200×600).')
                    ->image()
                    ->imageEditor()
                    ->directory('ads')
                    ->disk('public')
                    ->visibility('public')
                    ->maxSize(4096)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->columnSpanFull(),

                RichEditor::make('content')
                    ->label('Məzmun')
                    ->columnSpanFull()
                    ->translatable(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active'   => 'Aktiv',
                        'inactive' => 'Deaktiv',
                    ])
                    ->default('active')
                    ->required(),

                DateTimePicker::make('starts_at')
                    ->label('Başlama tarixi')
                    ->helperText('Boş = dərhal aktivdir'),

                DateTimePicker::make('ends_at')
                    ->label('Bitmə tarixi')
                    ->helperText('Boş = müddətsiz'),
            ]);
    }
}
