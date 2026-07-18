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

    'storage' => [
        /*
        | Número de días tras los cuales se eliminan los archivos originales
        | y los registros de extracción completados o fallidos.
        */
        'retention_days' => env('DOC_EXTRACTOR_RETENTION_DAYS', 7),

        /*
        | Umbral en bytes a partir del cual el contenido extraído se guarda
        | en disco en lugar de la columna `content` de `extraction_results`.
        | Valor por defecto: 2 MB.
        */
        'result_spillover_threshold_bytes' => env('DOC_EXTRACTOR_RESULT_SPILLOVER_BYTES', 2 * 1024 * 1024),

        /*
        | Disco de almacenamiento para el spillover de resultados grandes.
        */
        'result_disk' => env('DOC_EXTRACTOR_RESULT_DISK', 'local'),
    ],

    'chunking' => [
        /*
        | Habilita el registro de chunks de texto extraído en la tabla
        | `extraction_chunks`.
        */
        'enabled' => env('DOC_EXTRACTOR_CHUNKING_ENABLED', true),
    ],

];
