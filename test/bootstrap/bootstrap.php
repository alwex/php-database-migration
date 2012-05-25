<?php
// bdd connector initialization
try {
  $db = new PDO("pgsql:dbname=php_db_migrate_test;host=localhost", "php_db_migrate_test", "php_db_migrate_password");
} catch (PDOException $e) {
  print ($e->getMessage()."\n");
  exit;
}

$output = null;
exec("php ../../migrate --help", $output);

var_dump($output);
?>
