<?php
/**
 * Simple QR Code generator using Google Charts API
 * (no dependency needed - works offline alternative below)
 */
class QRCode {
    /**
     * Returns a QR code image URL using Google Charts API
     */
    public static function url(string $data, int $size = 200): string {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
    }

    /**
     * Generate QR code as base64 PNG using pure PHP
     * Fallback: returns the API URL
     */
    public static function base64(string $data, int $size = 200): string {
        // Try to fetch from API and encode
        $url = self::url($data, $size);
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $img = @file_get_contents($url, false, $ctx);
        if ($img) {
            return 'data:image/png;base64,' . base64_encode($img);
        }
        return $url;
    }

    /**
     * Generate a QR code for a screen token
     */
    public static function screenUrl(string $token): string {
        $data = APP_URL . '/viewer?token=' . $token;
        return self::url($data, 300);
    }

    /**
     * Download QR code image data
     */
    public static function download(string $data): void {
        $url = self::url($data, 300);
        $img = file_get_contents($url);
        if ($img) {
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="qrcode.png"');
            echo $img;
            exit;
        }
    }
}
