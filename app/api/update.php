<?php
/**
 * OTA Update endpoint — utilisé par @capgo/capacitor-updater
 *
 * L'APK envoie un POST avec ses informations (version courante, plateforme…).
 * Ce script retourne le dernier bundle disponible ou HTTP 204 si rien de nouveau.
 *
 * Format de réponse attendu par @capgo/capacitor-updater :
 * {
 *   "version": "1.2.3",
 *   "url": "https://example.com/updates/bundle-1.2.3.zip"
 * }
 */

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$latestPath = __DIR__ . '/../public/updates/latest.json';

if (!file_exists($latestPath)) {
    // Aucun bundle déployé — pas de mise à jour disponible
    http_response_code(204);
    exit;
}

$latest = json_decode(file_get_contents($latestPath), true);

if (empty($latest['version']) || empty($latest['url'])) {
    http_response_code(204);
    exit;
}

// Optionnel : comparer la version envoyée par l'APK pour éviter
// de retourner la même version que celle déjà installée.
$body           = json_decode(file_get_contents('php://input'), true) ?? [];
$currentVersion = $body['version_name'] ?? '';

if ($currentVersion && $currentVersion === $latest['version']) {
    // Déjà à jour
    http_response_code(204);
    exit;
}

echo json_encode($latest);
