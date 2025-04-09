<?php
require 'conexao.php';

try {
    $db->exec("CREATE TABLE IF NOT EXISTS vinculos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        colaborador_id INTEGER NOT NULL,
        equipamento_tipo TEXT NOT NULL,
        equipamento_id INTEGER NOT NULL,
        data_entrega DATE NOT NULL,
        data_devolucao DATE,
        status TEXT NOT NULL,
        observacoes TEXT,
        usuario_responsavel_id INTEGER,
        FOREIGN KEY(colaborador_id) REFERENCES colaboradores(id),
        FOREIGN KEY(usuario_responsavel_id) REFERENCES usuarios(id)
    )");

    echo "Tabela 'vinculos' criada com sucesso!";
} catch (PDOException $e) {
    die("Erro ao criar tabela 'vinculos': " . $e->getMessage());
}
?>
