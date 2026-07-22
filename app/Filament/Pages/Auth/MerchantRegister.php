<?php

namespace App\Filament\Pages\Auth;

use App\Models\Merchant;
use App\Models\User;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Mağazaların özləri qeydiyyatdan keçməsi üçün. Eyni anda həm Merchant, həm də
 * ona bağlı ilk istifadəçi (merchant_admin) yaradılır. Qeydiyyatdan sonra
 * mağazanın hələ paketi olmadığı üçün birbaşa "Abunəliyim" səhifəsinə
 * yönləndirilir — paket (pulsuz sınaq daxil) seçilməyincə panelin qalan
 * hissəsi görünmür (bax: App\Filament\Concerns\RequiresActivePlan).
 */
class MerchantRegister extends Register
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mağaza məlumatları')
                ->schema([
                    TextInput::make('merchant_name')
                        ->label('Mağazanın adı')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('address')
                        ->label('Ünvan')
                        ->maxLength(255),
                    Textarea::make('bio')
                        ->label('Qısa təsvir')
                        ->rows(2),
                ]),

            Section::make('Hesabınız')
                ->schema([
                    $this->getNameFormComponent(),
                    $this->getEmailFormComponent(),
                    $this->getPasswordFormComponent(),
                    $this->getPasswordConfirmationFormComponent(),
                ]),
        ]);
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Sizin adınız')
            ->required()
            ->maxLength(255);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        $merchant = Merchant::create([
            'name'    => $data['merchant_name'],
            'slug'    => Str::slug($data['merchant_name']) . '-' . Str::lower(Str::random(6)),
            'status'  => 'active',
            'bio'     => $data['bio'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        return User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => $data['password'],
            'role'        => User::ROLE_MERCHANT_ADMIN,
            'merchant_id' => $merchant->id,
        ]);
    }
}
