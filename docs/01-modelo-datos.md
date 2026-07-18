# Modelo de Datos — Servicio de Extracción de Documentos a Texto para IA

Stack: Laravel 13, MySQL 8+, PHP 8.2+
Uso: servicio interno consumido por proyectos personales (M2M, sin usuarios finales).

---

## Decisión arquitectónica: autenticación

No usar Laravel Sanctum con modelo `User`. Es un servicio máquina-a-máquina consumido por
tus propios proyectos (META Escritos, Voxlitycs, etc.), no por navegadores. Un token opaco
por "cliente" (proyecto) es más simple y evita acoplar todo a un modelo de usuario que no existe
en este contexto.

---

## Entidades

### 1. `api_clients`
Representa cada proyecto personal que consume el servicio.

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| uuid | char(36) unique | identificador público, no exponer el id incremental |
| name | string(80) | ej. `meta-escritos`, `voxlitycs` |
| token | string(64) unique | `hash('sha256', $plainToken)`, el plano solo se muestra 1 vez al crearlo |
| is_active | boolean default true | |
| rate_limit_per_minute | unsigned int default 60 | |
| last_used_at | timestamp nullable | |
| timestamps | | |

### 2. `extraction_jobs`
Un registro por documento subido.

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| uuid | char(36) unique | id público devuelto al cliente |
| api_client_id | FK -> api_clients | |
| original_filename | string(255) | solo informativo, nunca usar para paths |
| extension | string(10) | |
| declared_mime_type | string(100) nullable | lo que reporta el cliente/navegador |
| detected_mime_type | string(100) nullable | resultado real de `finfo` server-side |
| size_bytes | unsigned big integer | |
| storage_path | string(255) nullable | archivo original temporal; se borra tras procesar |
| status | string(20) | enum PHP: `pending`\|`processing`\|`completed`\|`failed` |
| extractor_driver | string(20) nullable | `pdf_native`\|`pdf_ocr`\|`docx`\|`doc_legacy`\|`xlsx`\|`csv`\|`markdown`\|`plain_text` |
| ocr_used | boolean default false | |
| pages_total | unsigned int nullable | |
| pages_ocr | unsigned int nullable | cuántas páginas requirieron OCR |
| error_code | string(40) nullable | `UNSUPPORTED_FORMAT`\|`CORRUPTED_FILE`\|`MIME_MISMATCH`\|`OCR_BINARY_MISSING`\|`TIMEOUT`\|`ZIP_BOMB_SUSPECTED` |
| error_message | text nullable | |
| started_at / completed_at | timestamp nullable | |
| duration_ms | unsigned int nullable | |
| timestamps | | |

Índices: `uuid` unique, `status`, `api_client_id + status` (compuesto), `created_at` (para limpieza).

### 3. `extraction_results`
Relación 1:1 con `extraction_jobs`.

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| extraction_job_id | FK unique -> extraction_jobs | |
| output_format | string(20) | enum: `markdown`\|`plain_text` |
| content | longText nullable | null si se usa spillover a disco |
| content_disk | string(20) nullable | `local`, se define en config si el texto supera el umbral |
| content_path | string(255) nullable | ruta cuando el contenido vive en disco |
| char_count | unsigned int | |
| estimated_tokens | unsigned int | heurística `char_count/4` o tiktoken si se integra |
| headings_count | unsigned smallint default 0 | |
| tables_count | unsigned smallint default 0 | |
| language_detected | string(10) nullable | ej. `es`, `en` |
| timestamps | | |

Regla: si `char_count` > umbral configurable (ej. 2 MB de texto), guardar en disco y dejar
`content` en null. Evita filas gigantes en InnoDB y problemas de `max_allowed_packet`.

### 4. `extraction_chunks` (opcional, solo si `chunking.enabled`)

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| extraction_job_id | FK -> extraction_jobs | |
| chunk_index | unsigned int | |
| content | mediumText | |
| token_count | unsigned int | |
| timestamps | | |

### 5. `extraction_job_events` (auditoría ligera, útil para debugging)

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| extraction_job_id | FK -> extraction_jobs | |
| event | string(40) | `queued`\|`mime_detected`\|`ocr_started`\|`ocr_completed`\|`failed`\|`purged` |
| payload | json nullable | |
| created_at | timestamp | (sin updated_at, es un log append-only) |

---

## Relaciones Eloquent

```
ApiClient
  hasMany ExtractionJob

ExtractionJob
  belongsTo ApiClient
  hasOne ExtractionResult
  hasMany ExtractionChunk
  hasMany ExtractionJobEvent

ExtractionResult
  belongsTo ExtractionJob
```

---

## Enums nativos PHP (crear manualmente en `app/Enums/`, no depender de un artisan
generator que no existe en core Laravel)

```php
// app/Enums/JobStatus.php
enum JobStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}

// app/Enums/ExtractorDriver.php
enum ExtractorDriver: string
{
    case PdfNative = 'pdf_native';
    case PdfOcr = 'pdf_ocr';
    case Docx = 'docx';
    case DocLegacy = 'doc_legacy';
    case Xlsx = 'xlsx';
    case Csv = 'csv';
    case Markdown = 'markdown';
    case PlainText = 'plain_text';
}

// app/Enums/OutputFormat.php
enum OutputFormat: string
{
    case Markdown = 'markdown';
    case PlainText = 'plain_text';
}

// app/Enums/ErrorCode.php
enum ErrorCode: string
{
    case UnsupportedFormat = 'UNSUPPORTED_FORMAT';
    case CorruptedFile = 'CORRUPTED_FILE';
    case MimeMismatch = 'MIME_MISMATCH';
    case OcrBinaryMissing = 'OCR_BINARY_MISSING';
    case Timeout = 'TIMEOUT';
    case ZipBombSuspected = 'ZIP_BOMB_SUSPECTED';
}
```

Castear en los modelos con `protected $casts = ['status' => JobStatus::class, ...]`.

---

## Comandos para generar el esqueleto

```bash
php artisan make:model ApiClient -m
php artisan make:model ExtractionJob -m
php artisan make:model ExtractionResult -m
php artisan make:model ExtractionChunk -m
php artisan make:model ExtractionJobEvent -m
```

Luego crear a mano `app/Enums/{JobStatus,ExtractorDriver,OutputFormat,ErrorCode}.php`.

---

## Retención y limpieza (importante para uso personal, evita que el disco/DB crezca sin control)

Config (ver archivo 2, `config/document-extractor.php`):

```php
'storage' => [
    'retention_days' => env('DOC_EXTRACTOR_RETENTION_DAYS', 7),
],
```

Comando programado `documents:purge` (detallado en el archivo 2) que:
1. Borra `storage_path` del archivo original de todo job con `completed_at` o `failed`
   más antiguo que `retention_days`.
2. Opcional: borra también la fila de `extraction_results` si quieres purga agresiva total,
   o solo el archivo si prefieres conservar el texto extraído indefinidamente (más barato en
   disco que guardar el original).

Foreign keys con `onDelete('cascade')` en `extraction_results`, `extraction_chunks` y
`extraction_job_events` hacia `extraction_jobs`, para que borrar un job limpie todo su rastro
en una sola operación.

---

## Migración de ejemplo (extraction_jobs, la tabla central)

```php
Schema::create('extraction_jobs', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('api_client_id')->constrained()->cascadeOnDelete();
    $table->string('original_filename');
    $table->string('extension', 10);
    $table->string('declared_mime_type', 100)->nullable();
    $table->string('detected_mime_type', 100)->nullable();
    $table->unsignedBigInteger('size_bytes');
    $table->string('storage_path')->nullable();
    $table->string('status', 20)->default('pending');
    $table->string('extractor_driver', 20)->nullable();
    $table->boolean('ocr_used')->default(false);
    $table->unsignedInteger('pages_total')->nullable();
    $table->unsignedInteger('pages_ocr')->nullable();
    $table->string('error_code', 40)->nullable();
    $table->text('error_message')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->unsignedInteger('duration_ms')->nullable();
    $table->timestamps();

    $table->index('status');
    $table->index(['api_client_id', 'status']);
    $table->index('created_at');
});
```

Las demás migraciones siguen la misma tabla de columnas de arriba; no las repito aquí para
no inflar el archivo, pero deben respetar exactamente los tipos indicados.
