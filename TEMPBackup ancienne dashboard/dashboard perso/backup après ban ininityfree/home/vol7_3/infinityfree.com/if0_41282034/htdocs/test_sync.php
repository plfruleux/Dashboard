<?php
// --- CONFIGURATION ET LOGIQUE ---
$url = "https://p149-caldav.icloud.com/published/2/MTAxMzMyMjkwNDMxMDEzM28_pCTgIHIQDjhl514D_alJRSU-XRvSbIFQ9HEc2AogdLeSZn_3Bo2kIElnYIVG3NzdZ9lcaAWe4Yc24PNkkD4";

$data = file_get_contents($url);
if (!$data) { die("Erreur : Impossible de contacter iCloud."); }

$data = preg_replace("/\r\n /", "", $data); // Unfolding
$events = explode("BEGIN:VEVENT", $data);
$extracted_events = [];
$start_limit = strtotime("-7 days"); 
$end_limit = strtotime("+21 days");  
$now = time();

foreach ($events as $event) {
    preg_match('/SUMMARY[:;](.*)/m', $event, $summary);
    preg_match('/^DTSTART.*?:([0-9T]+Z?)/m', $event, $start);
    preg_match('/RRULE:(.*)/m', $event, $rrule);

    if (isset($summary[1]) && isset($start[1])) {
        $title = trim($summary[1]);
        $base_timestamp = strtotime(trim($start[1]));
        $occurrences = [$base_timestamp];

        if (isset($rrule[1]) && strpos($rrule[1], 'FREQ=WEEKLY') !== false) {
            for ($i = 1; $i <= 52; $i++) { $occurrences[] = strtotime("+$i week", $base_timestamp); }
        }

        foreach ($occurrences as $timestamp) {
            if ($timestamp >= $start_limit && $timestamp <= $end_limit) {
                $extracted_events[] = [
                    "titre" => str_replace(['\\,', '\\;'], [',', ';'], $title),
                    "date" => date("d/m/Y H:i", $timestamp),
                    "ts" => $timestamp
                ];
            }
        }
    }
}

usort($extracted_events, function($a, $b) { return $a['ts'] <=> $b['ts']; });

// --- INTERFACE VISUELLE (HTML) ---
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug Calendrier</title>
    <style>
        body { font-family: sans-serif; background: #121212; color: white; padding: 20px; }
        .stat-bar { background: #1e1e1e; padding: 15px; border-radius: 8px; border-left: 5px solid #CC73E1; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
        .past { opacity: 0.5; font-style: italic; }
        .today { color: #CC73E1; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Contrôle de l'extraction</h1>
    
    <div class="stat-bar">
        <strong>Période :</strong> du <?php echo date("d/m", $start_limit); ?> au <?php echo date("d/m", $end_limit); ?><br>
        <strong>Total :</strong> <?php echo count($extracted_events); ?> événements trouvés.
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Événement</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($extracted_events as $ev): 
                $class = ($ev['ts'] < $now) ? 'past' : '';
                if (date('d/m/Y', $ev['ts']) == date('d/m/Y', $now)) $class = 'today';
            ?>
            <tr class="<?php echo $class; ?>">
                <td><?php echo $ev['date']; ?></td>
                <td><?php echo $ev['titre']; ?></td>
                <td><?php echo ($ev['ts'] < $now) ? '⌛ Passé' : '📅 À venir'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>