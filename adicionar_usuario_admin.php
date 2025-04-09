<?php
require 'conexao.php';

try {
    // Verifica se já existe um usuário administrador
    $stmt = $db->query("SELECT id FROM usuarios WHERE nivel_acesso = 'admin' LIMIT 1");
    $adminExiste = $stmt->fetch();

    if (!$adminExiste) {
        // Cria um usuário administrador padrão
        $senhaHash = password_hash('Admin@123', PASSWORD_BCRYPT);
        $db->exec("INSERT INTO usuarios (nome, email, senha_hash, nivel_acesso) 
                   VALUES ('Administrador', 'admin@empresa.com', '$senhaHash', 'admin')");
        echo "Usuário administrador criado com sucesso!<br>Email: admin@empresa.com<br>Senha: Admin@123<br>";
    } else {
        echo "Usuário administrador já existe.<br>";
    }
} catch (PDOException $e) {
    die("Erro ao criar usuário administrador: " . $e->getMessage());
}
?>
