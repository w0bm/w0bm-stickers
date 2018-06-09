<?php

require_once "config.php";

$dbh = new PDO($cfg_pgsql_dsn);
$query = $dbh->prepare("SELECT SUM(count) FROM orders");
if(!$query->execute())
    exit_response(500, c_error("database_error"));

exit_response(200, null, ["stickers_count" => 93 - $query->fetch()[0]]);

?>
