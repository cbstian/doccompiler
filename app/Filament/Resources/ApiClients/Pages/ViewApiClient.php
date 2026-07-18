<?php

namespace App\Filament\Resources\ApiClients\Pages;

use App\Filament\Resources\ApiClients\ApiClientResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewApiClient extends ViewRecord
{
    protected static string $resource = ApiClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function showTokenAction(): Action
    {
        $token = session()->get('api_client_plain_token');

        return Action::make('showToken')
            ->modalHeading('Token de acceso generado')
            ->modalDescription('Este token solo se muestra una vez. Cópialo ahora, no podrás verlo de nuevo.')
            ->form([
                TextInput::make('token_display')
                    ->label('Token')
                    ->default($token)
                    ->disabled()
                    ->copyable()
                    ->extraInputAttributes(['class' => 'font-mono text-sm']),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->closeModalByClickingAway(false)
            ->action(function (): void {
                session()->forget('api_client_plain_token');
            });
    }
}
