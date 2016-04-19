<?php
function create_log($message) {
    $path = '/var/tmp/annfbavan1.log'; //change path for Windows
    error_log($message, 3, $path);
}

function getConnection() {
    $dbhost = "127.0.0.1";
    $dbuser = "root";
    $dbpass = "kurt20cobain2.";
    $dbname = "annFB";
    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
}

?>
