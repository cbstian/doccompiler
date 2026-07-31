<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Landing Page — English
    |--------------------------------------------------------------------------
    */

    'meta' => [
        'title'       => 'DocCompiler — Compile any document into clean, LLM-ready text',
        'description' => 'DocCompiler is a self-hosted, open source REST API that converts PDFs, Word, Excel, CSV, Markdown and text files into normalized, token-efficient text for AI pipelines.',
    ],

    'nav' => [
        'features'    => 'Features',
        'pipeline'    => 'How it works',
        'quickstart'  => 'Quickstart',
        'github'      => 'GitHub',
        'lang_en'     => 'EN',
        'lang_es'     => 'ES',
    ],

    'hero' => [
        'eyebrow'   => '$ open&#8209;source&nbsp;&middot;&nbsp;self&#8209;hosted&nbsp;&middot;&nbsp;MIT license',
        'heading'   => 'Compile any document into clean, <span class="accent-text">LLM&#8209;ready</span> text.',
        'sub'       => 'DocCompiler is a self-hosted REST API that turns PDFs, Word docs, spreadsheets and CSVs into normalized, token-efficient text — so you stop paying to ship raw files to your models, and stop trusting a third party with your documents.',
        'btn_github'   => 'View on GitHub',
        'btn_quickstart' => 'Read the quickstart',
        'meta_laravel'  => 'Laravel + MySQL',
        'meta_docker'   => 'Docker-ready',
        'meta_own_hw'   => 'Runs on your own hardware',
    ],

    'compiler' => [
        'label'        => 'doccompiler &mdash; compile',
        'output'       => 'output',
        'tokens_saved' => 'tokens saved',
    ],

    'features' => [
        'eyebrow' => '// what it does',
        'heading' => 'Built to sit quietly inside your stack.',
        'cards' => [
            [
                'tag'  => '// self-hosted',
                'title' => 'Your infrastructure, your data',
                'body'  => 'Install it on your own server. Documents are processed and discarded on hardware you control — nothing is sent to a third-party extraction API.',
            ],
            [
                'tag'  => '// format coverage',
                'title' => 'PDF, DOCX, XLSX, CSV, MD, TXT',
                'body'  => 'One endpoint, every common document type. Legacy .doc and .xls are normalized automatically before extraction.',
            ],
            [
                'tag'  => '// ocr fallback',
                'title' => 'Scanned pages, handled',
                'body'  => 'Each PDF page is checked for a real text layer. Pages without one are routed through OCR automatically — no flag to set, no job to babysit.',
            ],
            [
                'tag'  => '// queue-based',
                'title' => 'Never blocks your API',
                'body'  => 'Built on Laravel Horizon. Large files, OCR jobs and spreadsheet parsing all run off the request/response cycle.',
            ],
            [
                'tag'  => '// mime verified',
                'title' => 'Extensions are never trusted',
                'body'  => 'Every upload is checked against its real file signature before processing — the same defense-in-depth you\'d want in your own code.',
            ],
            [
                'tag'  => '// production ready',
                'title' => 'Observability included',
                'body'  => 'Health checks, queue monitoring and metrics ship with the project — point your existing Grafana or Filament panel at it and go.',
            ],
        ],
    ],

    'pipeline' => [
        'eyebrow' => '// the pipeline',
        'heading' => 'Five steps, one shape, every time.',
        'steps' => [
            [
                'title' => 'Upload',
                'body'  => 'Send any supported file to the REST API, with a bearer token issued from your own dashboard.',
            ],
            [
                'title' => 'Detect',
                'body'  => 'The real MIME type is sniffed from the file\'s contents and cross-checked against its extension.',
            ],
            [
                'title' => 'Extract',
                'body'  => 'The matching engine runs — native text extraction, spreadsheet parsing, or page-level OCR when needed.',
            ],
            [
                'title' => 'Normalize',
                'body'  => 'Headings, tables and structure are rebuilt into clean, consistent Markdown — regardless of source format.',
            ],
            [
                'title' => 'Respond',
                'body'  => 'You get back plain, structured text — ready to drop straight into a prompt, no file handling on your side.',
            ],
        ],
    ],

    'quickstart' => [
        'eyebrow' => '// quickstart',
        'heading' => 'Running in about a minute.',
        'sub'     => 'Clone the repo, bring up the stack, and issue yourself an API key from the included admin panel. No accounts, no external services, no per-page pricing.',
        'copy'    => 'Copy',
        'copied'  => 'Copied',
        'terminal_label' => 'terminal',
        'curl_label'     => 'curl',
    ],

    'footer' => [
        'note'     => 'Open source under the MIT license. Built with Laravel.',
        'github'   => 'GitHub',
        'issues'   => 'Issues',
        'releases' => 'Releases',
        'docs'     => 'Docs',
    ],

];
