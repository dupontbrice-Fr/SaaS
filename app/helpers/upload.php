<?php
class Upload {
    public static function handle(array $file, string $destination, string $type = 'image'): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Erreur lors de l\'upload'];
        }

        $mime = mime_content_type($file['tmp_name']);

        switch ($type) {
            case 'image':
                $allowed = ALLOWED_IMAGE_TYPES;
                $maxSize = MAX_IMAGE_SIZE;
                break;
            case 'video':
                $allowed = ALLOWED_VIDEO_TYPES;
                $maxSize = MAX_VIDEO_SIZE;
                break;
            case 'pdf':
                $allowed = ALLOWED_PDF_TYPES;
                $maxSize = MAX_PDF_SIZE;
                break;
            case 'media':
                $allowed = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_VIDEO_TYPES, ALLOWED_PDF_TYPES);
                $maxSize = MAX_VIDEO_SIZE;
                break;
            default:
                $allowed = ALLOWED_IMAGE_TYPES;
                $maxSize = MAX_IMAGE_SIZE;
        }

        if (!in_array($mime, $allowed)) {
            return ['success' => false, 'error' => 'Type de fichier non autorisé: ' . $mime];
        }

        // Size validation per mime
        if (in_array($mime, ALLOWED_IMAGE_TYPES) && $file['size'] > MAX_IMAGE_SIZE) {
            return ['success' => false, 'error' => 'Image trop lourde (max 20 Mo)'];
        }
        if (in_array($mime, ALLOWED_VIDEO_TYPES) && $file['size'] > MAX_VIDEO_SIZE) {
            return ['success' => false, 'error' => 'Vidéo trop lourde (max 1 Go)'];
        }
        if (in_array($mime, ALLOWED_PDF_TYPES) && $file['size'] > MAX_PDF_SIZE) {
            return ['success' => false, 'error' => 'PDF trop lourd (max 50 Mo)'];
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('', true) . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
        $dir = UPLOAD_DIR . $destination . '/';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return ['success' => false, 'error' => 'Impossible de sauvegarder le fichier'];
        }

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $destination . '/' . $filename,
            'url' => UPLOAD_URL . $destination . '/' . $filename,
            'mime' => $mime,
            'size' => $file['size'],
            'original_name' => $file['name'],
        ];
    }

    public static function delete(string $path): void {
        $full = UPLOAD_DIR . $path;
        if (file_exists($full)) {
            unlink($full);
        }
    }

    public static function mediaType(string $mime): string {
        if (in_array($mime, ALLOWED_IMAGE_TYPES)) return 'image';
        if (in_array($mime, ALLOWED_VIDEO_TYPES)) return 'video';
        if (in_array($mime, ALLOWED_PDF_TYPES)) return 'pdf';
        return 'unknown';
    }

    public static function formatSize(int $bytes): string {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
