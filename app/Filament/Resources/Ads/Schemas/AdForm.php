<?php

namespace App\Filament\Resources\Ads\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;
use App\Models\Merchant;


class AdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('merchant_id')
                    ->label('Mağaza')
                    ->options(Merchant::query()->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
                TextInput::make('title')
                    ->label('Başlıq')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('image_path')
                    ->label('Şəkil')
                    ->directory('ads')
                    ->columnSpanFull()
                    ->imageEditor(2)
                    ->image(),
                RichEditor::make('content')
                    ->label('Məzmun')
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active'   => 'Aktiv',
                        'inactive' => 'Deaktiv',
                    ])
                    ->default('active')
                    ->required(),
                DateTimePicker::make('starts_at')->label('Başlama tarixi'),
                DateTimePicker::make('ends_at')->label('Bitmə tarixi'),
            ]);
    }
}
