<?php
echo "<h1>📁 File Structure Check</h1>";

function listFiles($dir, $prefix = '') {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $path = $dir . '/' . $file;
        $type = is_dir($path) ? '📁' : '📄';
        echo $prefix . $type . ' ' . $file . "<br>";
        
        if (is_dir($path)) {
            listFiles($path, $prefix . '&nbsp;&nbsp;&nbsp;');
        }
    }
}

echo "<h3>Current directory: " . __DIR__ . "</h3>";
listFiles(__DIR__);
?>