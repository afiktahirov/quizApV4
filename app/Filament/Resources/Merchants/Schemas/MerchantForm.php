<?php

namespace App\Filament\Resources\Merchants\Schemas;

use App\Models\Merchant;
use Dotswan\MapPicker\Fields\Map;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;


class MerchantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Ad')
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

            RichEditor::make('bio')
                ->columnSpanFull(),

            // İstəsən lat/lng inputları da görünsün (sənin screenshot-da var)
            TextInput::make('latitude')
                ->label('Latitude')
                ->step('0.00000001'),

            TextInput::make('longitude')
                ->label('Longitude')
                ->step('0.00000001'),

            TextInput::make('address')
                ->label('Address')
                ->placeholder('Street, city, postal code')
                ->columnSpanFull()
                ->suffixAction(
                    Action::make('findOnMap')
                        ->label('Find')
                        ->icon('heroicon-m-magnifying-glass')
                        ->action(function (Set $set, ?string $state): void {
                            $q = trim((string) $state);
                            if ($q === '') return;

                            $res = Http::withHeaders([
                                'User-Agent' => 'Quizzo_back/1.0 (contact: you@example.com)',
                                'Accept-Language' => 'az,en;q=0.8',
                            ])->get('https://nominatim.openstreetmap.org/search', [
                                'q' => $q,
                                'format' => 'json',
                                'limit' => 1,
                            ])->json();

                            if (!empty($res[0])) {
                                $lat = (float) $res[0]['lat'];
                                $lng = (float) $res[0]['lon'];

                                $set('location', ['lat' => $lat, 'lng' => $lng]);
                                $set('latitude', $lat);
                                $set('longitude', $lng);

                                // ✅ Address göstər:
                                $displayName = $res[0]['display_name'] ?? null;
                                $set('address', $displayName ?: (round($lat, 6) . ', ' . round($lng, 6)));
                            }
                        })

                ),

            Map::make('location')
                ->label('Location')
                ->columnSpanFull()
                ->defaultLocation(latitude: 40.4093, longitude: 49.8671) // Bakı
                ->showMarker(true)
                ->draggable(true)
                ->clickable(true)
                ->zoom(13)
                ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")

                // ⚠️ location DB-yə yazılmasın (çünki sütun yoxdur)
                ->dehydrated(false)

                // Marker hər dəfə dəyişəndə lat/lng sütunlarını yenilə
                ->afterStateUpdated(function (Set $set, ?array $state): void {
                    $lat = isset($state['lat']) ? (float) $state['lat'] : null;
                    $lng = isset($state['lng']) ? (float) $state['lng'] : null;

                    $set('latitude', $lat);
                    $set('longitude', $lng);
                    $set('geojson', $state['geojson'] ?? null);

                    if ($lat === null || $lng === null) {
                        $set('address', null);
                        return;
                    }

                    // Əvvəlcə koordinat fallback yaz (API geciksə də input boş qalmasın)
                    $fallback = round($lat, 6) . ', ' . round($lng, 6);
                    $set('address', $fallback);

                    // ✅ Reverse geocode (Cache ilə)
                    $latKey = round($lat, 5);
                    $lngKey = round($lng, 5);
                    $cacheKey = "nominatim_rev_{$latKey}_{$lngKey}";

                    $name = Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lng) {
                        try {
                            $json = Http::withHeaders([
                                'User-Agent' => 'Quizzo_back/1.0 (contact: you@example.com)',
                                'Accept-Language' => 'az,en;q=0.8',
                            ])->get('https://nominatim.openstreetmap.org/reverse', [
                                'lat' => $lat,
                                'lon' => $lng,
                                'format' => 'jsonv2',
                                'zoom' => 18,
                                'addressdetails' => 1,
                            ])->json();

                            return $json['display_name'] ?? null;
                        } catch (\Throwable $e) {
                            return null;
                        }
                    });

                    if (!empty($name)) {
                        $set('address', $name);
                    }
                })


                // ✅ Edit açılarkən marker-in "yadda qalması" üçün:
                ->afterStateHydrated(function ($component, ?Merchant $record): void {
                    if (! $record) {
                        return;
                    }

                    if ($record->latitude !== null && $record->longitude !== null) {
                        $component->state([
                            'lat' => (float) $record->latitude,
                            'lng' => (float) $record->longitude,
                            'geojson' => $record->geojson,
                        ]);
                    }
                }),

            FileUpload::make('photo')
                ->label('Foto')
                ->image()
                ->imageEditor()
                ->directory('merchants')
                ->disk('public')
                ->visibility('public')
                ->maxSize(2048)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->columnSpanFull(),
        ]);
    }
}
