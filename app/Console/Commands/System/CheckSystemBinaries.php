<?php

namespace App\Console\Commands\System;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('system:check-binaries')]
#[Description('Verifica que el sistema tenga todos los binarios necesarios y sugiere instalación si faltan')]
class CheckSystemBinaries extends Command
{
    /**
     * Mapeo de nombres de binario a paquetes apt de Ubuntu.
     */
    protected const BINARY_TO_PACKAGE = [
        'pdftotext' => 'poppler-utils',
        'pdftoppm' => 'poppler-utils',
        'pdfinfo' => 'poppler-utils',
        'tesseract' => 'tesseract-ocr',
        'soffice' => 'libreoffice-core',
    ];

    /**
     * Versiones de Ubuntu soportadas.
     */
    protected const SUPPORTED_UBUNTU_VERSIONS = ['24.04', '26.04'];

    /**
     * Ruta al archivo os-release para detección del SO.
     * Sobrescribible mediante SYSTEM_CHECK_OS_RELEASE para testing.
     */
    protected function getOsReleasePath(): string
    {
        $envPath = env('SYSTEM_CHECK_OS_RELEASE');

        return is_string($envPath) && $envPath !== '' ? $envPath : '/etc/os-release';
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $osCheck = $this->checkOperatingSystem();

        if ($osCheck !== true) {
            $this->error($osCheck);

            return Command::FAILURE;
        }

        $binaries = collect(config('document-extractor.binaries', []));
        $results = $this->checkBinaries($binaries);

        $this->renderTable($results);

        if ($results->contains(fn (array $row): bool => ! $row['executable'])) {
            $missingPackages = $this->resolveMissingPackages($results);

            $this->newLine();
            $this->warn('Faltan binarios. Ejecuta el siguiente comando para instalarlos:');
            $this->newLine();
            $this->line('  <fg=bright-green>sudo apt-get install -y '.$missingPackages->join(' ').'</>');

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Todos los binarios necesarios están instalados y son ejecutables.');

        return Command::SUCCESS;
    }

    /**
     * Verifica que el sistema operativo sea Ubuntu 24.04 o 26.04.
     *
     * @return true|string True si es compatible, mensaje de error en caso contrario.
     */
    protected function checkOperatingSystem(): true|string
    {
        $osReleasePath = $this->getOsReleasePath();

        if (! file_exists($osReleasePath) || ! is_readable($osReleasePath)) {
            return 'No se pudo leer /etc/os-release. Este comando solo soporta Ubuntu 24.04 y 26.04.';
        }

        $content = file_get_contents($osReleasePath);
        $lines = explode("\n", $content);

        $osName = '';
        $versionId = '';

        foreach ($lines as $line) {
            if (str_starts_with($line, 'NAME=')) {
                $osName = trim(explode('=', $line, 2)[1], "\"'");
            }

            if (str_starts_with($line, 'VERSION_ID=')) {
                $versionId = trim(explode('=', $line, 2)[1], "\"'");
            }
        }

        if ($osName === '' || $versionId === '') {
            return 'No se pudo determinar el sistema operativo desde /etc/os-release. Este comando solo soporta Ubuntu 24.04 y 26.04.';
        }

        if (! str_contains(strtolower($osName), 'ubuntu')) {
            return "Sistema operativo no soportado: {$osName}. Este comando solo soporta Ubuntu 24.04 y 26.04.";
        }

        if (! in_array($versionId, self::SUPPORTED_UBUNTU_VERSIONS, true)) {
            return "Versión de Ubuntu no soportada: {$versionId}. Este comando solo soporta Ubuntu 24.04 y 26.04.";
        }

        return true;
    }

    /**
     * Verifica cada binario configurado.
     *
     * @param  Collection<string, string>  $binaries
     * @return Collection<int, array{name: string, path: string, executable: bool, package: string}>
     */
    protected function checkBinaries(Collection $binaries): Collection
    {
        return $binaries->map(fn (string $path, string $name): array => [
            'name' => $name,
            'path' => $path,
            'executable' => is_executable($path),
            'package' => self::BINARY_TO_PACKAGE[$name] ?? '—',
        ])->values();
    }

    /**
     * Renderiza la tabla de resultados en consola.
     *
     * @param  Collection<int, array{name: string, path: string, executable: bool, package: string}>  $results
     */
    protected function renderTable(Collection $results): void
    {
        $this->newLine();
        $this->line('  <fg=bright-cyan;options=bold>Binarios del sistema</>');
        $this->newLine();

        $rows = $results->map(fn (array $row): array => [
            $row['name'],
            $row['path'],
            $row['executable'] ? '<fg=bright-green>✅</>' : '<fg=bright-red>❌</>',
            $row['package'],
        ])->toArray();

        $this->table(
            ['Binario', 'Ruta', 'Estado', 'Paquete apt'],
            $rows,
        );
    }

    /**
     * Obtiene la lista de paquetes apt a instalar (sin duplicados) para los binarios faltantes.
     *
     * @param  Collection<int, array{name: string, executable: bool, package: string}>  $results
     * @return Collection<int, string>
     */
    protected function resolveMissingPackages(Collection $results): Collection
    {
        return $results
            ->filter(fn (array $row): bool => ! $row['executable'])
            ->pluck('package')
            ->unique()
            ->values();
    }
}
