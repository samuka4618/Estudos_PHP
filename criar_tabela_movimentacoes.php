<?php
require 'conexao.php';

try {
    // Criação da tabela 'movimentacoes'
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

    echo "Tabela 'movimentacoes' criada com sucesso!";
} catch (PDOException $e) {
    die("Erro ao criar tabela 'movimentacoes': " . $e->getMessage());
}
?>