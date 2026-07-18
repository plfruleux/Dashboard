<?php
/**
 * rss.php — Proxy RSS Le Monde côté serveur
 * Cache 15 minutes. Retourne un tableau JSON d'articles.
 * Appelé par le Dashboard via XHR : /rss.php
 */

$CACHE_FILE = __DIR__ . '/rss_cache.json';
$CACHE_TTL  = 15 * 60; // 15 minutes
$RSS_URL    = 'https://www.lemonde.fr/rss/une.xml';
$MAX_ITEMS  = 20;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

/* ── Servir le cache s'il est frais ── */
if (file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) < $CACHE_TTL) {
    echo file_get_contents($CACHE_FILE);
    exit;
}

/* ── Télécharger le flux ── */
$ctx = stream_context_create([
    'http' => [
        'timeout'    => 10,
        'user_agent' => 'Mozilla/5.0 (compatible; Dashboard/1.0)',
        'ignore_errors' => true,
    ],
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ],
]);

$xml_raw = @file_get_contents($RSS_URL, false, $ctx);

if (!$xml_raw && function_exists('curl_init')) {
    $ch = curl_init($RSS_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; Dashboard/1.0)',
    ]);
    $xml_raw = curl_exec($ch);
    curl_close($ch);
}

if (!$xml_raw) {
    /* Servir le cache périmé plutôt que rien */
    if (file_exists($CACHE_FILE)) {
        echo file_get_contents($CACHE_FILE);
    } else {
        echo json_encode(['error' => 'unreachable', 'items' => []]);
    }
    exit;
}

/* ── Parser le XML ── */
libxml_use_internal_errors(true);
$xml = simplexml_load_string($xml_raw, 'SimpleXMLElement', LIBXML_NOCDATA);

$items = [];
if ($xml && isset($xml->channel->item)) {
    foreach ($xml->channel->item as $item) {
        if (count($items) >= $MAX_ITEMS) break;
        $title = trim(strip_tags((string)$item->title));
        $link  = trim((string)$item->link);
        /* Le Monde met l'URL dans un nœud texte après <link/> */
        if (!$link) {
            $dom = dom_import_simplexml($item);
            foreach ($dom->childNodes as $node) {
                if ($node->nodeName === 'link' && $node->nextSibling) {
                    $link = trim($node->nextSibling->nodeValue ?? '');
                    break;
                }
            }
        }
        if ($title) {
            $items[] = ['title' => $title, 'link' => $link];
        }
    }
}

$result = json_encode([
    'items'    => $items,
    'fetched'  => date('H:i'),
    'count'    => count($items),
]);

/* ── Écrire le cache ── */
@file_put_contents($CACHE_FILE, $result);
echo $result;
