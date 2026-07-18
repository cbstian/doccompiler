<?php

namespace App\Filament\Resources\ApiClients\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApiClientInfolist
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
                                        TextEntry::make('name')
                                            ->label('Nombre'),

                                        TextEntry::make('uuid')
                                            ->label('UUID')
                                            ->copyable(),

                                        TextEntry::make('rate_limit_per_minute')
                                            ->label('Límite de peticiones por minuto')
                                            ->numeric(),
                                    ]),
                            ]),

                        // ── Sidebar (25%) ──
                        Grid::make(12)
                            ->columnSpan(3)
                            ->components([
                                Section::make('Control')
                                    ->columnSpanFull()
                                    ->components([
                                        IconEntry::make('is_active')
                                            ->label('Activo')
                                            ->boolean(),

                                        TextEntry::make('last_used_at')
                                            ->label('Último uso')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('-'),
                                    ]),

                                Section::make('Registro')
                                    ->columnSpanFull()
                                    ->components([
                                        TextEntry::make('created_at')
                                            ->label('Creado')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('-'),

                                        TextEntry::make('updated_at')
                                            ->label('Actualizado')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('-'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
