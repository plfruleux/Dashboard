<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;
date_default_timezone_set('Asia/Ho_Chi_Minh');

$jsonFile = __DIR__.'/rappels.json';
$action   = $_GET['action'] ?? '';
$listName = trim($_GET['list'] ?? '');

function loadData($file){ return file_exists($file) ? (json_decode(file_get_contents($file),true) ?: []) : []; }
function saveData($file,$data){
    $ok = file_put_contents($file,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    return $ok!==false;
}
function cleanItem($r){
    return [
        'title'    => substr(trim($r['title'] ?? $r['nom'] ?? ''),0,200),
        'due'      => isset($r['due']) ? trim($r['due']) : null,
        'complete' => !empty($r['complete']) || !empty($r['done']),
        'notes'    => substr(trim($r['notes'] ?? $r['description'] ?? ''),0,500),
    ];
}
function logDebug($label,$payload){ file_put_contents(__DIR__.'/rappels_debug.txt',date('c')." [$label]\n$payload\n\n",FILE_APPEND); }
function respond($data,$code=200){ http_response_code($code); echo json_encode($data); exit; }

/* ── GET ── */
if ($_SERVER['REQUEST_METHOD']==='GET') {
    if ($action==='diag') {
        respond([
            'php_version'=>PHP_VERSION,
            'dir_writable'=>is_writable(__DIR__),
            'json_writable'=>is_writable(__DIR__) && (!file_exists($jsonFile) || is_writable($jsonFile)),
            'json_exists'=>file_exists($jsonFile),
            'json_size'=>file_exists($jsonFile)?filesize($jsonFile):0,
            'time'=>date('c')
        ]);
    }
    if ($action==='log_raw') { logDebug('GET log_raw', file_get_contents('php://input')); respond(['ok'=>true]); }
    if ($action==='lists') {
        $data=loadData($jsonFile);
        $names=array_values(array_filter(array_keys($data),fn($k)=>$k!=='_updated'));
        respond(['lists'=>$names,'updated'=>$data['_updated']??null]);
    }
    if ($action==='clear' && $listName) { $data=loadData($jsonFile); $data[$listName]=[]; $data['_updated']=date('c'); saveData($jsonFile,$data); respond(['ok'=>true,'cleared'=>$listName]); }
    if ($action==='clear_all') { saveData($jsonFile,['_updated'=>date('c')]); respond(['ok'=>true]); }
    if (!file_exists($jsonFile)) {
        respond(['setup_required'=>true,'message'=>'Configurer le Raccourci iOS.','instructions'=>[
            '1. Rappels -> Récupérer (non complétés, toutes listes)',
            '2. Répéter : Dictionnaire {title,list,due,complete}',
            '3. POST JSON {"items":[...]} vers /api_rappels.php',
            '4. Automatiser toutes les heures',
        ]]);
    }
    $data=loadData($jsonFile);
    if ($listName) respond(['list'=>$listName,'items'=>$data[$listName]??[],'count'=>count($data[$listName]??[])]);
    respond($data);
}

/* ── POST ── */
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $raw=file_get_contents('php://input'); logDebug('POST raw',$raw);
    $body=json_decode($raw,true); if(!$body && !empty($_POST)) $body=$_POST;
    if(!$body){ respond(['ok'=>false,'error'=>'JSON invalide','json_error'=>json_last_error_msg()],400); }

    $data=loadData($jsonFile);

    if(isset($body['items']) && is_array($body['items'])){
        $byList=[];
        foreach($body['items'] as $item){ $ln=trim($item['list']??$item['liste']??'Sans liste'); $byList[$ln][] = cleanItem($item); }
        foreach($byList as $ln=>$items){ $data[$ln]=$items; }
        $data['_updated']=date('c');
        if(!saveData($jsonFile,$data)) respond(['ok'=>false,'error'=>'Echec écriture rappels.json'],500);
        $counts=[]; foreach($byList as $ln=>$items){ $counts[$ln]=count($items); }
        respond(['ok'=>true,'lists_updated'=>array_keys($byList),'counts'=>$counts]);
    }

    if(isset($body['list'],$body['items'])){
        $ln=trim($body['list']);
        $items=array_map('cleanItem',(array)$body['items']);
        usort($items,fn($a,$b)=>strcmp($a['due']??'z',$b['due']??'z'));
        $data[$ln]=$items; $data['_updated']=date('c');
        if(!saveData($jsonFile,$data)) respond(['ok'=>false,'error'=>'Echec écriture rappels.json'],500);
        respond(['ok'=>true,'list'=>$ln,'count'=>count($items)]);
    }

    if(isset($body['title'])){
        $ln=trim($body['list']??$body['liste']??'Sans liste');
        if(!isset($data[$ln])||!is_array($data[$ln])) $data[$ln]=[];
        foreach($data[$ln] as $ex){ if(($ex['title']??'')===trim($body['title'])) respond(['ok'=>true,'skipped'=>true]); }
        $data[$ln][]=cleanItem($body); $data['_updated']=date('c');
        if(!saveData($jsonFile,$data)) respond(['ok'=>false,'error'=>'Echec écriture rappels.json'],500);
        respond(['ok'=>true,'list'=>$ln,'title'=>trim($body['title'])]);
    }

    $raw_items = $body['rappels'] ?? (isset($body[0]) ? $body : null);
    if($raw_items){
        $clean=array_map('cleanItem',$raw_items);
        $data['Rappels']=$clean; $data['_updated']=date('c');
        if(!saveData($jsonFile,$data)) respond(['ok'=>false,'error'=>'Echec écriture rappels.json'],500);
        respond(['ok'=>true,'list'=>'Rappels','count'=>count($clean)]);
    }

    respond(['ok'=>false,'error'=>'Format non reconnu','expected'=>'{"items":[{title,list,due,complete}]}'],400);
}

respond(['ok'=>false,'error'=>'Méthode non autorisée'],405);
