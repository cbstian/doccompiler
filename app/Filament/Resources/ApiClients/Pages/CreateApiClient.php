<?php

namespace App\Filament\Resources\ApiClients\Pages;

use App\Filament\Resources\ApiClients\ApiClientResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateApiClient extends CreateRecord
{
    protected static string $resource = ApiClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['token'] = $this->hashToken($plainToken = Str::random(40));

        $this->dispatch('token-generated', token: $plainToken);

        return $data;
    }

    protected function afterCreate(): void
    {
        $plainToken = session()->pull('api_client_plain_token');

        if ($plainToken) {
            Notification::make()
                ->title('Cliente API creado')
                ->body('El token de acceso se muestra una única vez. Cópialo ahora.')
                ->success()
                ->persistent()
                ->actions([
                    Action::make('copyToken')
                        ->label('Copiar token')
                        ->icon('heroicon-o-clipboard')
                        ->action(fn () => null)
                        ->extraAttributes([
                            'x-data' => '{}',
                            'x-on:click' => 'navigator.clipboard.writeText(\''.e($plainToken).'\')',
                        ]),
                ])
                ->send();
        }
    }

    private function hashToken(string $plainToken): string
    {
        session()->put('api_client_plain_token', $plainToken);

        return hash('sha256', $plainToken);
    }
}
