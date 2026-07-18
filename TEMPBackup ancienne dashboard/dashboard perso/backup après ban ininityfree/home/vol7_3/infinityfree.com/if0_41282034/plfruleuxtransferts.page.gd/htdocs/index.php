<?php
// ── Config ────────────────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_SIZE',   100 * 1024 * 1024); // 100 MB

// Create uploads dir
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

// ── Actions ───────────────────────────────────────────────

// Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $f = $_FILES['file'];
    $error = null;

    if ($f['error'] !== UPLOAD_ERR_OK) {
        $error = 'Erreur lors de l\'upload.';
    } elseif ($f['size'] > MAX_SIZE) {
        $error = 'Fichier trop volumineux (max 100 MB).';
    } else {
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($f['name']));
        $dest = UPLOAD_DIR . time() . '_' . $safeName;
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            $error = 'Impossible de sauvegarder le fichier.';
        }
    }

    if ($error) {
        $uploadStatus = ['ok' => false, 'msg' => $error];
    } else {
        $uploadStatus = ['ok' => true, 'msg' => 'Fichier envoyé avec succès !'];
    }
}

// Download
if (isset($_GET['dl'])) {
    $filename = basename($_GET['dl']);
    $path = UPLOAD_DIR . $filename;
    if (file_exists($path) && is_file($path)) {
        $originalName = preg_replace('/^\d+_/', '', $filename);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $originalName . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: must-revalidate');
        ob_clean(); flush();
        readfile($path);
        exit;
    }
}

// ── File list ─────────────────────────────────────────────
$files = [];
foreach (glob(UPLOAD_DIR . '*') as $path) {
    if (!is_file($path)) continue;
    $filename = basename($path);
    $originalName = preg_replace('/^\d+_/', '', $filename);
    $files[] = [
        'filename'     => $filename,
        'originalName' => $originalName,
        'size'         => filesize($path),
        'time'         => filemtime($path),
    ];
}
usort($files, fn($a, $b) => $b['time'] - $a['time']);

// ── Helpers ───────────────────────────────────────────────
function fmtSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / (1024 * 1024), 1) . ' MB';
}
function getExt($name) {
    $parts = explode('.', $name);
    return count($parts) > 1 ? strtoupper(substr(end($parts), 0, 5)) : '???';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Les quoicoufichiers du feziofeman</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg: #0e0e0e;
      --surface: #161616;
      --border: #2a2a2a;
      --accent: #e8ff47;
      --accent-dim: #b8cc2a;
      --text: #e8e8e8;
      --muted: #666;
      --success: #4ade80;
      --error: #f87171;
      --mono: 'IBM Plex Mono', monospace;
      --sans: 'IBM Plex Sans', sans-serif;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: var(--sans);
      min-height: 100vh;
      padding: 2rem 1rem;
    }

    header {
      max-width: 720px;
      margin: 0 auto 3rem;
      display: flex;
      align-items: baseline;
      gap: 1rem;
      border-bottom: 1px solid var(--border);
      padding-bottom: 1.5rem;
    }

    header h1 {
      font-family: var(--mono);
      font-size: 1.4rem;
      font-weight: 600;
      letter-spacing: -0.02em;
      color: var(--accent);
    }

    header span {
      font-family: var(--mono);
      font-size: 0.75rem;
      color: var(--muted);
    }

    main {
      max-width: 720px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 2.5rem;
    }

    /* Status banner */
    .banner {
      padding: 0.75rem 1rem;
      font-family: var(--mono);
      font-size: 0.8rem;
      border-radius: 3px;
      border-left: 3px solid;
    }
    .banner.ok  { background: rgba(74,222,128,.08); border-color: var(--success); color: var(--success); }
    .banner.err { background: rgba(248,113,113,.08); border-color: var(--error);   color: var(--error); }

    /* Drop Zone */
    #drop-zone {
      border: 1px dashed var(--border);
      border-radius: 4px;
      padding: 3rem 2rem;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.15s, background 0.15s;
    }

    #drop-zone:hover, #drop-zone.drag-over {
      border-color: var(--accent);
      background: rgba(232, 255, 71, 0.03);
    }

    #drop-zone .icon { font-size: 2rem; margin-bottom: 0.75rem; opacity: 0.5; }

    #drop-zone p {
      font-family: var(--mono);
      font-size: 0.85rem;
      color: var(--muted);
      line-height: 1.6;
    }

    #drop-zone p strong { color: var(--accent); font-weight: 500; }

    #file-input { display: none; }

    #selected-name {
      margin-top: 0.75rem;
      font-family: var(--mono);
      font-size: 0.75rem;
      color: var(--text);
      opacity: 0.7;
      min-height: 1.2em;
    }

    /* Progress bar */
    #progress-wrap {
      display: none;
      margin-top: 1rem;
      height: 3px;
      background: var(--border);
      border-radius: 2px;
      overflow: hidden;
    }
    #progress-wrap.visible { display: block; }
    #progress-bar {
      height: 100%;
      width: 0%;
      background: var(--accent);
      transition: width 0.2s ease;
    }

    /* Upload button */
    #upload-btn {
      margin-top: 1.25rem;
      background: var(--accent);
      color: #0e0e0e;
      border: none;
      padding: 0.6rem 1.5rem;
      font-family: var(--mono);
      font-size: 0.8rem;
      font-weight: 600;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      cursor: pointer;
      border-radius: 2px;
      transition: background 0.15s, transform 0.1s;
      display: none;
    }

    #upload-btn:hover { background: var(--accent-dim); }
    #upload-btn:active { transform: scale(0.98); }
    #upload-btn.visible { display: inline-block; }

    /* File list */
    section h2 {
      font-family: var(--mono);
      font-size: 0.7rem;
      font-weight: 500;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 1rem;
    }

    #file-list { display: flex; flex-direction: column; gap: 1px; }

    .file-item {
      background: var(--surface);
      border: 1px solid var(--border);
      padding: 0.9rem 1rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-4px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .file-item:hover { border-color: #3a3a3a; }

    .file-ext {
      font-family: var(--mono);
      font-size: 0.65rem;
      font-weight: 600;
      color: #0e0e0e;
      background: var(--accent);
      padding: 0.15rem 0.35rem;
      border-radius: 2px;
      text-transform: uppercase;
      min-width: 36px;
      text-align: center;
      flex-shrink: 0;
    }

    .file-info { flex: 1; min-width: 0; }

    .file-name {
      font-family: var(--mono);
      font-size: 0.82rem;
      color: var(--text);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .file-meta {
      font-family: var(--mono);
      font-size: 0.68rem;
      color: var(--muted);
      margin-top: 0.2rem;
    }

    .dl-btn {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--text);
      padding: 0.4rem 0.8rem;
      font-family: var(--mono);
      font-size: 0.72rem;
      cursor: pointer;
      border-radius: 2px;
      transition: border-color 0.15s, color 0.15s;
      flex-shrink: 0;
      text-decoration: none;
    }

    .dl-btn:hover { border-color: var(--accent); color: var(--accent); }

    .empty {
      font-family: var(--mono);
      font-size: 0.8rem;
      color: var(--muted);
      padding: 2rem 0;
      text-align: center;
    }
  </style>
</head>
<body>

<header>
  <h1>// Les quoicoufichiers du Feziofeman</h1>
  <span><?= count($files) ?> fichier<?= count($files) !== 1 ? 's' : '' ?></span>
</header>

<main>

  <?php if (isset($uploadStatus)): ?>
    <div class="banner <?= $uploadStatus['ok'] ? 'ok' : 'err' ?>">
      <?= $uploadStatus['ok'] ? '✓' : '✗' ?> <?= htmlspecialchars($uploadStatus['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- Upload form -->
  <form method="POST" enctype="multipart/form-data" id="upload-form">
    <div id="drop-zone">
      <div class="icon">⬆</div>
      <p>Glissez un fichier ici<br>ou <strong>cliquez pour sélectionner</strong></p>
      <div id="selected-name"></div>
      <div id="progress-wrap"><div id="progress-bar"></div></div>
      <button type="submit" id="upload-btn">Envoyer le fichier</button>
      <input type="file" name="file" id="file-input">
    </div>
  </form>

  <!-- File list -->
  <section>
    <h2>Fichiers disponibles</h2>
    <div id="file-list">
      <?php if (empty($files)): ?>
        <div class="empty">Aucun fichier pour l'instant</div>
      <?php else: ?>
        <?php foreach ($files as $f): ?>
          <div class="file-item">
            <span class="file-ext"><?= htmlspecialchars(getExt($f['originalName'])) ?></span>
            <div class="file-info">
              <div class="file-name" title="<?= htmlspecialchars($f['originalName']) ?>">
                <?= htmlspecialchars($f['originalName']) ?>
              </div>
              <div class="file-meta">
                <?= fmtSize($f['size']) ?> · <?= date('d/m/Y H:i', $f['time']) ?>
              </div>
            </div>
            <a class="dl-btn" href="?dl=<?= urlencode($f['filename']) ?>">↓ télécharger</a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

</main>

<script>
  const dropZone = document.getElementById('drop-zone');
  const fileInput = document.getElementById('file-input');
  const uploadBtn = document.getElementById('upload-btn');
  const selectedName = document.getElementById('selected-name');
  const progressWrap = document.getElementById('progress-wrap');
  const progressBar = document.getElementById('progress-bar');
  const form = document.getElementById('upload-form');

  function selectFile(file) {
    const kb = file.size < 1024 * 1024
      ? (file.size / 1024).toFixed(1) + ' KB'
      : (file.size / (1024 * 1024)).toFixed(1) + ' MB';
    selectedName.textContent = file.name + ' (' + kb + ')';
    uploadBtn.classList.add('visible');
  }

  dropZone.addEventListener('click', (e) => {
    if (e.target !== uploadBtn) fileInput.click();
  });

  fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) selectFile(fileInput.files[0]);
  });

  uploadBtn.addEventListener('click', (e) => e.stopPropagation());

  dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('drag-over');
  });

  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));

  dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    if (e.dataTransfer.files[0]) {
      const dt = new DataTransfer();
      dt.items.add(e.dataTransfer.files[0]);
      fileInput.files = dt.files;
      selectFile(e.dataTransfer.files[0]);
    }
  });

  // Progress simulation during form submit
  form.addEventListener('submit', () => {
    if (!fileInput.files[0]) return;
    progressWrap.classList.add('visible');
    let w = 0;
    const iv = setInterval(() => {
      w = Math.min(w + Math.random() * 12, 90);
      progressBar.style.width = w + '%';
    }, 150);
    form.addEventListener('submit', () => clearInterval(iv), { once: true });
  });
</script>

</body>
</html>
