# Configuración API REST — Servicio de Extracción de Documentos a Texto para IA

Requiere haber implementado el modelo de datos de `01-modelo-datos.md`.

---

## 1. Dependencias Composer

```bash
composer require spatie/pdf-to-text
composer require phpoffice/phpword
composer require phpoffice/phpspreadsheet
composer require league/html-to-markdown
composer require league/csv
composer require league/commonmark
composer require thiagoalessio/tesseract_ocr
```

No agregar `spatie/pdf-to-image` (requiere Imagick + Ghostscript, una familia de
dependencias extra). En su lugar, rasterizar páginas para OCR con `pdftoppm`, que ya
viene incluido en `poppler-utils` junto con `pdftotext` — un binario menos que mantener
en el servidor.

## 2. Binarios de sistema (Forge / Vultr VPS)

```bash
sudo apt-get update
sudo apt-get install -y poppler-utils tesseract-ocr tesseract-ocr-spa libreoffice --no-install-recommends
```

- `poppler-utils` → `pdftotext`, `pdftoppm`, `pdfinfo`
- `tesseract-ocr` + `tesseract-ocr-spa` → OCR en español e inglés
- `libreoffice` → único fallback confiable para `.doc` legado (Word 97-2003), que ninguna
  librería PHP pura lee bien. Se normaliza a `.docx` con `soffice --headless --convert-to docx`
  antes de entrar al pipeline normal.

Verificar en el servidor antes de desplegar: `which pdftotext pdftoppm tesseract soffice`.

---

## 3. Config: `config/document-extractor.php`

```php
return [
    'max_upload_size_kb' => env('DOC_EXTRACTOR_MAX_KB', 25000), // 25MB

    'allowed_extensions' => ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'csv', 'md', 'txt'],

    'binaries' => [
        'pdftotext' => env('BIN_PDFTOTEXT', '/usr/bin/pdftotext'),
        'pdftoppm'  => env('BIN_PDFTOPPM', '/usr/bin/pdftoppm'),
        'pdfinfo'   => env('BIN_PDFINFO', '/usr/bin/pdfinfo'),
        'tesseract' => env('BIN_TESSERACT', '/usr/bin/tesseract'),
        'soffice'   => env('BIN_SOFFICE', '/usr/bin/soffice'),
    ],

    'process_timeout_seconds' => env('DOC_EXTRACTOR_TIMEOUT', 120),

    'ocr' => [
        'languages' => env('DOC_EXTRACTOR_OCR_LANG', 'spa+eng'),
        // si una página de PDF tiene menos caracteres extraíbles que esto,
        // se asume "sin capa de texto" y se manda a OCR
        'min_chars_per_page_threshold' => 10,
        'dpi' => 300,
    ],

    'storage' => [
        'disk' => env('DOC_EXTRACTOR_DISK', 'local'),
        'temp_path' => storage_path('app/document-extractor/tmp'),
        'content_spillover_threshold_bytes' => 2 * 1024 * 1024, // 2MB
        'retention_days' => env('DOC_EXTRACTOR_RETENTION_DAYS', 7),
    ],

    'chunking' => [
        'enabled' => false,
        'max_tokens' => 4000,
    ],

    'queue' => [
        'connection' => env('DOC_EXTRACTOR_QUEUE_CONNECTION', 'redis'),
        'queue' => env('DOC_EXTRACTOR_QUEUE_NAME', 'documents'),
    ],
];
```

---

## 4. Detección MIME robusta (no confiar nunca en la extensión ni el `Content-Type` del cliente)

Regla de validación personalizada:

```php
// app/Rules/RealMimeType.php
class RealMimeType implements ValidationRule
{
    private const MAP = [
        'pdf'  => ['application/pdf'],
        'docx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xlsx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'doc'  => ['application/msword', 'application/x-ole-storage'],
        'xls'  => ['application/vnd.ms-excel', 'application/x-ole-storage'],
        'csv'  => ['text/plain', 'text/csv'],
        'txt'  => ['text/plain'],
        'md'   => ['text/plain', 'text/markdown'],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $real = $finfo->file($value->getRealPath());
        $ext = strtolower($value->getClientOriginalExtension());

        if (!isset(self::MAP[$ext]) || !in_array($real, self::MAP[$ext], true)) {
            $fail("El archivo declara .$ext pero su contenido real es $real.");
        }
    }
}
```

Capas adicionales de verificación (todas antes de tocar el contenido del archivo):

1. **DOCX/XLSX son ZIP**: intentar `ZipArchive::open($path)` antes de pasarlo a
   PHPWord/PhpSpreadsheet. Si falla, es corrupto o no es realmente OOXML → `CORRUPTED_FILE`.
2. **Zip-bomb guard**: antes de descomprimir, revisar `ZipArchive::statIndex()` de cada
   entrada y sumar `size` sin descomprimir nada; si el total supera un límite razonable
   (ej. 200MB descomprimido) → `ZIP_BOMB_SUSPECTED`, rechazar sin procesar.
3. **Magic bytes PDF**: primeros 4 bytes deben ser `%PDF`. Barato de chequear antes de
   invocar `pdftotext`.
4. Nunca usar `$request->file('document')->getClientOriginalName()` para construir rutas
   de filesystem — solo para mostrarlo de vuelta al cliente. El nombre físico en disco
   siempre es el `uuid` del job.

Dado tu trabajo reciente con malware en WordPress, aplica aquí el mismo principio de
defensa en profundidad: la extensión declarada, el MIME declarado y el contenido real
deben coincidir los tres, y el archivo se procesa fuera de cualquier directorio servible
públicamente.

---

## 5. Rutas — `routes/api.php`

```php
Route::get('/health', HealthCheckController::class);

Route::middleware(['auth.apiclient', 'throttle:api-client'])
    ->prefix('v1')
    ->group(function () {
        Route::post('/documents', [DocumentController::class, 'store']);
        Route::get('/documents/{uuid}', [DocumentController::class, 'show']);
        Route::get('/documents/{uuid}/content', [DocumentController::class, 'content']);
        Route::delete('/documents/{uuid}', [DocumentController::class, 'destroy']);
    });
```

## 6. Middleware de autenticación — `app/Http/Middleware/EnsureValidApiClient.php`

```php
class EnsureValidApiClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $hashed = $token ? hash('sha256', $token) : null;

        $client = $hashed
            ? ApiClient::where('token', $hashed)->where('is_active', true)->first()
            : null;

        if (!$client) {
            return response()->json(['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Token inválido']], 401);
        }

        $client->update(['last_used_at' => now()]);
        $request->attributes->set('apiClient', $client);

        return $next($request);
    }
}
```

Registro (Laravel 11+/13, sin `Kernel.php`) en `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias(['auth.apiclient' => \App\Http\Middleware\EnsureValidApiClient::class]);
})
```

Throttle por cliente, no por IP (varios proyectos personales pueden compartir red):

```php
// AppServiceProvider::boot()
RateLimiter::for('api-client', function (Request $request) {
    $client = $request->attributes->get('apiClient');
    $limit = $client?->rate_limit_per_minute ?? 30;
    return Limit::perMinute($limit)->by($client?->id ?? $request->ip());
});
```

---

## 7. Flujo de la petición

1. `DocumentController::store` valida (`max:` tamaño, extensión permitida, `RealMimeType`),
   crea `ExtractionJob` (`status = pending`), mueve el archivo a
   `storage/app/document-extractor/tmp/{uuid}.{ext}`, despacha el job y responde `202`:

```json
{ "uuid": "…", "status": "pending", "status_url": "/api/v1/documents/…" }
```

2. `ProcessDocumentExtraction` (job en cola) resuelve el driver correcto vía un
   `ExtractorManager` (extiende `Illuminate\Support\Manager`, mismo patrón que usa
   Laravel para Cache/Queue — cada formato es un "driver" registrable con `extend()`).

3. Cada driver implementa un contrato común:

```php
interface DocumentExtractorContract
{
    public function extract(string $path): ExtractionResultData;
}
```

4. **Pipeline PDF** (el más delicado):
   - `pdfinfo` para obtener número de páginas.
   - `pdftotext -layout` página por página (`-f N -l N`), contar caracteres por página.
   - Páginas bajo `ocr.min_chars_per_page_threshold` → se consideran "sin capa de texto":
     `pdftoppm -png -r 300` sobre esa página → imagen → `tesseract` con `-l spa+eng` →
     texto de esa página.
   - Se concatena todo en orden de página, se marca `ocr_used = true` y se guarda
     cuántas páginas requirieron OCR (`pages_ocr`).
   - Esto evita mandar TODO el PDF a OCR cuando solo 2 de 40 páginas son escaneadas.

5. **Pipeline DOCX**: PHPWord lee → `HTML::save()` (writer HTML) → `league/html-to-markdown`
   con `TableConverter` habilitado → Markdown limpio.

6. **Pipeline DOC legado**: `soffice --headless --convert-to docx --outdir {tmp} {path}`
   primero, luego mismo pipeline que DOCX.

7. **Pipeline XLSX/XLS**: PhpSpreadsheet con `setReadDataOnly(true)`, iterar filas (no
   cargar toda la hoja de una vez si es grande) y construir una tabla Markdown por hoja.

8. **Pipeline CSV**: `league/csv` en modo streaming (`Reader::createFromPath`), convertir
   a tabla Markdown igual que XLSX para output consistente.

9. **Pipeline MD/TXT**: lectura directa; si es `.md`, opcionalmente re-normalizar con
   `league/commonmark` para garantizar sintaxis consistente.

10. Capa final `TextNormalizer`: colapsa espacios/saltos redundantes, calcula
    `char_count` y `estimated_tokens` (`char_count / 4` como heurística simple), cuenta
    headings/tablas, decide si el contenido va a `content` (DB) o a disco según
    `content_spillover_threshold_bytes`.

11. Guarda `ExtractionResult`, marca `extraction_jobs.status = completed`, borra el
    archivo original temporal (a menos que se configure conservarlo).

12. En cualquier excepción de cualquier paso: `status = failed`, `error_code` según el
    catálogo del enum `ErrorCode`, log en `extraction_job_events`.

---

## 8. Timeouts y límites de proceso

- Cada `Symfony\Component\Process\Process` (pdftotext, pdftoppm, tesseract, soffice) con
  `->setTimeout(config('document-extractor.process_timeout_seconds'))` explícito — un
  archivo malicioso o corrupto no debe colgar un worker de cola indefinidamente.
- El job de cola (`ProcessDocumentExtraction`) con `public $timeout` mayor a la suma de
  binarios (ej. 300s), y `--timeout=310` en la configuración del worker de Forge.
- PhpSpreadsheet con archivos grandes: usar lectura en streaming/chunks, nunca asumir
  memoria ilimitada en el VPS.

---

## 9. Manejo de errores — `bootstrap/app.php`

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (Throwable $e, Request $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'error' => ['code' => 'INTERNAL_ERROR', 'message' => $e->getMessage()],
            ], 500);
        }
    });
})
```

Respuestas de error de negocio (mismatch de mime, formato no soportado, etc.) se
devuelven desde el controlador/`FormRequest`, no desde el handler global.

---

## 10. Health check — `HealthCheckController`

```php
public function __invoke()
{
    $bins = collect(config('document-extractor.binaries'))
        ->mapWithKeys(fn ($path, $name) => [$name => is_executable($path)]);

    return response()->json([
        'binaries' => $bins,
        'queue_connection' => config('document-extractor.queue.connection'),
        'disk_free_mb' => round(disk_free_space(storage_path()) / 1024 / 1024),
    ]);
}
```

Útil para verificar rápido en Forge que un binario no quedó sin instalar tras un
reprovisioning del servidor.

---

## 11. Limpieza programada — `routes/console.php`

```php
Schedule::command('documents:purge')->daily();
```

```php
// app/Console/Commands/PurgeExtractionJobs.php
public function handle(): void
{
    $cutoff = now()->subDays(config('document-extractor.storage.retention_days'));

    ExtractionJob::whereIn('status', ['completed', 'failed'])
        ->where('completed_at', '<', $cutoff)
        ->whereNotNull('storage_path')
        ->cursor()
        ->each(function (ExtractionJob $job) {
            Storage::disk(config('document-extractor.storage.disk'))->delete($job->storage_path);
            $job->update(['storage_path' => null]);
        });
}
```

---

## 12. Testing (Pest)

Un test de feature por formato, con fixtures reales en `tests/Fixtures/`:
`sample.pdf` (con capa de texto), `sample-scanned.pdf` (para forzar OCR), `sample.docx`,
`sample-legacy.doc`, `sample.xlsx`, `sample.csv`, `sample.md`.

```php
it('extracts text from a native pdf without triggering ocr', function () {
    $client = ApiClient::factory()->create();

    $response = $this->withToken($client->plainToken)
        ->post('/api/v1/documents', ['document' => UploadedFile::fake()->...]);

    $response->assertStatus(202);
    // poll o ejecutar el job síncrono en el test con Queue::fake() / assertPushed
});
```

Test unitario aparte por cada driver del `ExtractorManager`, sin pasar por HTTP.

---

## 13. Checklist de implementación

- [ ] `composer require` de las 6 librerías
- [ ] binarios de sistema instalados y verificados en el servidor
- [ ] 5 migraciones + modelos + enums (archivo 1)
- [ ] `config/document-extractor.php`
- [ ] `App\Rules\RealMimeType`
- [ ] `ExtractorManager` + 6 drivers (pdf, docx, doc_legacy, xlsx, csv, markdown/txt)
- [ ] `ProcessDocumentExtraction` (job)
- [ ] `DocumentController` + `FormRequest` de validación
- [ ] `EnsureValidApiClient` middleware + rate limiter
- [ ] `routes/api.php`
- [ ] `HealthCheckController`
- [ ] `PurgeExtractionJobs` command + schedule
- [ ] tests Pest (feature por formato + unitarios por driver)
