<?php

namespace App\Enums;

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
