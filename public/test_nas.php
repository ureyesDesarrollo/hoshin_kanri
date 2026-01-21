<?php
$base = '\\\\NAS01\\ARCHIVOS_SISTEMAS\\HK_EVIDENCIAS\\';
$dir  = $base . 'test\\';
$file = $dir . 'ping.txt';

if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
  http_response_code(500);
  die("NO mkdir: $dir");
}

if (file_put_contents($file, "OK " . date('c')) === false) {
  http_response_code(500);
  die("NO write: $file");
}

echo "NAS OK: $file";
