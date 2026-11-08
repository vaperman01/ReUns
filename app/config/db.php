<?php
function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO('mysql:host='.env('DB_HOST','localhost').';dbname='.env('DB_NAME','reuns').';charset=utf8mb4',
      env('DB_USER','root'), env('DB_PASS',''),
      [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
  }
  return $pdo;
}
