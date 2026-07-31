<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Landing Page — Spanish
    |--------------------------------------------------------------------------
    */

    'meta' => [
        'title'       => 'DocCompiler — Convierte cualquier documento en texto limpio para LLMs',
        'description' => 'DocCompiler es una API REST autoalojada y open source que convierte PDFs, Word, Excel, CSV, Markdown y archivos de texto en texto normalizado y eficiente en tokens para pipelines de IA.',
    ],

    'nav' => [
        'features'    => 'Funcionalidades',
        'pipeline'    => 'Cómo funciona',
        'quickstart'  => 'Inicio rápido',
        'github'      => 'GitHub',
        'lang_en'     => 'EN',
        'lang_es'     => 'ES',
    ],

    'hero' => [
        'eyebrow'   => '$ open&#8209;source&nbsp;&middot;&nbsp;autoalojado&nbsp;&middot;&nbsp;licencia MIT',
        'heading'   => 'Convierte cualquier documento en texto limpio, <span class="accent-text">listo para LLMs</span>.',
        'sub'       => 'DocCompiler es una API REST autoalojada que transforma PDFs, documentos Word, hojas de cálculo y CSVs en texto normalizado y eficiente en tokens — para que dejes de pagar por enviar archivos sin procesar a tus modelos, y dejes de confiar tus documentos a terceros.',
        'btn_github'   => 'Ver en GitHub',
        'btn_quickstart' => 'Guía de inicio rápido',
        'meta_laravel'  => 'Laravel + MySQL',
        'meta_docker'   => 'Listo para Docker',
        'meta_own_hw'   => 'Ejecuta en tu propio hardware',
    ],

    'compiler' => [
        'label'        => 'doccompiler &mdash; compilar',
        'output'       => 'salida',
        'tokens_saved' => 'tokens ahorrados',
    ],

    'features' => [
        'eyebrow' => '// qué hace',
        'heading' => 'Diseñado para integrarse silenciosamente en tu stack.',
        'cards' => [
            [
                'tag'  => '// autoalojado',
                'title' => 'Tu infraestructura, tus datos',
                'body'  => 'Instálalo en tu propio servidor. Los documentos se procesan y descartan en hardware que tú controlas — nada se envía a una API de extracción de terceros.',
            ],
            [
                'tag'  => '// cobertura de formatos',
                'title' => 'PDF, DOCX, XLSX, CSV, MD, TXT',
                'body'  => 'Un solo endpoint para todos los tipos de documentos comunes. Los archivos .doc y .xls heredados se normalizan automáticamente antes de la extracción.',
            ],
            [
                'tag'  => '// ocr automático',
                'title' => 'Páginas escaneadas, sin problema',
                'body'  => 'Cada página del PDF se verifica en busca de una capa de texto real. Las páginas sin ella se enrutan automáticamente por OCR — sin flags que configurar, sin trabajos que supervisar.',
            ],
            [
                'tag'  => '// basado en colas',
                'title' => 'Nunca bloquea tu API',
                'body'  => 'Construido sobre Laravel Horizon. Archivos grandes, trabajos de OCR y parsing de hojas de cálculo se ejecutan fuera del ciclo request/response.',
            ],
            [
                'tag'  => '// verificación MIME',
                'title' => 'Las extensiones no son de fiar',
                'body'  => 'Cada archivo se verifica contra su firma real antes de procesarlo — la misma defensa en profundidad que querrías en tu propio código.',
            ],
            [
                'tag'  => '// listo para producción',
                'title' => 'Observabilidad incluida',
                'body'  => 'Health checks, monitoreo de colas y métricas vienen con el proyecto — apunta tu panel de Grafana o Filament existente y listo.',
            ],
        ],
    ],

    'pipeline' => [
        'eyebrow' => '// el pipeline',
        'heading' => 'Cinco pasos, una sola estructura, siempre igual.',
        'steps' => [
            [
                'title' => 'Subir',
                'body'  => 'Envía cualquier archivo compatible a la API REST, con un bearer token emitido desde tu propio panel.',
            ],
            [
                'title' => 'Detectar',
                'body'  => 'El tipo MIME real se detecta desde el contenido del archivo y se verifica contra su extensión.',
            ],
            [
                'title' => 'Extraer',
                'body'  => 'El motor correspondiente se ejecuta — extracción nativa de texto, parsing de hojas de cálculo, u OCR por página cuando sea necesario.',
            ],
            [
                'title' => 'Normalizar',
                'body'  => 'Encabezados, tablas y estructura se reconstruyen en Markdown limpio y consistente — sin importar el formato de origen.',
            ],
            [
                'title' => 'Responder',
                'body'  => 'Recibes texto plano y estructurado — listo para incluir directamente en un prompt, sin manejar archivos por tu lado.',
            ],
        ],
    ],

    'quickstart' => [
        'eyebrow' => '// inicio rápido',
        'heading' => 'Funcionando en aproximadamente un minuto.',
        'sub'     => 'Clona el repositorio, levanta el stack y emite una clave API desde el panel de administración incluido. Sin cuentas, sin servicios externos, sin precios por página.',
        'copy'    => 'Copiar',
        'copied'  => 'Copiado',
        'terminal_label' => 'terminal',
        'curl_label'     => 'curl',
    ],

    'footer' => [
        'note'     => 'Código abierto bajo licencia MIT. Construido con Laravel.',
        'github'   => 'GitHub',
        'issues'   => 'Incidencias',
        'releases' => 'Versiones',
        'docs'     => 'Docs',
    ],

];
