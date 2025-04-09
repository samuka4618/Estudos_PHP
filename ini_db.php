<?php
require 'conexao.php';

try {
    // Tabela de movimentações (vinculos)
    $db->exec("CREATE TABLE IF NOT EXISTS movimentacoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        colaborador_origem_id INTEGER,
        colaborador_destino_id INTEGER NOT NULL,
        equipamento_tipo TEXT NOT NULL,
        equipamento_id INTEGER NOT NULL,
        data_movimentacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        usuario_responsavel_id INTEGER NOT NULL,
        observacoes TEXT,
        FOREIGN KEY(colaborador_origem_id) REFERENCES colaboradores(id),
        FOREIGN KEY(colaborador_destino_id) REFERENCES colaboradores(id),
        FOREIGN KEY(usuario_responsavel_id) REFERENCES usuarios(id)
    )");

    // Adiciona coluna de equipamento_atual_id na tabela colaboradores
    $db->exec("ALTER TABLE colaboradores ADD COLUMN equipamento_atual_id INTEGER DEFAULT NULL");

    echo "Banco de dados atualizado com sucesso!";
} catch (PDOException $e) {
    die("Erro ao atualizar banco de dados: " . $e->getMessage());
}
?>