<?php
/**
 * Este arquivo é o ponto de entrada da aplicação.
 * Sua única função é redirecionar o usuário para a página de login.
 */

// Redireciona o navegador para a página de login localizada na pasta /view/
header("Location: view/login");

// Garante que o script PHP pare a execução após o cabeçalho de redirecionamento ser enviado.
// Isso é uma boa prática para evitar que código adicional seja executado desnecessariamente.
exit;
?>
