<?php
// Limpeza total do buffer
while (ob_get_level()) ob_end_clean();

// Inicia e destrói a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa todos os dados da sessão
$_SESSION = [];

// Destrói o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão
session_destroy();

// Redireciona para login
header("Location: index.php");
exit;
?>