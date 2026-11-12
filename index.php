<?php
// Load or initialize labels
$labelsFile = __DIR__ . '/labels.json';
$labels = file_exists($labelsFile) ? json_decode(file_get_contents($labelsFile), true) : [];
if (!is_array($labels)) $labels = [];


$isLocalAdmin = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);

// Handle label updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLocalAdmin) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['file'], $input['label'])) {
        $file = basename($input['file']); // Prevent directory traversal
        $label = trim($input['label']);
        
        if ($label === '') {
            unset($labels[$file]);
        } else {
            $labels[$file] = $label;
        }
        
        file_put_contents($labelsFile, json_encode($labels, JSON_PRETTY_PRINT));
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        exit;
    }
}

// Helper for human-readable file sizes
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Scan directory and filter out hidden/system files
$ignore = ['.', '..', 'index.php', 'labels.json', '.htaccess'];
$files = array_diff(scandir(__DIR__), $ignore);

// Sort by newest modified first
usort($files, function($a, $b) {
    return filemtime(__DIR__ . '/' . $b) <=> filemtime(__DIR__ . '/' . $a);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Directory</title>
    <style>
        :root {
            --bg: #121212;
            --surface: #1e1e1e;
            --border: #2e2e2e;
            --text: #e0e0e0;
            --muted: #888888;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --tag-bg: #263859;
            --tag-text: #93c5fd;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 2rem;
            line-height: 1.5;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        h1 { font-size: 1.4rem; font-weight: 600; font-family: monospace; }
        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--muted);
        }
        .search-bar {
            width: 100%;
            padding: 0.7rem 1rem;
            margin-bottom: 1rem;
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .search-bar:focus { outline: 1px solid var(--accent); }
        .file-list {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }
        .file-row {
            display: grid;
            grid-template-columns: 1fr 120px 160px 110px;
            align-items: center;
            padding: 0.85rem 1.2rem;
            border-bottom: 1px solid var(--border);
            gap: 1rem;
            transition: background 0.15s;
        }
        .file-row:last-child { border-bottom: none; }
        .file-row:hover { background: #252525; }
        .file-header {
            background: #181818;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            font-weight: 600;
        }
        .file-info { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
        .file-name {
            font-weight: 500;
            color: var(--text);
            text-decoration: none;
            word-break: break-all;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .label-wrapper { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .label-tag {
            display: inline-block;
            background: var(--tag-bg);
            color: var(--tag-text);
            font-size: 0.78rem;
            padding: 0.15rem 0.55rem;
            border-radius: 4px;
        }
        .edit-btn {
            background: none;
            border: 1px dashed var(--border);
            color: var(--muted);
            font-size: 0.72rem;
            padding: 0.1rem 0.45rem;
            border-radius: 4px;
            cursor: pointer;
        }
        .edit-btn:hover { color: var(--text); border-color: var(--muted); }
        .label-input {
            background: var(--bg);
            border: 1px solid var(--accent);
            color: var(--text);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            width: 240px;
        }
        .meta { font-size: 0.85rem; color: var(--muted); font-family: monospace; }
        .btn-download {
            display: inline-block;
            text-align: center;
            background: var(--accent);
            color: #fff;
            text-decoration: none;
            padding: 0.4rem 0.8rem;
            border-radius: 5px;
            font-size: 0.82rem;
            font-weight: 500;
            transition: background 0.15s;
        }
        .btn-download:hover { background: var(--accent-hover); }
        .empty { padding: 2rem; text-align: center; color: var(--muted); }
        @media (max-width: 680px) {
            .file-row { grid-template-columns: 1fr auto; }
            .hide-mobile { display: none; }
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>// RawIndex</h1>
        <span class="badge">
            <?php echo $isLocalAdmin ? 'Host Mode (Click labels to edit)' : 'Read-Only Mode'; ?>
        </span>
    </header>

    <input type="text" id="search" class="search-bar" placeholder="Filter files by name or label...">

    <div class="file-list">
        <div class="file-row file-header">
            <div>File / Label</div>
            <div class="hide-mobile">Size</div>
            <div class="hide-mobile">Date Modified</div>
            <div style="text-align: right;">Action</div>
        </div>

        <?php if (empty($files)): ?>
            <div class="empty">No files in directory.</div>
        <?php else: ?>
            <?php foreach ($files as $file): 
                $path = __DIR__ . '/' . $file;
                if (is_dir($path)) continue; // Skip folders or adjust if needed
                $size = formatSize(filesize($path));
                $date = date("Y-m-d H:i", filemtime($path));
                $label = $labels[$file] ?? '';
            ?>
            <div class="file-row item-row" data-search="<?php echo htmlspecialchars(strtolower($file . ' ' . $label)); ?>">
                <div class="file-info">
                    <span class="file-name"><?php echo htmlspecialchars($file); ?></span>
                    <div class="label-wrapper" data-file="<?php echo htmlspecialchars($file); ?>">
                        <?php if ($label !== ''): ?>
                            <span class="label-tag"><?php echo htmlspecialchars($label); ?></span>
                        <?php endif; ?>
                        <?php if ($isLocalAdmin): ?>
                            <button class="edit-btn" onclick="editLabel(this)">
                                <?php echo $label === '' ? '+ Add Label' : 'Edit'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="meta hide-mobile"><?php echo $size; ?></div>
                <div class="meta hide-mobile"><?php echo $date; ?></div>
                <div style="text-align: right;">
                    <a class="btn-download" href="<?php echo rawurlencode($file); ?>" download>Download</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Live search filter
document.getElementById('search').addEventListener('input', function(e) {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('.item-row').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});

// Inline label editing (Admin only)
function editLabel(btn) {
    const wrapper = btn.closest('.label-wrapper');
    const file = wrapper.dataset.file;
    const tag = wrapper.querySelector('.label-tag');
    const currentText = tag ? tag.textContent : '';

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'label-input';
    input.value = currentText;
    input.placeholder = 'Enter label / description...';

    wrapper.innerHTML = '';
    wrapper.appendChild(input);
    input.focus();

    const save = () => {
        const newLabel = input.value.trim();
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ file: file, label: newLabel })
        }).then(() => location.reload());
    };

    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') save();
        if (e.key === 'Escape') location.reload();
    });
    input.addEventListener('blur', save);
}
</script>
</body>
</html>