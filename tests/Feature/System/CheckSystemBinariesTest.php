<?php

use Illuminate\Support\Facades\Artisan;

// ─── Helpers ─────────────────────────────────────────────────────────

/**
 * Crea un archivo temporal ejecutable y devuelve su ruta.
 */
function createExecutableBinaryForTest(): string
{
    $path = tempnam(sys_get_temp_dir(), 'system-check-');
    chmod($path, 0755);

    return $path;
}

/**
 * Crea un archivo temporal con contenido de /etc/os-release simulado.
 */
function fakeOsReleaseFile(string $name, string $versionId): string
{
    $content = "NAME=\"{$name}\"\nVERSION_ID=\"{$versionId}\"\n";
    $path = tempnam(sys_get_temp_dir(), 'os-release-');
    file_put_contents($path, $content);

    return $path;
}

// ─── Comando completo (happy path) ───────────────────────────────────

it('passes when all binaries are executable on supported Ubuntu', function (): void {
    $osRelease = fakeOsReleaseFile('Ubuntu', '24.04');
    putenv('SYSTEM_CHECK_OS_RELEASE='.$osRelease);

    $binaries = [
        'pdftotext' => createExecutableBinaryForTest(),
        'pdftoppm' => createExecutableBinaryForTest(),
        'pdfinfo' => createExecutableBinaryForTest(),
        'tesseract' => createExecutableBinaryForTest(),
        'soffice' => createExecutableBinaryForTest(),
    ];

    config()->set('document-extractor.binaries', $binaries);

    $exitCode = Artisan::call('system:check-binaries');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('Todos los binarios necesarios están instalados y son ejecutables.');

    // Limpiar
    foreach ($binaries as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }
    unlink($osRelease);
    putenv('SYSTEM_CHECK_OS_RELEASE=');
});

// ─── Binarios faltantes ──────────────────────────────────────────────

it('fails and shows apt install command when binaries are missing', function (): void {
    $osRelease = fakeOsReleaseFile('Ubuntu', '24.04');
    putenv('SYSTEM_CHECK_OS_RELEASE='.$osRelease);

    $executableBinary = createExecutableBinaryForTest();

    config()->set('document-extractor.binaries', [
        'pdftotext' => $executableBinary,
        'pdftoppm' => sys_get_temp_dir().'/missing-pdftoppm',
        'pdfinfo' => sys_get_temp_dir().'/missing-pdfinfo',
        'tesseract' => sys_get_temp_dir().'/missing-tesseract',
        'soffice' => sys_get_temp_dir().'/missing-soffice',
    ]);

    $exitCode = Artisan::call('system:check-binaries');
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('apt-get install')
        ->and($output)->toContain('poppler-utils')
        ->and($output)->toContain('tesseract-ocr')
        ->and($output)->toContain('libreoffice-core');

    unlink($executableBinary);
    unlink($osRelease);
    putenv('SYSTEM_CHECK_OS_RELEASE=');
});

it('groups duplicate packages correctly in apt install command', function (): void {
    $osRelease = fakeOsReleaseFile('Ubuntu', '24.04');
    putenv('SYSTEM_CHECK_OS_RELEASE='.$osRelease);

    $executable1 = createExecutableBinaryForTest();
    $executable2 = createExecutableBinaryForTest();
    $executable3 = createExecutableBinaryForTest();

    config()->set('document-extractor.binaries', [
        'pdftotext' => $executable1,
        'pdftoppm' => sys_get_temp_dir().'/missing-pdftoppm',
        'pdfinfo' => sys_get_temp_dir().'/missing-pdfinfo',
        'tesseract' => $executable2,
        'soffice' => $executable3,
    ]);

    $exitCode = Artisan::call('system:check-binaries');
    $output = Artisan::output();

    expect($exitCode)->toBe(1);

    // Extraer la línea del comando apt-get install
    $lines = explode("\n", $output);
    $aptLine = '';
    foreach ($lines as $line) {
        if (str_contains($line, 'apt-get install')) {
            $aptLine = $line;
            break;
        }
    }

    // poppler-utils debe aparecer una sola vez en el comando de instalación
    $popplerCount = substr_count($aptLine, 'poppler-utils');
    expect($popplerCount)->toBe(1);

    unlink($executable1);
    unlink($executable2);
    unlink($executable3);
    unlink($osRelease);
    putenv('SYSTEM_CHECK_OS_RELEASE=');
});

it('shows table with correct header and binary names', function (): void {
    $osRelease = fakeOsReleaseFile('Ubuntu', '24.04');
    putenv('SYSTEM_CHECK_OS_RELEASE='.$osRelease);

    $executableBinary = createExecutableBinaryForTest();

    config()->set('document-extractor.binaries', [
        'pdftotext' => $executableBinary,
        'soffice' => sys_get_temp_dir().'/missing-soffice',
    ]);

    $exitCode = Artisan::call('system:check-binaries');
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('Binarios del sistema')
        ->and($output)->toContain('pdftotext')
        ->and($output)->toContain('soffice');

    unlink($executableBinary);
    unlink($osRelease);
    putenv('SYSTEM_CHECK_OS_RELEASE=');
});

// ─── Detección de SO ─────────────────────────────────────────────────

it('fails when os-release file cannot be read', function (): void {
    putenv('SYSTEM_CHECK_OS_RELEASE=/nonexistent/path/os-release');

    $exitCode = Artisan::call('system:check-binaries');
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('No se pudo leer /etc/os-release');

    putenv('SYSTEM_CHECK_OS_RELEASE=');
});

it('fails when OS is not Ubuntu', function (): void {
    $osRelease = fakeOsReleaseFile('Debian', '12.0');
    putenv('SYSTEM_CHECK_OS_RELEASE='.$osRelease);

    $exitCode = Artisan::call('system:check-binaries');
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('Sistema operativo no soportado: Debian');

    unlink($osRelease);
    putenv('SYSTEM_CHECK_OS_RELEASE=');
});

it('fails when Ubuntu version is unsupported', function (): void {
    $osRelease = fakeOsReleaseFile('Ubuntu', '22.04');
    putenv('SYSTEM_CHECK_OS_RELEASE='.$osRelease);

    $exitCode = Artisan::call('system:check-binaries');
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('Versión de Ubuntu no soportada: 22.04');

    unlink($osRelease);
    putenv('SYSTEM_CHECK_OS_RELEASE=');
});

it('supports Ubuntu 26.04', function (): void {
    $osRelease = fakeOsReleaseFile('Ubuntu', '26.04');
    putenv('SYSTEM_CHECK_OS_RELEASE='.$osRelease);

    $binary = createExecutableBinaryForTest();

    config()->set('document-extractor.binaries', [
        'pdftotext' => $binary,
    ]);

    $exitCode = Artisan::call('system:check-binaries');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('Todos los binarios necesarios están instalados y son ejecutables.');

    unlink($binary);
    unlink($osRelease);
    putenv('SYSTEM_CHECK_OS_RELEASE=');
});

it('handles os-release with quoted values correctly', function (): void {
    $osRelease = fakeOsReleaseFile('Ubuntu', '24.04');
    putenv('SYSTEM_CHECK_OS_RELEASE='.$osRelease);

    $exitCode = Artisan::call('system:check-binaries');

    // El comando pudo haber fallado por binarios reales faltantes,
    // pero no por el parseo de os-release.
    // Verificamos que NO contenga mensajes de error de SO.
    $output = Artisan::output();
    expect($output)->not->toContain('No se pudo leer /etc/os-release')
        ->and($output)->not->toContain('Sistema operativo no soportado')
        ->and($output)->not->toContain('Versión de Ubuntu no soportada');

    unlink($osRelease);
    putenv('SYSTEM_CHECK_OS_RELEASE=');
});
