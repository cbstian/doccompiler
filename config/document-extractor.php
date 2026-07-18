<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document Extractor Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración del servicio de extracción de documentos a texto para IA.
    | Servicio M2M consumido por proyectos personales, sin usuarios finales.
    |
    */

    'max_upload_size_kb' => env('DOC_EXTRACTOR_MAX_KB', 25000),

    'allowed_extensions' => ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'csv', 'md', 'txt'],

    'binaries' => [
        'pdftotext' => env('BIN_PDFTOTEXT', '/usr/bin/pdftotext'),
        'pdftoppm' => env('BIN_PDFTOPPM', '/usr/bin/pdftoppm'),
        'pdfinfo' => env('BIN_PDFINFO', '/usr/bin/pdfinfo'),
        'tesseract' => env('BIN_TESSERACT', '/usr/bin/tesseract'),
        'soffice' => env('BIN_SOFFICE', '/usr/bin/soffice'),
    ],

    'process_timeout_seconds' => env('DOC_EXTRACTOR_TIMEOUT', 120),

    'ocr' => [
        'languages' => env('DOC_EXTRACTOR_OCR_LANG', 'spa+eng'),
        'min_chars_per_page_threshold' => 10,
        'dpi' => 300,
    ],

    'storage' => [
        /*
        | Número de días tras los cuales se eliminan los archivos originales
        | y los registros de extracción completados o fallidos.
        */
        'retention_days' => env('DOC_EXTRACTOR_RETENTION_DAYS', 7),

        'disk' => env('DOC_EXTRACTOR_DISK', 'local'),

        'temp_path' => storage_path('app/document-extractor/tmp'),

        /*
        | Umbral en bytes a partir del cual el contenido extraído se guarda
        | en disco en lugar de la columna `content` de `extraction_results`.
        | Valor por defecto: 2 MB.
        */
        'content_spillover_threshold_bytes' => env('DOC_EXTRACTOR_RESULT_SPILLOVER_BYTES', 2 * 1024 * 1024),

        /*
        | Disco de almacenamiento para el spillover de resultados grandes.
        */
        'result_disk' => env('DOC_EXTRACTOR_RESULT_DISK', env('DOC_EXTRACTOR_DISK', 'local')),
    ],

    'chunking' => [
        /*
        | Habilita el registro de chunks de texto extraído en la tabla
        | `extraction_chunks`.
        */
        'enabled' => env('DOC_EXTRACTOR_CHUNKING_ENABLED', false),

        'max_tokens' => env('DOC_EXTRACTOR_CHUNK_MAX_TOKENS', 4000),
    ],

    'queue' => [
        'connection' => env('DOC_EXTRACTOR_QUEUE_CONNECTION', 'redis'),
        'queue' => env('DOC_EXTRACTOR_QUEUE_NAME', 'documents'),
    ],

];
