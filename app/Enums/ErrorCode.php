<?php

namespace App\Enums;

enum ErrorCode: string
{
    case UnsupportedFormat = 'UNSUPPORTED_FORMAT';
    case CorruptedFile = 'CORRUPTED_FILE';
    case MimeMismatch = 'MIME_MISMATCH';
    case OcrBinaryMissing = 'OCR_BINARY_MISSING';
    case Timeout = 'TIMEOUT';
    case ZipBombSuspected = 'ZIP_BOMB_SUSPECTED';
}
