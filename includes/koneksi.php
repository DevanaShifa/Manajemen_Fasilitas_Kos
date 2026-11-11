<?php
$hostname = "localhost";
$username = "root";
$password = "";
$database = "kos_ndut";

$connect = mysqli_connect($hostname, $username, $password, $database);
if(!$connect){
    die("Koneksi Gagal: " . mysqli_connect_error());
}

?>