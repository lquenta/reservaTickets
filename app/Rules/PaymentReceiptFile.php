<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class PaymentReceiptFile implements ValidationRule
{
    /** @var list<string> */
    private const IMAGE_EXTENSIONS = [
        'jpg', 'jpeg', 'jpe', 'jfif', 'png', 'gif', 'webp', 'bmp',
        'heic', 'heif', 'tif', 'tiff', 'avif', 'ico', 'svg',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('El comprobante debe ser una imagen o un PDF.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $mime = strtolower((string) $value->getMimeType());

        $isPdf = $extension === 'pdf' || str_contains($mime, 'pdf');
        $isImage = str_starts_with($mime, 'image/') || in_array($extension, self::IMAGE_EXTENSIONS, true);

        if (! $isPdf && ! $isImage) {
            $fail('El comprobante debe ser una imagen o un PDF.');
        }
    }
}
