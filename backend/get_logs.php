<?php
require_once '../classes/config.php';
require_once '../classes/LogService.php';

// Defina o tipo de retorno como JSON
header('Content-Type: application/json');

// Verifica e sanitiza os parâmetros de offset e limit
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

// Garante que valores negativos não sejam usados
$offset = max(0, $offset);
$limit = max(10, $limit);

// Instancia o serviço de logs
$logService = new LogService($conexao);

// Obtém os logs
$logs = $logService->getLogs($limit, $offset);

// Retorna os logs como JSON
echo json_encode($logs);
exit;
