<?php
// Get the search string from the URL parameter 's'
$searchString = isset($_GET['s']) ? $_GET['s'] : '';

if ($searchString === '') {
  echo 'Please provide a search string using the "s" parameter in the URL.';
  exit;
}

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
      if (stripos($contents, $searchString) !== false) {
        $results[] = $filePath;
      }
    }
  }
}

// Initialize an empty array to hold the search results
$results = [];

// Start the search from the current directory
searchInDirectory('.', $searchString, $results);

if (!empty($results)) {
  echo 'Found "' . htmlspecialchars($searchString) . '" in the following files:<br>';
  echo '<ul>';
  foreach ($results as $result) {
    echo '<li>' . htmlspecialchars($result) . '</li>';
  }
  echo '</ul>';
} else {
  echo 'No matches found for "' . htmlspecialchars($searchString) . '".';
}
?>
