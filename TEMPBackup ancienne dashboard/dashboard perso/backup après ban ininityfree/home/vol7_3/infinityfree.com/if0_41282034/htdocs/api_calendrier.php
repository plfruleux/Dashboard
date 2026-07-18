<?php
header('Content-Type: application/json');

/* ── Timezone locale : toutes les dates affichées en heure de Hanoï ── */
date_default_timezone_set('Asia/Ho_Chi_Minh');

$url = "https://p149-caldav.icloud.com/published/2/MTAxMzMyMjkwNDMxMDEzM28_pCTgIHIQDjhl514D_alJRSU-XRvSbIFQ9HEc2AogdLeSZn_3Bo2kIElnYIVG3NzdZ9lcaAWe4Yc24PNkkD4";

$data = file_get_contents($url);
if (!$data) { echo json_encode(["error" => "Injoignable"]); exit; }

/* ── Unfold des lignes longues iCal (CRLF + espace = continuation) ── */
$data = preg_replace("/\r\n[ \t]/", "", $data);
$data = preg_replace("/\n[ \t]/",   "", $data);

$start_limit = strtotime("-7 days");
$end_limit   = strtotime("+21 days");

/* ══════════════════════════════════════════════
   Convertit une date iCal compacte en timestamp
   Gère : 20260305T173000Z  20260305T173000  20260305
   Prend en compte le TZID si présent sur la même ligne
══════════════════════════════════════════════ */
function parseIcalDate($line_raw) {
    /* Séparer paramètre:valeur   ex: DTSTART;TZID=Asia/Ho_Chi_Minh:20260305T173000 */
    $colon = strpos($line_raw, ':');
    if ($colon === false) return false;
    $params = substr($line_raw, 0, $colon);
    $val    = trim(substr($line_raw, $colon + 1));

    /* Extraire TZID si présent */
    $tzid = 'UTC';
    if (preg_match('/TZID=([^;:]+)/', $params, $m)) {
        $tzid = $m[1];
    }

    /* Format compact → ISO */
    if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})(Z?)$/', $val, $m)) {
        $iso = $m[1].'-'.$m[2].'-'.$m[3].'T'.$m[4].':'.$m[5].':'.$m[6];
        if ($m[7] === 'Z') {
            return strtotime($iso . '+00:00');
        } else {
            try {
                $dt = new DateTime($iso, new DateTimeZone($tzid));
                return $dt->getTimestamp();
            } catch (Exception $e) {
                return strtotime($iso);
            }
        }
    }
    /* Événement tout-jour : 20260305 */
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $val, $m)) {
        return strtotime($m[1].'-'.$m[2].'-'.$m[3]);
    }
    /* Fallback strtotime standard */
    return strtotime($val) ?: false;
}

/* ══════════════════════════════════════════════
   Parse la DURATION iCal : PT1H30M, P1D, P1DT2H etc.
══════════════════════════════════════════════ */
function parseIcalDuration($str) {
    $str = trim($str);
    $mins = 0;
    if (preg_match('/(\d+)D/', $str, $m)) $mins += (int)$m[1] * 1440;
    if (preg_match('/(\d+)H/', $str, $m)) $mins += (int)$m[1] * 60;
    if (preg_match('/(\d+)M/', $str, $m)) $mins += (int)$m[1];
    return $mins;
}

/* ══════════════════════════════════════════════
   Génère les occurrences d'une RRULE dans la fenêtre
   Supporte : FREQ=WEEKLY / DAILY + BYDAY + UNTIL + COUNT
══════════════════════════════════════════════ */
function expandRRule($rrule_str, $base_ts, $start_limit, $end_limit) {
    $parts = [];
    foreach (explode(';', $rrule_str) as $p) {
        $kv = explode('=', $p, 2);
        if (count($kv) === 2) $parts[strtoupper($kv[0])] = strtoupper($kv[1]);
    }
    $freq  = isset($parts['FREQ'])  ? $parts['FREQ']  : '';
    $count = isset($parts['COUNT']) ? (int)$parts['COUNT'] : 500;
    $until = isset($parts['UNTIL']) ? parseIcalDate('DTEND:'.$parts['UNTIL']) : $end_limit;
    if (!$until || $until > $end_limit) $until = $end_limit;

    $byday = [];
    if (isset($parts['BYDAY'])) {
        $day_map = ['SU'=>0,'MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6];
        foreach (explode(',', $parts['BYDAY']) as $d) {
            $key = strtoupper(trim($d));
            if (isset($day_map[$key])) $byday[] = $day_map[$key];
        }
    }

    $occurrences = [];
    $step_days   = ($freq === 'DAILY') ? 1 : 7;
    if ($freq !== 'WEEKLY' && $freq !== 'DAILY') return [];

    $ts = $base_ts;
    $n  = 0;
    /* Recule au début de la semaine courante si BYDAY présent */
    if ($byday && $freq === 'WEEKLY') {
        /* Avance jusqu'à la première occurrence dans la semaine de base_ts */
        $base_dow = (int)date('w', $base_ts);
        $first_byday = $byday[0];
        $diff = ($first_byday - $base_dow + 7) % 7;
        $ts   = $base_ts + $diff * 86400;
    }

    while ($ts <= $until && $n < $count) {
        if ($byday) {
            $dow = (int)date('w', $ts);
            foreach ($byday as $d) {
                $candidate = $ts + (($d - $dow + 7) % 7) * 86400;
                if ($candidate >= $start_limit && $candidate <= $until && $candidate > $base_ts - 86400) {
                    $occurrences[] = $candidate;
                    $n++;
                }
            }
            $ts += $step_days * 86400;
        } else {
            if ($ts >= $start_limit) $occurrences[] = $ts;
            $n++;
            $ts += $step_days * 86400;
        }
    }
    return $occurrences;
}

/* ══════════════════════════════════════════════
   Parsing principal
══════════════════════════════════════════════ */
$events = preg_split('/BEGIN:VEVENT/', $data);
$extracted_events = [];

foreach ($events as $event) {
    /* SUMMARY */
    if (!preg_match('/^SUMMARY[:;](.*)/m', $event, $sum)) continue;
    $title = trim(str_replace(['\\,','\\;'], [',',';'], $sum[1]));

    /* DTSTART — ligne complète pour parser TZID */
    if (!preg_match('/^(DTSTART[^\r\n]*)/m', $event, $ds_line)) continue;
    $base_ts = parseIcalDate(trim($ds_line[1]));
    if (!$base_ts) continue;

    /* DTEND */
    $ts_end = false;
    if (preg_match('/^(DTEND[^\r\n]*)/m', $event, $de_line)) {
        $ts_end = parseIcalDate(trim($de_line[1]));
    }

    /* DURATION */
    $duree_minutes = 60;
    if ($ts_end && $ts_end > $base_ts) {
        $duree_minutes = (int)round(($ts_end - $base_ts) / 60);
    } elseif (preg_match('/^DURATION:(.*)/m', $event, $dur_m)) {
        $d = parseIcalDuration($dur_m[1]);
        if ($d > 0) $duree_minutes = $d;
    }
    if ($duree_minutes <= 0 || $duree_minutes > 1440) $duree_minutes = 60;

    /* RRULE */
    $occurrences = [$base_ts];
    if (preg_match('/^RRULE:(.*)/m', $event, $rr)) {
        $expanded = expandRRule(trim($rr[1]), $base_ts, $start_limit, $end_limit);
        if ($expanded) $occurrences = $expanded;
    }

    foreach ($occurrences as $timestamp) {
        if ($timestamp >= $start_limit && $timestamp <= $end_limit) {
            $extracted_events[] = [
                "titre"          => $title,
                "date_formattee" => date("d/m/Y H:i", $timestamp),
                "timestamp"      => $timestamp,
                "timestamp_fin"  => $timestamp + ($duree_minutes * 60),
                "duree_minutes"  => $duree_minutes
            ];
        }
    }
}

usort($extracted_events, function($a, $b) { return $a['timestamp'] <=> $b['timestamp']; });

$resultat = [
    "metadata" => [
        "compte_total" => count($extracted_events),
        "bornes"       => ["debut" => date("d/m", $start_limit), "fin" => date("d/m", $end_limit)],
        "derniere_maj" => date("d/m/Y H:i:s")
    ],
    "data" => $extracted_events
];

file_put_contents('data.json', json_encode($resultat));
echo json_encode($resultat);
