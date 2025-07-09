<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "banco_questoes";
$port = 3306;

$conexao = new mysqli($servername, $username, $password, $dbname, $port);

if ($conexao->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conexao->connect_error);
}
?>
