<?php

namespace App\Filament\Resources\ApiClients\Pages;

use App\Filament\Resources\ApiClients\ApiClientResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditApiClient extends EditRecord
{
    protected static string $resource = ApiClientResource::class;

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
                    $plainToken = Str::random(40);

                    $this->getRecord()->update([
                        'token' => hash('sha256', $plainToken),
                    ]);

                    Notification::make()
                        ->title('Token regenerado')
                        ->body('Copia este token ahora. No se volverá a mostrar.')
                        ->success()
                        ->persistent()
                        ->actions([
                            Action::make('copyToken')
                                ->label('Copiar token')
                                ->icon('heroicon-o-clipboard')
                                ->extraAttributes([
                                    'x-data' => '{}',
                                    'x-on:click' => 'navigator.clipboard.writeText(\''.e($plainToken).'\')',
                                ]),
                        ])
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
