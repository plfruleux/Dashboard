<?php
/**
 * rss_podcast.php — Proxy RSS pour les podcasts Radio France
 *
 * Contourne les restrictions CORS de Radio France en faisant
 * la requête côté serveur avec un User-Agent navigateur.
 *
 * Usage : /rss_podcast.php?url=https://radiofrance-podcast.net/...
 *
 * Sécurité : whitelist stricte des domaines autorisés.
 * Cache :    5 minutes (304 si non modifié).
 */

/* ── Whitelist domaines autorisés ── */
$ALLOWED_DOMAINS = [
    'radiofrance-podcast.net',
    'radiofrance.fr',
    'www.radiofrance.fr',
];

/* ── Récupération et validation de l'URL ── */
$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (!$url) {
    http_response_code(400);
    header('Content-Type: text/plain');
    exit('Paramètre url manquant.');
}

$host = parse_url($url, PHP_URL_HOST);
if (!$host) {
    http_response_code(400);
    header('Content-Type: text/plain');
    exit('URL invalide.');
}

/* Vérification whitelist */
$allowed = false;
foreach ($ALLOWED_DOMAINS as $domain) {
    if ($host === $domain || substr($host, -(strlen($domain) + 1)) === '.' . $domain) {
        $allowed = true;
        break;
    }
}
if (!$allowed) {
    http_response_code(403);
    header('Content-Type: text/plain');
    exit('Domaine non autorisé : ' . htmlspecialchars($host));
}

/* ── Récupération via cURL ── */
if (!function_exists('curl_init')) {
    http_response_code(500);
    exit('cURL non disponible sur ce serveur.');
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 4,
    CURLOPT_TIMEOUT        => 12,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER     => [
        'Accept: application/rss+xml, application/xml, text/xml, */*',
        'Accept-Language: fr-FR,fr;q=0.9',
        'Cache-Control: no-cache',
    ],
]);

$body   = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err    = curl_error($ch);
curl_close($ch);

if ($err) {
    http_response_code(502);
    header('Content-Type: text/plain');
    exit('Erreur cURL : ' . $err);
}

if ($status !== 200) {
    http_response_code(502);
    header('Content-Type: text/plain');
    exit('Erreur upstream HTTP ' . $status);
}

if (!$body || strlen($body) < 50) {
    http_response_code(502);
    header('Content-Type: text/plain');
    exit('Réponse vide du serveur Radio France.');
}

/* ── Réponse ── */
header('Content-Type: application/xml; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300'); // cache 5 min
header('X-Proxy-Status: ok');
header('X-Upstream: ' . htmlspecialchars($host));

echo $body;
