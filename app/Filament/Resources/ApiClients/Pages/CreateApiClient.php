<?php

namespace App\Filament\Resources\ApiClients\Pages;

use App\Filament\Resources\ApiClients\ApiClientResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateApiClient extends CreateRecord
{
    protected static string $resource = ApiClientResource::class;

    protected ?string $plainTextToken = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $this->plainTextToken = Str::random(40);
        $data['token'] = hash('sha256', $this->plainTextToken);

        return $data;
    }

    protected function afterCreate(): void
    {
        session()->put($this->getTokenSessionKey(), $this->plainTextToken);

        $this->plainTextToken = null;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]).'?action=showToken';
    }

    protected function getTokenSessionKey(): string
    {
        return 'api_client_plain_token.'.$this->getRecord()->getKey();
    }
}
