<?php

namespace App\Rules;

use Closure;
use finfo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class RealMimeType implements ValidationRule
{
    /**
     * @var array<string, list<string>>
     */
    private const MAP = [
        'pdf' => ['application/pdf'],
        'docx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xlsx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'doc' => ['application/msword', 'application/x-ole-storage'],
        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage'],
        'csv' => ['text/plain', 'text/csv'],
        'txt' => ['text/plain'],
        'md' => ['text/plain', 'text/markdown'],
    ];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! method_exists($value, 'getRealPath') || ! method_exists($value, 'getClientOriginalExtension')) {
            $fail('El archivo no puede ser inspeccionado.');

            return;
        }

        $extension = strtolower((string) $value->getClientOriginalExtension());
        $realPath = $value->getRealPath();
        $mimeType = $realPath ? (new finfo(FILEINFO_MIME_TYPE))->file($realPath) : false;

        if (! $mimeType || ! isset(self::MAP[$extension]) || ! in_array($mimeType, self::MAP[$extension], true)) {
            $fail("El archivo declara .{$extension} pero su contenido real es {$mimeType}.");
        }
    }
}
