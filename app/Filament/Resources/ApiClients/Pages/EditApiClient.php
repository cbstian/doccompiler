<?php

namespace App\Filament\Resources\ApiClients\Pages;

use App\Filament\Resources\ApiClients\ApiClientResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditApiClient extends EditRecord
{
    protected static string $resource = ApiClientResource::class;

    public ?string $plainTextToken = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('regenerateToken')
                ->label('Regenerar token')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Regenerar token de acceso')
                ->modalDescription('El token anterior dejará de funcionar inmediatamente. El nuevo token se mostrará una única vez.')
                ->modalSubmitActionLabel('Sí, regenerar')
                ->action(function (): void {
                    $this->plainTextToken = Str::random(40);

                    $this->getRecord()->update([
                        'token' => hash('sha256', $this->plainTextToken),
                    ]);

                    $this->replaceMountedAction('showRegeneratedToken');
                }),
            DeleteAction::make(),
        ];
    }

    public function showRegeneratedTokenAction(): Action
    {
        $token = $this->plainTextToken;

        return Action::make('showRegeneratedToken')
            ->modalHeading('Token regenerado')
            ->modalDescription('El token anterior ha dejado de funcionar. Copia el nuevo token ahora, no se volverá a mostrar.')
            ->form([
                TextInput::make('token_display')
                    ->label('Nuevo token')
                    ->default($token)
                    ->disabled()
                    ->copyable()
                    ->extraInputAttributes(['class' => 'font-mono text-sm']),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->closeModalByClickingAway(false)
            ->action(function (): void {
                $this->plainTextToken = null;
            });
    }
}
