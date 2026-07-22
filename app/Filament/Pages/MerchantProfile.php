<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Merchants\Schemas\MerchantForm;
use App\Models\Merchant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * merchant_admin üçün "Mağazam" — öz mağazasını tək səhifədə redaktə etmə.
 * Super admin bunu görmür, o "Mağazalar" resurs-u (siyahı+idarəetmə) istifadə edir.
 */
class MerchantProfile extends Page
{
    protected static ?string $slug = 'magazam';

    protected static ?string $navigationLabel = 'Mağazam';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = 'Müəssisə';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

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

        $this->record = Merchant::findOrFail(Filament::auth()->user()->merchant_id);

        $this->form->fill($this->record->attributesToArray());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->model($this->record)
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return MerchantForm::configure($schema);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([$this->getSaveFormAction()])
                        ->key('form-actions'),
                ]),
        ]);
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Yadda saxla')
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->record->update($data);

        Notification::make()
            ->title('Mağaza məlumatları yadda saxlanıldı')
            ->success()
            ->send();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Mağazam';
    }
}
