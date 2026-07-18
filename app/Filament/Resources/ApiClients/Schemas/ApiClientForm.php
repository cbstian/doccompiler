<?php

namespace App\Filament\Resources\ApiClients\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApiClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->components([
                        // ── Columna Principal (75%) ──
                        Grid::make(12)
                            ->columnSpan(9)
                            ->components([
                                Section::make('Información del Cliente')
                                    ->columnSpanFull()
                                    ->components([
                                        TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(80)
                                            ->autofocus(),

                                        TextInput::make('rate_limit_per_minute')
                                            ->label('Límite de peticiones por minuto')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(60),
                                    ]),
                            ]),

                        // ── Sidebar (25%) ──
                        Grid::make(12)
                            ->columnSpan(3)
                            ->components([
                                Section::make('Control')
                                    ->columnSpanFull()
                                    ->components([
                                        Toggle::make('is_active')
                                            ->label('Activo')
                                            ->default(true)
                                            ->helperText('Desactivar para revocar el acceso sin eliminar el cliente.'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
