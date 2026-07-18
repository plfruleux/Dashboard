<?php
/**
 * api_wallpapers.php — Gestion des fonds d'écran (3 bibliothèques)
 * Paramètre GET : dir = dashboard (défaut) | pomo-work | pomo-break
 * Actions : list | upload | delete | reindex
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

$imgExts = ['jpg','jpeg','png','webp'];

/* ── Résolution de la bibliothèque selon le param "dir" ── */
$dirParam = isset($_GET['dir']) ? trim($_GET['dir']) : 'dashboard';
switch ($dirParam) {
    case 'pomo-work':
        $wallDir  = __DIR__ . '/pomodoro/assets/bg/work/';
        $baseUrl  = '/pomodoro/assets/bg/work/';
        $jsonFile = null;
        $prefix   = 'work';
        break;
    case 'pomo-break':
        $wallDir  = __DIR__ . '/pomodoro/assets/bg/break/';
        $baseUrl  = '/pomodoro/assets/bg/break/';
        $jsonFile = null;
        $prefix   = 'break';
        break;
    default: /* dashboard */
        $wallDir  = __DIR__ . '/wallpapers/';
        $baseUrl  = '/wallpapers/';
        $jsonFile = __DIR__ . '/wallpapers.json';
        $prefix   = 'wp';
        break;
}

$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';

/* ════ LIST ════ */
if ($action === 'list') {
    $files = [];
    if (is_dir($wallDir)) {
        $dh = opendir($wallDir);
        while (($f = readdir($dh)) !== false) {
            if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $f)) {
                $path = $wallDir . $f;
                $files[] = ['name'=>$f,'url'=>$baseUrl.$f,'size'=>filesize($path),'mtime'=>filemtime($path)];
            }
        }
        closedir($dh);
        usort($files, function($a,$b){ return strnatcmp($a['name'],$b['name']); });
    }
    $indexed = [];
    if ($jsonFile && file_exists($jsonFile)) {
        $json = json_decode(file_get_contents($jsonFile), true);
        foreach (($json['photos'] ?? []) as $p) { $indexed[] = basename($p['url']); }
    }
    echo json_encode(['success'=>true,'files'=>$files,'indexed'=>$indexed,'count'=>count($files),'dir'=>$dirParam]);
    exit;
}

/* ════ REINDEX ════ */
if ($action === 'reindex') {
    if (!$jsonFile) { echo json_encode(['success'=>false,'error'=>'Reindex non applicable pour cette bibliothèque']); exit; }
    $files = [];
    if (is_dir($wallDir)) {
        $dh = opendir($wallDir);
        while (($f = readdir($dh)) !== false) { if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $f)) $files[] = $f; }
        closedir($dh); natsort($files);
    }
    $photos = [];
    foreach ($files as $f) { $photos[] = ['id'=>pathinfo($f,PATHINFO_FILENAME),'url'=>$baseUrl.$f]; }
    $out = json_encode(['version'=>'2.0','photos'=>array_values($photos)], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    if (file_put_contents($jsonFile, $out) === false) { echo json_encode(['success'=>false,'error'=>"Impossible d'écrire wallpapers.json"]); exit; }
    echo json_encode(['success'=>true,'count'=>count($photos)]);
    exit;
}

/* ════ DELETE ════ */
if ($action === 'delete') {
    $filename = basename(isset($_GET['file']) ? $_GET['file'] : '');
    if (!$filename || !preg_match('/\.(jpg|jpeg|png|webp)$/i', $filename)) { echo json_encode(['success'=>false,'error'=>'Nom invalide']); exit; }
    $path = $wallDir . $filename;
    if (!file_exists($path)) { echo json_encode(['success'=>false,'error'=>'Fichier introuvable']); exit; }
    if (!unlink($path)) { echo json_encode(['success'=>false,'error'=>'Suppression impossible']); exit; }
    if ($jsonFile && file_exists($jsonFile)) {
        $json = json_decode(file_get_contents($jsonFile), true);
        $photos = array_values(array_filter($json['photos']??[], function($p) use ($filename){ return basename($p['url'])!==$filename; }));
        file_put_contents($jsonFile, json_encode(['version'=>'2.0','photos'=>$photos], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
    echo json_encode(['success'=>true]);
    exit;
}

/* ════ UPLOAD ════ */
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['file'])) { echo json_encode(['success'=>false,'error'=>'Aucun fichier reçu']); exit; }
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) { echo json_encode(['success'=>false,'error'=>'Erreur upload: '.$file['error']]); exit; }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg','image/png','image/webp'];
    if (!in_array($mime, $allowed)) { echo json_encode(['success'=>false,'error'=>'Type non autorisé: '.$mime]); exit; }
    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime];
    if (!is_dir($wallDir)) mkdir($wallDir, 0755, true);
    $existing = [];
    $dh = opendir($wallDir);
    while (($f = readdir($dh)) !== false) {
        if (preg_match('/^'.preg_quote($prefix,'/').'(\d+)\.(jpg|jpeg|png|webp)$/i', $f, $m)) $existing[] = intval($m[1]);
    }
    closedir($dh); sort($existing);
    $next    = $existing ? (max($existing)+1) : 1;
    $newName = $prefix . str_pad($next, 2, '0', STR_PAD_LEFT) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $wallDir.$newName)) { echo json_encode(['success'=>false,'error'=>'Déplacement impossible']); exit; }
    if ($jsonFile) {
        $photos = [];
        if (file_exists($jsonFile)) { $json = json_decode(file_get_contents($jsonFile), true); $photos = $json['photos']??[]; }
        $photos[] = ['id'=>pathinfo($newName,PATHINFO_FILENAME),'url'=>$baseUrl.$newName];
        file_put_contents($jsonFile, json_encode(['version'=>'2.0','photos'=>$photos], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
    echo json_encode(['success'=>true,'filename'=>$newName,'url'=>$baseUrl.$newName]);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Action inconnue']);