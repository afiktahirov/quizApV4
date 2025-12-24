<?php

namespace App\Filament\Resources\Merchants\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Malzariey\FilamentLexicalEditor\FilamentLexicalEditor;
use Filament\Forms\Components\RichEditor;


class MerchantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label("Ad")
                    ->required(),

                TextInput::make('slug')
                    ->required(),

                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),


//                Textarea::make('settings')
//                    ->default(null)
//                    ->label('Bio')
//                    ->columnSpanFull(),

                RichEditor::make('bio')
                    ->columnSpanFull()
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                        ['table', 'attachFiles'], // The `customBlocks` and `mergeTags` tools are also added here if those features are used.
                        ['undo', 'redo'],
                    ])
//                Select::make('is_master')
//                    ->label('Master merchant?')
//                    ->options([
//                        1 => 'Bəli',
//                        0 => 'Xeyr',
//                    ])
//                    ->required()
//                    ->default(0),
            ]);
    }
}
