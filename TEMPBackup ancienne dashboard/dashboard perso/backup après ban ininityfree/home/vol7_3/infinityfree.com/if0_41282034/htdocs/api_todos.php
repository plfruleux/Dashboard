<?php
/**
 * api_todos.php — Pont CalDAV ↔ iCloud Reminders
 *
 * Endpoints :
 *   GET  ?action=lists               → liste des calendriers VTODO découverts
 *   GET  ?action=todos&name=Quotidien → todos d'une liste (cache 60s)
 *   GET  ?action=todos&name=…&refresh=1 → force refresh
 *   POST {"action":"complete","uid":"…","list":"…"} → marquer fait
 *   GET  ?action=refresh_lists       → redécouverte des calendriers
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

/* ── Config ──────────────────────────────────────────────────── */
$cfgFile = __DIR__ . '/config_caldav.php';
if (!file_exists($cfgFile)) {
    echo json_encode([
        'error'  => 'config_caldav.php manquant',
        'setup'  => true,
        'notice' => 'Créer config_caldav.php avec CALDAV_USER et CALDAV_PASS. Voir le fichier template fourni.'
    ]);
    exit;
}
require_once $cfgFile;
if (!defined('CALDAV_USER') || CALDAV_USER === 'votre@apple.id') {
    echo json_encode(['error' => 'config_caldav.php non configuré — renseigner CALDAV_USER et CALDAV_PASS']);
    exit;
}

/* ── Chemins de cache ─────────────────────────────────────────── */
$LISTS_CACHE = __DIR__ . '/caldav_lists.json';  /* URLs des calendriers */
$TODOS_CACHE = __DIR__ . '/todos.json';          /* Items par liste      */
$LISTS_TTL   = 3600;   /* 1h — les URLs de calendriers changent rarement */
$TODOS_TTL   = 60;     /* 60s — les todos changent souvent               */

/* ════════════════════════════════════════════════════════════════
   HELPERS CalDAV
════════════════════════════════════════════════════════════════ */

/**
 * Effectue une requête CalDAV/WebDAV
 */
function dav(string $url, string $httpMethod, string $xml = '', array $extraHeaders = [], int $depth = 0): array
{
    $headers = array_merge([
        'Content-Type: application/xml; charset=utf-8',
        'Depth: ' . $depth,
        'Prefer: return-minimal',
    ], $extraHeaders);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $httpMethod,
        CURLOPT_POSTFIELDS     => $xml ?: null,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
        CURLOPT_USERPWD        => CALDAV_USER . ':' . CALDAV_PASS,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HEADER         => true,
    ]);

    $response    = curl_exec($ch);
    $code        = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $effectiveUrl= curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $error       = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['code' => 0, 'body' => '', 'error' => $error, 'effective_url' => $url];
    }
    return [
        'code'          => $code,
        'headers'       => substr($response, 0, $headerSize),
        'body'          => substr($response, $headerSize),
        'effective_url' => $effectiveUrl,
    ];
}

/**
 * Extrait le contenu d'une balise XML (ignore les namespaces)
 * Cherche la PREMIÈRE occurrence même en namespace par défaut (xmlns="DAV:")
 */
function xmlFirst(string $xml, string $tag): ?string
{
    /* Namespace préfixé : <D:tag> ou <ns:tag> */
    if (preg_match('/<(?:[^:\s>]+:)?' . preg_quote($tag, '/') . '(?:\s[^>]*)?>[\s\S]*?<\/(?:[^:\s>]+:)?' . preg_quote($tag, '/') . '>/si', $xml, $m)) {
        /* Retirer les sous-balises et garder le texte pur */
        return trim(strip_tags($m[0]));
    }
    return null;
}

/**
 * Extrait le href DANS une balise parente donnée (ex: current-user-principal > href)
 * Évite d'attraper le href de <response> au lieu de celui de la propriété
 */
function xmlNestedHref(string $xml, string $parentTag): ?string
{
    /* Trouver le bloc du parent */
    if (!preg_match('/<(?:[^:\s>]+:)?' . preg_quote($parentTag, '/') . '(?:\s[^>]*)?>[\s\S]*?<\/(?:[^:\s>]+:)?' . preg_quote($parentTag, '/') . '>/si', $xml, $m)) {
        return null;
    }
    $block = $m[0];
    /* Extraire href dans ce bloc */
    if (!preg_match('/<(?:[^:\s>]+:)?href(?:\s[^>]*)?>([^<]+)<\/(?:[^:\s>]+:)?href>/si', $block, $h)) {
        return null;
    }
    $val = trim($h[1]);
    return $val !== '' ? $val : null;
}

/**
 * Extrait toutes les valeurs d'une balise dans un bloc XML
 */
function xmlAll(string $xml, string $outerTag, string $innerTag): array
{
    $results = [];
    preg_match_all('/<(?:[^:\s>]+:)?' . preg_quote($outerTag, '/') . '(?:\s[^>]*)?>[\s\S]*?<\/(?:[^:\s>]+:)?' . preg_quote($outerTag, '/') . '>/i', $xml, $blocks);
    foreach ($blocks[0] as $block) {
        $v = xmlFirst($block, $innerTag);
        if ($v !== null) $results[] = $v;
    }
    return $results;
}

/**
 * Résout une URL relative par rapport à une URL de base
 */
function resolveUrl(string $base, string $relative): string
{
    if (strpos($relative, 'http') === 0) return $relative;
    $p = parse_url($base);
    $host = $p['scheme'] . '://' . $p['host'];
    /* Chemin absolu */
    if (strpos($relative, '/') === 0) return $host . $relative;
    /* Chemin relatif */
    $dir = rtrim(dirname($p['path'] ?? '/'), '/');
    return $host . $dir . '/' . $relative;
}

/* ════════════════════════════════════════════════════════════════
   1. DÉCOUVERTE CalDAV (mise en cache)
════════════════════════════════════════════════════════════════ */

function discoverCalendars(): ?array
{
    global $LISTS_CACHE, $LISTS_TTL;

    /* Cache valide ? Vérifier aussi que l'URL n'est pas corrompue */
    if (file_exists($LISTS_CACHE)) {
        $c = json_decode(file_get_contents($LISTS_CACHE), true);
        $homeOk = isset($c['home']) && strlen($c['home']) > 30;
        if ($c && isset($c['ts']) && (time() - $c['ts']) < $LISTS_TTL && $homeOk) {
            return $c['lists'];
        }
        @unlink($LISTS_CACHE);
    }

    /* ── Étape 1 : current-user-principal → extraire userId ── */
    $xml1 = '<?xml version="1.0"?><D:propfind xmlns:D="DAV:"><D:prop><D:current-user-principal/></D:prop></D:propfind>';
    $r1   = dav('https://caldav.icloud.com/', 'PROPFIND', $xml1, [], 0);
    if ($r1['code'] < 200 || $r1['code'] >= 300) return null;

    /* URL de base réelle après redirect (ex: p149-caldav.icloud.com) */
    $base = $r1['effective_url'] ?? 'https://caldav.icloud.com/';
    /* Normaliser : garder scheme://host seulement */
    $parsedBase = parse_url($base);
    $baseHost   = ($parsedBase['scheme'] ?? 'https') . '://' . ($parsedBase['host'] ?? 'caldav.icloud.com');

    $principalPath = xmlNestedHref($r1['body'], 'current-user-principal');
    if (!$principalPath) return null;

    /* Extraire le userId numérique : /10133229043/principal/ → 10133229043 */
    if (!preg_match('#/(\d+)/principal/?#', $principalPath, $idM)) return null;
    $userId  = $idM[1];

    /* iCloud CalDAV : structure d'URL fixe et documentée */
    $homeUrl = $baseHost . '/' . $userId . '/calendars/';

    /* ── Étape 2 : liste des calendriers (Depth:1) ── */
    $xml2 = '<?xml version="1.0"?><D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:prop>
    <D:displayname/>
    <D:resourcetype/>
    <C:supported-calendar-component-set/>
  </D:prop>
</D:propfind>';
    $r2   = dav($homeUrl, 'PROPFIND', $xml2, [], 1);
    if ($r2['code'] < 200 || $r2['code'] >= 300) return null;

    $base2       = $r2['effective_url'] ?? $homeUrl;
    $parsedBase2 = parse_url($base2);
    $baseHost2   = ($parsedBase2['scheme'] ?? 'https') . '://' . ($parsedBase2['host'] ?? 'caldav.icloud.com');

    /* Parser /calendars/ — exclure la racine et les calendriers abonnés */
    $lists    = [];
    $rootPath = '/' . $userId . '/calendars/';
    _parseVtodoCollections($r2['body'], $baseHost2, $lists, $rootPath);

    /* Sonder aussi /reminders/ — iCloud y stocke les listes Rappels nommées */
    $remUrl = $baseHost . '/' . $userId . '/reminders/';
    $xmlR   = '<?xml version="1.0"?><D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav"><D:prop><D:displayname/><D:resourcetype/><C:supported-calendar-component-set/></D:prop></D:propfind>';
    $r3     = dav($remUrl, 'PROPFIND', $xmlR, [], 1);
    if ($r3['code'] >= 200 && $r3['code'] < 300) {
        $parsedBase3 = parse_url($r3['effective_url'] ?? $remUrl);
        $baseHost3   = ($parsedBase3['scheme'] ?? 'https') . '://' . ($parsedBase3['host'] ?? 'caldav.icloud.com');
        _parseVtodoCollections($r3['body'], $baseHost3, $lists, '/' . $userId . '/reminders/');
    }

    if (empty($lists)) {
        $CALDAV_LAST_ERROR = 'Aucune liste VTODO trouvée. /calendars/: ' . strlen($r2['body']) . 'b, /reminders/ HTTP ' . ($r3['code'] ?? '?');
    }

    file_put_contents($LISTS_CACHE, json_encode(
        ['ts' => time(), 'lists' => $lists, 'home' => $homeUrl, 'userId' => $userId],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ));
    return $lists;
}

/**
 * Extrait les collections VTODO d'un corps PROPFIND.
 * Exclut la racine ($skipHref) et les calendriers abonnés.
 */
function _parseVtodoCollections(string $body, string $baseHost, array &$lists, string $skipHref): void
{
    preg_match_all('/<(?:[^:\s>]+:)?response(?:\s[^>]*)?>[\s\S]*?<\/(?:[^:\s>]+:)?response>/i', $body, $responses);
    foreach ($responses[0] as $resp) {
        if (stripos($resp, 'VTODO') === false) continue;
        if (stripos($resp, 'subscribed') !== false) continue; /* calendriers abonnés */

        if (!preg_match('/<(?:[^:\s>]+:)?response(?:\s[^>]*)?>\s*<(?:[^:\s>]+:)?href(?:\s[^>]*)?>([^<]+)<\/(?:[^:\s>]+:)?href>/si', $resp, $hM)) continue;
        $href = trim($hM[1]);
        if (!$href) continue;

        /* Exclure la collection racine */
        if (rtrim($href, '/') . '/' === rtrim($skipHref, '/') . '/') continue;

        $name = xmlFirst($resp, 'displayname');
        if (!$name) continue;

        $fullHref = (strpos($href, 'http') === 0) ? $href : $baseHost . $href;

        /* Dédoublonner */
        foreach ($lists as $existing) {
            if ($existing['name'] === $name) continue 2;
        }
        $lists[] = ['name' => $name, 'url' => $fullHref];
    }
}

/* ════════════════════════════════════════════════════════════════
   2. LIRE LES TODOS D'UNE LISTE
════════════════════════════════════════════════════════════════ */

function getCalUrl(string $listName): ?string
{
    $lists = discoverCalendars();
    if (!$lists) return null;
    $needle = mb_strtolower(str_replace('_', ' ', $listName));
    foreach ($lists as $l) {
        if (mb_strtolower($l['name']) === $needle) return $l['url'];
        if (mb_strtolower($l['name']) === mb_strtolower($listName)) return $l['url'];
    }
    return null;
}

function getTodos(string $listName, bool $forceRefresh = false): array
{
    global $TODOS_CACHE, $TODOS_TTL;
    $cacheKey = mb_strtolower($listName);

    /* Cache */
    if (!$forceRefresh && file_exists($TODOS_CACHE)) {
        $c = json_decode(file_get_contents($TODOS_CACHE), true);
        if ($c && isset($c[$cacheKey]) && (time() - $c[$cacheKey]['ts']) < $TODOS_TTL) {
            return $c[$cacheKey]['items'];
        }
    }

    $calUrl = getCalUrl($listName);
    if (!$calUrl) {
        /* Essayer une redécouverte forcée */
        global $LISTS_CACHE;
        @unlink($LISTS_CACHE);
        $calUrl = getCalUrl($listName);
        if (!$calUrl) return ['_error' => 'Liste "' . $listName . '" introuvable après redécouverte'];
    }

    /* REPORT calendar-query — tous les VTODO non complétés */
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<C:calendar-query xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:D="DAV:">
  <D:prop>
    <D:getetag/>
    <D:href/>
    <C:calendar-data/>
  </D:prop>
  <C:filter>
    <C:comp-filter name="VCALENDAR">
      <C:comp-filter name="VTODO"/>
    </C:comp-filter>
  </C:filter>
</C:calendar-query>';

    $r = dav($calUrl, 'REPORT', $xml, ['Content-Type: application/xml; charset=utf-8'], 1);

    if ($r['code'] < 200 || $r['code'] >= 300) {
        return ['_error' => 'REPORT CalDAV HTTP ' . $r['code'] . ' sur ' . $calUrl];
    }

    /* Extraire les données iCal encodées en HTML dans la réponse XML */
    $body = html_entity_decode($r['body'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
    preg_match_all('/BEGIN:VCALENDAR[\s\S]*?END:VCALENDAR/i', $body, $cals);

    $todos = [];
    foreach ($cals[0] as $calData) {
        if (!preg_match('/BEGIN:VTODO([\s\S]*?)END:VTODO/i', $calData, $vtM)) continue;
        $vtodo = $vtM[1];

        $uid     = icalProp($vtodo, 'UID');
        $summary = icalProp($vtodo, 'SUMMARY');
        $status  = icalProp($vtodo, 'STATUS') ?: 'NEEDS-ACTION';
        $due     = icalProp($vtodo, 'DUE');
        $prio    = icalProp($vtodo, 'PRIORITY');
        $notes   = icalProp($vtodo, 'DESCRIPTION');

        if (!$uid || !$summary) continue;
        if (strtoupper($status) === 'COMPLETED') continue;
        if (strtoupper($status) === 'CANCELLED') continue;

        $todos[] = [
            'uid'      => $uid,
            'title'    => $summary,
            'status'   => $status,
            'due'      => $due ? icalDate($due) : null,
            'priority' => $prio ? (int)$prio : 5,
            'notes'    => $notes ?: '',
            'calUrl'   => $calUrl,
        ];
    }

    /* Trier : date d'échéance ASC, sans date en dernier, puis priorité */
    usort($todos, function ($a, $b) {
        if ($a['due'] && $b['due']) return strcmp($a['due'], $b['due']);
        if ($a['due'])  return -1;
        if ($b['due'])  return 1;
        return $a['priority'] <=> $b['priority'];
    });

    /* Mettre en cache */
    $cache = file_exists($TODOS_CACHE) ? json_decode(file_get_contents($TODOS_CACHE), true) : [];
    if (!is_array($cache)) $cache = [];
    $cache[$cacheKey] = ['ts' => time(), 'items' => $todos];
    file_put_contents($TODOS_CACHE, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $todos;
}

/**
 * Lit une propriété iCal (gère folding et paramètres)
 */
function icalProp(string $vtodo, string $prop): ?string
{
    if (preg_match('/^' . $prop . '(?:;[^:"\r\n]*)?:(.+?)(?=\r?\n(?![ \t])|\z)/ms', $vtodo, $m)) {
        return icalUnfold(trim($m[1]));
    }
    return null;
}

/**
 * Supprime le folding RFC 5545 et décode les échappements
 */
function icalUnfold(string $val): string
{
    $val = preg_replace('/\r?\n[ \t]/', '', $val); /* unfold */
    $val = str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $val);
    return trim($val);
}

/**
 * Normalise une date iCal en ISO 8601
 */
function icalDate(string $val): string
{
    $val = preg_replace('/^[^:]+:/', '', $val); /* enlever paramètres TZID etc. */
    $val = trim($val);
    if (strlen($val) >= 15) {
        return substr($val, 0, 4) . '-' . substr($val, 4, 2) . '-' . substr($val, 6, 2)
            . 'T' . substr($val, 9, 2) . ':' . substr($val, 11, 2) . ':00';
    }
    if (strlen($val) >= 8) {
        return substr($val, 0, 4) . '-' . substr($val, 4, 2) . '-' . substr($val, 6, 2);
    }
    return $val;
}

/* ════════════════════════════════════════════════════════════════
   3. COMPLÉTER UN TODO
════════════════════════════════════════════════════════════════ */

function completeTodo(string $uid, string $listName): array
{
    $calUrl = getCalUrl($listName);
    if (!$calUrl) return ['ok' => false, 'error' => 'Liste introuvable'];

    /* Trouver le todo par UID */
    $xml = '<?xml version="1.0"?>
<C:calendar-query xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:D="DAV:">
  <D:prop><D:href/><D:getetag/><C:calendar-data/></D:prop>
  <C:filter>
    <C:comp-filter name="VCALENDAR">
      <C:comp-filter name="VTODO">
        <C:prop-filter name="UID">
          <C:text-match>' . htmlspecialchars($uid, ENT_XML1) . '</C:text-match>
        </C:prop-filter>
      </C:comp-filter>
    </C:comp-filter>
  </C:filter>
</C:calendar-query>';

    $r = dav($calUrl, 'REPORT', $xml, [], 1);
    $body  = html_entity_decode($r['body'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $base  = $r['effective_url'] ?? $calUrl;

    /* Extraire href de la ressource (namespace par défaut ou préfixé) */
    preg_match('/<(?:[^:\s>]+:)?response(?:\s[^>]*)?>\s*<(?:[^:\s>]+:)?href(?:\s[^>]*)?>([^<]+)<\/(?:[^:\s>]+:)?href>/si', $r['body'], $hrefM);
    preg_match('/BEGIN:VCALENDAR[\s\S]*?END:VCALENDAR/i', $body, $calM);

    if (empty($hrefM[1]) || empty($calM[0])) {
        return ['ok' => false, 'error' => 'Todo introuvable (uid: ' . $uid . ', body: ' . substr($r['body'], 0, 200) . ')'];
    }

    $todoUrl = resolveUrl($base, trim($hrefM[1]));
    $calData = $calM[0];

    /* Modifier le VTODO */
    $now     = gmdate('Ymd\THis\Z');
    /* STATUS */
    if (preg_match('/^STATUS:/m', $calData)) {
        $calData = preg_replace('/^STATUS:[^\r\n]*/m', 'STATUS:COMPLETED', $calData);
    } else {
        $calData = preg_replace('/(END:VTODO)/', "STATUS:COMPLETED\r\n$1", $calData);
    }
    /* COMPLETED timestamp */
    if (preg_match('/^COMPLETED:/m', $calData)) {
        $calData = preg_replace('/^COMPLETED:[^\r\n]*/m', 'COMPLETED:' . $now, $calData);
    } else {
        $calData = preg_replace('/(END:VTODO)/', "COMPLETED:$now\r\n$1", $calData);
    }
    /* LAST-MODIFIED */
    if (preg_match('/^LAST-MODIFIED:/m', $calData)) {
        $calData = preg_replace('/^LAST-MODIFIED:[^\r\n]*/m', 'LAST-MODIFIED:' . $now, $calData);
    }

    /* PUT */
    $r2 = dav($todoUrl, 'PUT', $calData, ['Content-Type: text/calendar; charset=utf-8'], 0);

    if ($r2['code'] >= 200 && $r2['code'] < 300) {
        invalidateCache($listName);
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'PUT échoué: HTTP ' . $r2['code']];
}

function invalidateCache(string $listName): void
{
    global $TODOS_CACHE;
    if (!file_exists($TODOS_CACHE)) return;
    $c = json_decode(file_get_contents($TODOS_CACHE), true);
    if (is_array($c)) {
        $k = mb_strtolower($listName);
        if (isset($c[$k])) { $c[$k]['ts'] = 0; }
        file_put_contents($TODOS_CACHE, json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

/* ════════════════════════════════════════════════════════════════
   ROUTER
════════════════════════════════════════════════════════════════ */

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$body   = [];
if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?: [];
    $action = $body['action'] ?? $action;
}

switch ($action) {

    case 'lists':
        $lists = discoverCalendars();
        if ($lists !== null) {
            echo json_encode($lists);
        } else {
            global $CALDAV_LAST_ERROR;
            echo json_encode(['error' => 'Échec CalDAV', 'detail' => $CALDAV_LAST_ERROR ?? 'inconnu']);
        }
        break;

    case 'todos':
        $name    = trim($_GET['name'] ?? '');
        $refresh = !empty($_GET['refresh']);
        if (!$name) { echo json_encode(['error' => 'Paramètre name requis']); break; }
        echo json_encode(getTodos($name, $refresh));
        break;

    case 'complete':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST requis']); break; }
        $uid  = trim($body['uid']  ?? '');
        $list = trim($body['list'] ?? '');
        if (!$uid || !$list) { echo json_encode(['ok' => false, 'error' => 'uid et list requis']); break; }
        echo json_encode(completeTodo($uid, $list));
        break;

    case 'refresh_lists':
        global $LISTS_CACHE;
        if (file_exists($LISTS_CACHE)) unlink($LISTS_CACHE);
        $lists = discoverCalendars();
        echo json_encode($lists !== null ? $lists : ['error' => 'Redécouverte échouée']);
        break;

    case 'debug':
        $out = ['user' => CALDAV_USER, 'pass_len' => strlen(CALDAV_PASS)];
        $out['curl_available'] = function_exists('curl_init');
        if (!$out['curl_available']) { echo json_encode(['error'=>'curl absent','debug'=>$out]); break; }

        /* Étape 1 : current-user-principal */
        $xml1 = '<?xml version="1.0"?><D:propfind xmlns:D="DAV:"><D:prop><D:current-user-principal/></D:prop></D:propfind>';
        $r1   = dav('https://caldav.icloud.com/', 'PROPFIND', $xml1, [], 0);
        $out['step1'] = [
            'url'           => 'https://caldav.icloud.com/',
            'effective_url' => $r1['effective_url'] ?? null,
            'code'          => $r1['code'],
            'error'         => $r1['error'] ?? null,
            'body'          => substr($r1['body'] ?? '', 0, 800),
        ];
        if ($r1['code'] < 200 || $r1['code'] >= 300) { echo json_encode(['error'=>'Étape 1 échouée','debug'=>$out]); break; }

        $principalPath = xmlNestedHref($r1['body'], 'current-user-principal');
        $out['principal_path'] = $principalPath;
        if (!$principalPath) { echo json_encode(['error'=>'current-user-principal href introuvable','debug'=>$out]); break; }

        preg_match('#/(\d+)/principal/?#', $principalPath, $idM);
        $userId = $idM[1] ?? null;
        $out['user_id'] = $userId;
        if (!$userId) { echo json_encode(['error'=>'userId non extrait de: '.$principalPath,'debug'=>$out]); break; }

        $parsedBase = parse_url($r1['effective_url'] ?? 'https://caldav.icloud.com/');
        $baseHost   = $parsedBase['scheme'].'://'.$parsedBase['host'];
        $homeUrl    = $baseHost.'/'.$userId.'/calendars/';
        $out['home_url'] = $homeUrl;

        /* Étape 2 : PROPFIND sur calendars/ Depth:1 */
        $xml2 = '<?xml version="1.0"?><D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav"><D:prop><D:displayname/><D:resourcetype/><C:supported-calendar-component-set/></D:prop></D:propfind>';
        $r2   = dav($homeUrl, 'PROPFIND', $xml2, [], 1);
        $out['step2'] = [
            'url'           => $homeUrl,
            'effective_url' => $r2['effective_url'] ?? null,
            'code'          => $r2['code'],
            'error'         => $r2['error'] ?? null,
            'body'          => substr($r2['body'] ?? '', 0, 3000),
        ];

        echo json_encode(['ok'=>true,'debug'=>$out], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'diag2':
        $out = [];
        $x1 = '<?xml version="1.0"?><D:propfind xmlns:D="DAV:"><D:prop><D:current-user-principal/></D:prop></D:propfind>';
        $d1 = dav('https://caldav.icloud.com/', 'PROPFIND', $x1, [], 0);
        $out['s1_code'] = $d1['code'];
        $out['s1_eff']  = $d1['effective_url'] ?? null;

        if ($d1['code'] >= 200 && $d1['code'] < 300) {
            $pp = xmlNestedHref($d1['body'], 'current-user-principal');
            $out['principal_path'] = $pp;
            preg_match('#/(\d+)/principal/?#', $pp ?? '', $mm);
            $uid = $mm[1] ?? null;
            $out['user_id'] = $uid;
            if ($uid) {
                $parsedEff = parse_url($d1['effective_url'] ?? 'https://caldav.icloud.com/');
                $bh = $parsedEff['scheme'].'://'.$parsedEff['host'];

                $xp = '<?xml version="1.0"?><D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav"><D:prop><D:displayname/><D:resourcetype/><C:supported-calendar-component-set/></D:prop></D:propfind>';

                /* /calendars/ */
                $cal_url = $bh.'/'.$uid.'/calendars/';
                $d2 = dav($cal_url, 'PROPFIND', $xp, [], 1);
                $out['calendars'] = ['url'=>$cal_url,'code'=>$d2['code'],'body_len'=>strlen($d2['body']??''),'body'=>$d2['body']??''];

                /* /reminders/ */
                $rem_url = $bh.'/'.$uid.'/reminders/';
                $d3 = dav($rem_url, 'PROPFIND', $xp, [], 1);
                $out['reminders'] = ['url'=>$rem_url,'code'=>$d3['code'],'body_len'=>strlen($d3['body']??''),'body'=>$d3['body']??''];
            }
        }
        echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'diag3':
        $calUrl = 'https://caldav.icloud.com/10133229043/calendars/9561b330-76c8-4446-bfbd-fa323f1ed93e/';
        $xml = '<?xml version="1.0"?>
<C:calendar-query xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:D="DAV:">
  <D:prop><D:href/><C:calendar-data/></D:prop>
  <C:filter>
    <C:comp-filter name="VCALENDAR">
      <C:comp-filter name="VTODO"/>
    </C:comp-filter>
  </C:filter>
</C:calendar-query>';
        $r    = dav($calUrl, 'REPORT', $xml, ['Content-Type: application/xml'], 1);
        $body = html_entity_decode($r['body'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
        preg_match_all('/BEGIN:VTODO[\s\S]*?END:VTODO/i', $body, $todos);
        echo json_encode([
            'http_code'   => $r['code'],
            'body_len'    => strlen($r['body']),
            'total_found' => count($todos[0]),
            'samples'     => array_slice($todos[0], 0, 5),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
        /* Test end-to-end : découverte + lecture de la liste Quotidien */
        $lists = discoverCalendars();
        if (!$lists) {
            echo json_encode(['error' => 'Découverte échouée', 'tip' => 'Supprimer caldav_lists.json et réessayer']);
            break;
        }
        $out = ['lists_found' => array_column($lists, 'name'), 'lists_count' => count($lists)];
        /* Lire les todos de la première liste */
        $first = $lists[0]['name'] ?? 'Quotidien';
        $items = getTodos($first, true);
        $out['sample_list']  = $first;
        $out['items_count']  = count($items);
        $out['items_sample'] = array_slice($items, 0, 3);
        echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action inconnue', 'actions' => ['lists', 'todos', 'test_todos', 'complete', 'refresh_lists', 'debug']]);
}