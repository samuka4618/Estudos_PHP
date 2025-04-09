<?php
require 'conexao.php';

try {
    // Criação da tabela 'colaboradores' se não existir
    $db->exec("CREATE TABLE IF NOT EXISTS colaboradores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        cpf TEXT NOT NULL UNIQUE,
        setor TEXT NOT NULL,
        email TEXT,
        status TEXT DEFAULT 'Ativo',
        equipamento_atual_id INTEGER DEFAULT NULL
    )");
    echo "Tabela 'colaboradores' criada ou já existente.<br>";

    // Criação da tabela 'usuarios' se não existir
    $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        senha_hash TEXT NOT NULL,
        nivel_acesso TEXT NOT NULL
    )");
    echo "Tabela 'usuarios' criada ou já existente.<br>";

    // Criação da tabela 'vinculos' se não existir
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
    echo "Tabela 'vinculos' criada ou já existente.<br>";

    // Criação da tabela 'movimentacoes' se não existir
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
    echo "Tabela 'movimentacoes' criada ou já existente.<br>";

    // Criação das tabelas de equipamentos se não existirem
    $equipamentos = [
        'notebooks' => "CREATE TABLE IF NOT EXISTS notebooks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            marca TEXT NOT NULL,
            modelo TEXT NOT NULL,
            numero_serie TEXT NOT NULL UNIQUE,
            data_aquisicao DATE,
            status TEXT DEFAULT 'Disponivel',
            setor_responsavel TEXT,
            observacoes TEXT
        )",
        'desktops' => "CREATE TABLE IF NOT EXISTS desktops (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            marca TEXT NOT NULL,
            modelo TEXT NOT NULL,
            numero_serie TEXT NOT NULL UNIQUE,
            data_aquisicao DATE,
            status TEXT DEFAULT 'Disponivel',
            setor_responsavel TEXT,
            observacoes TEXT
        )",
        'celulares' => "CREATE TABLE IF NOT EXISTS celulares (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            marca TEXT NOT NULL,
            modelo TEXT NOT NULL,
            numero_serie TEXT NOT NULL UNIQUE,
            data_aquisicao DATE,
            status TEXT DEFAULT 'Disponivel',
            setor_responsavel TEXT,
            imei1 TEXT,
            imei2 TEXT,
            observacoes TEXT
        )",
        'tablets' => "CREATE TABLE IF NOT EXISTS tablets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            marca TEXT NOT NULL,
            modelo TEXT NOT NULL,
            numero_serie TEXT NOT NULL UNIQUE,
            data_aquisicao DATE,
            status TEXT DEFAULT 'Disponivel',
            setor_responsavel TEXT,
            imei1 TEXT,
            observacoes TEXT
        )"
    ];

    foreach ($equipamentos as $nome => $sql) {
        $db->exec($sql);
        echo "Tabela '$nome' criada ou já existente.<br>";
    }

    // Adiciona coluna 'equipamento_atual_id' na tabela 'colaboradores', se necessário
    $colunasColaboradores = $db->query("PRAGMA table_info(colaboradores)")->fetchAll(PDO::FETCH_ASSOC);
    $colunaEquipamentoAtual = array_filter($colunasColaboradores, fn($coluna) => $coluna['name'] === 'equipamento_atual_id');

    if (empty($colunaEquipamentoAtual)) {
        $db->exec("ALTER TABLE colaboradores ADD COLUMN equipamento_atual_id INTEGER DEFAULT NULL");
        echo "Coluna 'equipamento_atual_id' adicionada à tabela 'colaboradores'.<br>";
    }

    // Adiciona coluna 'usuario_responsavel_id' na tabela 'vinculos', se necessário
    $colunasVinculos = $db->query("PRAGMA table_info(vinculos)")->fetchAll(PDO::FETCH_ASSOC);
    $colunaUsuarioResponsavel = array_filter($colunasVinculos, fn($coluna) => $coluna['name'] === 'usuario_responsavel_id');

    if (empty($colunaUsuarioResponsavel)) {
        $db->exec("ALTER TABLE vinculos ADD COLUMN usuario_responsavel_id INTEGER DEFAULT NULL");
        echo "Coluna 'usuario_responsavel_id' adicionada à tabela 'vinculos'.<br>";
    }

    echo "Atualização concluída com sucesso!";
} catch (PDOException $e) {
    die("Erro ao atualizar o banco de dados: " . $e->getMessage());
}
?>
