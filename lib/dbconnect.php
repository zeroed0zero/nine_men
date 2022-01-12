<?php
$host="localhost";
$db = "nine_men_morris";
require_once "../config.php";

$user=$DB_USER;
$pass=$DB_PASS;

if(gethostname() == "users.iee.ihu.gr") 
	$mysqli = new mysqli($host, $user, $pass, $db, null, "/home/student/it/2017/it174861/mysql/run/mysql.sock");
else
	$mysqli = new mysqli($host, $user, null, $db);

if ($mysqli->connect_errno) {
	echo "Failed to connect to MySQL: (". 
	$mysqli->connect_errno. ") " .$mysqli->connect_error;
}
?>