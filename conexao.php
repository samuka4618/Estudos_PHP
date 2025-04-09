<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Evita loop de redirecionamento
if (isset($_SESSION['redirect_count'])) {
    if ($_SESSION['redirect_count'] > 3) {
        session_unset();
        session_destroy();
        die("Erro crítico: Muitos redirecionamentos. Por favor, recarregue a página.");
    }
    $_SESSION['redirect_count']++;
} else {
    $_SESSION['redirect_count'] = 1;
}

try {
    $db = new PDO('sqlite:'.__DIR__.'/controle_equipamentos.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec("PRAGMA foreign_keys = ON");
    
    // Reset do contador se a conexão for bem-sucedida
    unset($_SESSION['redirect_count']);
} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro na conexão com o banco de dados";
    if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
        header("Location: index.php");
        exit;
    }
    die("Erro crítico no banco de dados: " . $e->getMessage());
}
?>