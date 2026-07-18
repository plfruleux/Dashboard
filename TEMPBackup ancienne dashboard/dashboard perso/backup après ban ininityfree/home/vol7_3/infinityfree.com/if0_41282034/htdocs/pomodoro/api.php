<?php
/**
 * Focus Sanctuary — API centralisée
 * Gestion du timer côté serveur — auto-complete, aucune logique de transition côté client
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$STATE_FILE = __DIR__ . '/state.json';
$STATS_FILE = __DIR__ . '/stats.json';

// Durées en secondes
$DURATIONS = [
    'work'        => ['short' => 1500, 'long' => 3000],
    'short_break' => ['short' =>  300, 'long' =>  600],
    'long_break'  => ['short' => 1800, 'long' => 3600],
];

function loadState($file) {
    if (!file_exists($file)) return initState();
    $d = json_decode(file_get_contents($file), true);
    return is_array($d) ? $d : initState();
}
function initState() {
    return [
        'mode'    => 'work',
        'dur'     => 1500,
        'start'   => null,
        'rem'     => 1500,
        'running' => false,
        'sessions'=> 0,
        'pos'     => 0,    // 0-3 dans le cycle de 4 sessions
        'wsize'   => 'short',
        'bsize'   => 'short',
        'done_at' => null,
    ];
}
function saveState($file, $s) {
    file_put_contents($file, json_encode($s, JSON_PRETTY_PRINT), LOCK_EX);
}
function remaining($s) {
    if (!$s['running'] || !$s['start']) return max(0, (float)$s['rem']);
    return max(0, (float)$s['dur'] - (microtime(true) - (float)$s['start']));
}
function workDur($s, $D)  { return $D['work'][$s['wsize']]; }
function brkDur($s, $m, $D){ return $D[$m][$s['bsize']]; }

$s = loadState($STATE_FILE);
$a = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'state');

switch ($a) {

case 'state':
    $r = remaining($s);
    $just_completed = false;
    $completed_mode = null;

    /* ── Auto-complete côté serveur dès que le temps expire ──
       Aucun client n'a besoin d'appeler "complete".
       Le verrou done_at empêche toute double-exécution. */
    if ($s['running'] && $r <= 0) {
        $already = $s['done_at'] && (microtime(true) - (float)$s['done_at']) < 6;
        if (!$already) {
            $completed_mode = $s['mode'];
            /* Statistiques */
            if ($completed_mode === 'work') {
                $stats = file_exists($STATS_FILE)
                    ? (json_decode(file_get_contents($STATS_FILE), true) ?: [])
                    : [];
                $stats[] = [
                    'date' => date('Y-m-d'),
                    'time' => date('H:i'),
                    'min'  => (int)round($s['dur'] / 60),
                    'ts'   => time(),
                ];
                file_put_contents($STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
                $s['sessions']++;
                $s['pos'] = ($s['pos'] + 1) % 4;
            }
            /* Prochain mode */
            $next = ($completed_mode === 'work')
                ? (($s['pos'] === 0) ? 'long_break' : 'short_break')
                : 'work';
            $ndur = ($next === 'work')
                ? workDur($s, $DURATIONS)
                : brkDur($s, $next, $DURATIONS);
            $s = array_merge($s, [
                'mode'    => $next,
                'dur'     => $ndur,
                'running' => false,
                'start'   => null,
                'rem'     => $ndur,
                'done_at' => microtime(true),
            ]);
            saveState($STATE_FILE, $s);
            $just_completed = true;
            $r = $ndur;
        }
    }

    echo json_encode([
        'mode'            => $s['mode'],
        'dur'             => (int)$s['dur'],
        'rem'             => (int)round($r),
        'running'         => (bool)$s['running'],
        'sessions'        => (int)$s['sessions'],
        'pos'             => (int)$s['pos'],
        'wsize'           => $s['wsize'],
        'bsize'           => $s['bsize'],
        'just_completed'  => $just_completed,          // flag de transition
        'completed_mode'  => $completed_mode,          // mode qui vient de se terminer
    ]);
    break;

case 'start':
    if (!$s['running']) {
        $r = ($s['rem'] !== null) ? (float)$s['rem'] : (float)$s['dur'];
        $s['start']   = microtime(true) - ($s['dur'] - $r);
        $s['running'] = true;
        $s['rem']     = null;
        saveState($STATE_FILE, $s);
    }
    echo json_encode(['ok' => true]);
    break;

case 'pause':
    if ($s['running']) {
        $s['rem']     = (int)round(remaining($s));
        $s['running'] = false;
        $s['start']   = null;
        saveState($STATE_FILE, $s);
    }
    echo json_encode(['ok' => true]);
    break;

case 'reset':
    $s['running'] = false;
    $s['start']   = null;
    $s['rem']     = $s['dur'];
    $s['done_at'] = null;
    saveState($STATE_FILE, $s);
    echo json_encode(['ok' => true]);
    break;

case 'skip':
    // Passer la pause (interdit pendant le travail)
    if ($s['mode'] !== 'work') {
        $d = workDur($s, $DURATIONS);
        $s = array_merge($s, ['mode'=>'work','dur'=>$d,'running'=>false,'start'=>null,'rem'=>$d]);
        saveState($STATE_FILE, $s);
    }
    echo json_encode(['ok' => true]);
    break;

case 'cfg':
    $wmin = isset($_POST['wmin']) ? (int)$_POST['wmin'] : 25;
    $bsz  = isset($_POST['bsz'])  ? $_POST['bsz']       : 'short';
    $s['wsize'] = ($wmin >= 50) ? 'long' : 'short';
    $s['bsize'] = ($bsz === 'long') ? 'long' : 'short';
    if (!$s['running']) {
        $s['dur'] = ($s['mode'] === 'work')
            ? workDur($s, $DURATIONS)
            : brkDur($s, $s['mode'], $DURATIONS);
        $s['rem'] = $s['dur'];
    }
    saveState($STATE_FILE, $s);
    echo json_encode(['ok' => true]);
    break;

case 'stats':
    if (!file_exists($STATS_FILE)) {
        echo json_encode(['today'=>0,'week'=>0,'month'=>0,'total'=>0,'daily'=>array_fill(0,14,0),'labels'=>[],'recent'=>[]]);
        break;
    }
    $all    = json_decode(file_get_contents($STATS_FILE), true) ?: [];
    $today  = date('Y-m-d');
    $wstart = date('Y-m-d', strtotime('monday this week'));
    $mstart = date('Y-m-01');
    $t = $w = $mo = $tot = 0;
    $daily = [];
    for ($i = 13; $i >= 0; $i--) $daily[date('Y-m-d', strtotime("-{$i} days"))] = 0;
    foreach ($all as $r) {
        $m = (int)($r['min'] ?? 0);
        $tot += $m;
        if ($r['date'] === $today)   $t  += $m;
        if ($r['date'] >= $wstart)   $w  += $m;
        if ($r['date'] >= $mstart)   $mo += $m;
        if (isset($daily[$r['date']])) $daily[$r['date']] += $m;
    }
    echo json_encode([
        'today'   => $t,
        'week'    => $w,
        'month'   => $mo,
        'total'   => $tot,
        'daily'   => array_values($daily),
        'labels'  => array_keys($daily),
        'recent'  => array_slice(array_reverse($all), 0, 20),
    ]);
    break;

case 'bg':
    $ext = ['jpg','jpeg','webp','png'];
    $work = $brk = [];
    foreach ($ext as $e) {
        $work = array_merge($work, glob(__DIR__ . '/assets/bg/work/*.' . $e) ?: []);
        $brk  = array_merge($brk,  glob(__DIR__ . '/assets/bg/break/*.' . $e) ?: []);
    }
    echo json_encode([
        'work'  => array_values(array_map(function($f){ return '/pomodoro/assets/bg/work/'.basename($f); }, $work)),
        'break' => array_values(array_map(function($f){ return '/pomodoro/assets/bg/break/'.basename($f); }, $brk)),
    ]);
    break;

default:
    echo json_encode(['ok' => true]);
}