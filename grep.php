<?php
$searchString = isset($_GET['s']) ? trim($_GET['s']) : '';

// Function to recursively search PHP files
function searchInDirectory($dir, $searchString, &$results) {
  $files = scandir($dir);

  foreach ($files as $file) {
    if ($file === '.' || $file === '..') {
      continue;
    }

    $filePath = $dir . DIRECTORY_SEPARATOR . $file;

    if (is_dir($filePath)) {
      searchInDirectory($filePath, $searchString, $results);
    } elseif (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
      $contents = file_get_contents($filePath);
      $lines = explode("\n", $contents);
      foreach ($lines as $lineNumber => $line) {
        if (stripos($line, $searchString) !== false) {
          if (!isset($results[$filePath])) {
            $results[$filePath] = [
              'file' => $filePath,
              'count' => 0,
              'matches' => []
            ];
          }
          $results[$filePath]['count'] += 1;
          $results[$filePath]['matches'][] = [
            'line' => $lineNumber + 1,
            'content' => $line
          ];
        }
      }
    }
  }
}

$results = [];

if ($searchString !== '') {
  searchInDirectory('.', $searchString, $results);
}

$resultsList = array_values($results);
usort($resultsList, function ($a, $b) {
  return $b['count'] <=> $a['count'];
});
$resultCount = count($resultsList);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PHP Grep</title>
  <style>
    :root {
      color-scheme: light;
      --bg: #f5f7fb;
      --card: #ffffff;
      --text: #111827;
      --muted: #6b7280;
      --accent: #2563eb;
      --accent-light: #dbeafe;
      --border: #e5e7eb;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .page {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    header {
      padding: 48px 24px 24px;
      background: radial-gradient(circle at top, #e0f2fe, transparent 65%), var(--bg);
    }

    .hero {
      max-width: 960px;
      margin: 0 auto;
    }

    h1 {
      font-size: clamp(2rem, 3vw, 3rem);
      margin: 0 0 8px;
    }

    .subtitle {
      margin: 0;
      color: var(--muted);
      font-size: 1.05rem;
    }

    main {
      flex: 1;
      padding: 0 24px 48px;
    }

    .content {
      max-width: 960px;
      margin: -24px auto 0;
      display: grid;
      gap: 24px;
    }

    .card {
      background: var(--card);
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
      border: 1px solid var(--border);
    }

    form {
      display: grid;
      gap: 16px;
    }

    label {
      font-weight: 600;
    }

    .input-row {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
    }

    input[type="text"] {
      flex: 1 1 320px;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid var(--border);
      font-size: 1rem;
    }

    button {
      background: var(--accent);
      color: #fff;
      border: none;
      padding: 12px 20px;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 10px 16px rgba(37, 99, 235, 0.2);
    }

    button:hover {
      filter: brightness(1.03);
    }

    .hint {
      color: var(--muted);
      font-size: 0.95rem;
      margin: 0;
    }

    .results-header {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .pill {
      background: var(--accent-light);
      color: var(--accent);
      padding: 6px 12px;
      border-radius: 999px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .badge {
      background: #e5e7eb;
      color: #111827;
      padding: 4px 10px;
      border-radius: 999px;
      font-weight: 600;
      font-size: 0.85rem;
    }

    ul {
      list-style: none;
      padding: 0;
      margin: 16px 0 0;
      display: grid;
      gap: 12px;
    }

    li {
      background: #f9fafb;
      border-radius: 12px;
      padding: 16px;
      border: 1px solid var(--border);
      display: grid;
      gap: 6px;
    }

    .file {
      font-weight: 600;
      color: #1f2937;
    }

    .file-row {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }

    .matches {
      margin: 8px 0 0;
      padding-left: 18px;
      color: var(--muted);
      display: grid;
      gap: 6px;
    }

    details {
      border-radius: 12px;
      background: #f9fafb;
      border: 1px solid var(--border);
      overflow: hidden;
    }

    summary {
      list-style: none;
      cursor: pointer;
      display: block;
    }

    summary::-webkit-details-marker {
      display: none;
    }

    .accordion-header {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      padding: 16px;
    }

    .accordion-header::after {
      content: "▾";
      font-size: 0.9rem;
      color: var(--muted);
      transition: transform 0.2s ease;
    }

    details[open] .accordion-header::after {
      transform: rotate(-180deg);
    }

    .accordion-body {
      padding: 0 16px 16px;
    }

    .matches li {
      background: transparent;
      border: none;
      padding: 0;
      gap: 4px;
    }

    .line {
      font-family: "JetBrains Mono", "SFMono-Regular", ui-monospace, SFMono-Regular, Menlo, monospace;
      background: #111827;
      color: #e5e7eb;
      padding: 10px 12px;
      border-radius: 10px;
      overflow-x: auto;
    }

    .empty-state {
      text-align: center;
      color: var(--muted);
      padding: 20px 12px;
    }

    footer {
      text-align: center;
      padding: 24px;
      color: var(--muted);
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <div class="page">
    <header>
      <div class="hero">
        <h1>PHP Grep</h1>
        <p class="subtitle">Search through PHP files in this directory with a clean, focused interface.</p>
      </div>
    </header>
    <main>
      <div class="content">
        <section class="card">
          <form method="get" action="">
            <label for="search">Search term</label>
            <div class="input-row">
              <input id="search" type="text" name="s" placeholder="Try \"function\", \"class\", or a variable name" value="<?php echo htmlspecialchars($searchString); ?>">
              <button type="submit">Search</button>
            </div>
            <p class="hint">Search is case-insensitive and scans all PHP files under the current directory.</p>
          </form>
        </section>

        <section class="card">
          <div class="results-header">
            <div>
              <h2 style="margin: 0;">Results</h2>
              <p class="hint" style="margin-top: 6px;">Showing matches for your query.</p>
            </div>
            <span class="pill"><?php echo $resultCount; ?> match<?php echo $resultCount === 1 ? '' : 'es'; ?></span>
          </div>

          <?php if ($searchString === ''): ?>
            <div class="empty-state">
              Enter a search term above to begin.
            </div>
          <?php elseif ($resultCount === 0): ?>
            <div class="empty-state">
              No matches found for "<?php echo htmlspecialchars($searchString); ?>".
            </div>
          <?php else: ?>
            <ul>
              <?php foreach ($resultsList as $result): ?>
                <li>
                  <details>
                    <summary>
                      <div class="accordion-header">
                        <div class="file"><?php echo htmlspecialchars($result['file']); ?></div>
                        <span class="badge"><?php echo $result['count']; ?> match<?php echo $result['count'] === 1 ? '' : 'es'; ?></span>
                      </div>
                    </summary>
                    <div class="accordion-body">
                      <ul class="matches">
                        <?php foreach ($result['matches'] as $match): ?>
                          <li>
                            <div class="file">Line <?php echo $match['line']; ?></div>
                            <div class="line"><?php echo htmlspecialchars($match['content']); ?></div>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  </details>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>
      </div>
    </main>
    <footer>
      Built for fast, readable code search.
    </footer>
  </div>
</body>
</html>
