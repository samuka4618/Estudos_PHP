<?php
require 'conexao.php';

try {
    // Criação da tabela 'Desktop'
    $db->exec("CREATE TABLE IF NOT EXISTS Desktop (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        marca TEXT NOT NULL,
        modelo TEXT NOT NULL,
        numero_serie TEXT NOT NULL UNIQUE,
        data_aquisicao DATE,
        status TEXT DEFAULT 'Disponivel',
        setor_responsavel TEXT,
        observacoes TEXT
    )");

    echo "Tabela 'Desktop' criada com sucesso!";
} catch (PDOException $e) {
    die("Erro ao criar tabela 'Desktop': " . $e->getMessage());
}
?>
