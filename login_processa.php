<?php
// Limpeza total do buffer
while (ob_get_level()) ob_end_clean();

// Inicia sessão apenas se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já estiver logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Verifica se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro'] = "Método inválido";
    header("Location: index.php");
    exit;
}

// Dados do formulário
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'] ?? '';

// Validação básica
if (empty($email) || empty($senha)) {
    $_SESSION['erro'] = "Email e senha são obrigatórios";
    header("Location: index.php");
    exit;
}

// Conexão com banco de dados
try {
    $db = new PDO('sqlite:'.__DIR__.'/controle_equipamentos.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Busca usuário
    $stmt = $db->prepare("SELECT id, nome, senha_hash, nivel_acesso FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
        // Regenera ID da sessão por segurança
        session_regenerate_id(true);
        
        // Define dados na sessão
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
        
        // Redireciona para dashboard
        header("Location: dashboard.php");
        exit;
    } else {
        $_SESSION['erro'] = "Credenciais inválidas";
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro no sistema. Tente novamente mais tarde.";
    header("Location: index.php");
    exit;
}
?>