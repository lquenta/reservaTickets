<?php

namespace App\Support;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

final class TicketQrCode
{
    /**
     * Genera un data URI (PNG base64) del QR con el código del ticket.
     * Formato: payment_code-position (ej. EV-ABC123-1).
     */
    public static function dataUri(string $paymentCode, int $position, int $size = 120): string
    {
        $data = $paymentCode . '-' . $position;
        $writer = new PngWriter();
        $qrCode = new QrCode(
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: $size,
            margin: 5,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );
        $result = $writer->write($qrCode, null, null, []);

        return $result->getDataUri();
    }
}
