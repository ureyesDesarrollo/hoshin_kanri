<?php
function db()
{
  static $conn;

  if (!$conn) {
    $conn = new mysqli('localhost', 'root', '', 'hoshin_kanri');
    if ($conn->connect_error) {
      die('Error de conexión a BD');
    }
    $conn->set_charset('utf8');
  }

  return $conn;
}
