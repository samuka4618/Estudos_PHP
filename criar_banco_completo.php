<?php
try {
    // Cria o banco de dados SQLite
    $db = new PDO('sqlite:'.__DIR__.'/controle_equipamentos_novo.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criação da tabela 'usuarios'
    $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        senha_hash TEXT NOT NULL,
        nivel_acesso TEXT NOT NULL
    )");

    // Criação da tabela 'colaboradores'
    $db->exec("CREATE TABLE IF NOT EXISTS colaboradores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        cpf TEXT NOT NULL UNIQUE,
        setor TEXT NOT NULL,
        email TEXT,
        status TEXT DEFAULT 'Ativo',
        equipamento_atual_id INTEGER DEFAULT NULL
    )");

    // Criação da tabela 'notebooks'
    $db->exec("CREATE TABLE IF NOT EXISTS notebooks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        marca TEXT NOT NULL,
        modelo TEXT NOT NULL,
        numero_serie TEXT NOT NULL UNIQUE,
        data_aquisicao DATE,
        status TEXT DEFAULT 'Disponivel',
        setor_responsavel TEXT,
        observacoes TEXT
    )");

    // Criação da tabela 'desktops'
    $db->exec("CREATE TABLE IF NOT EXISTS desktops (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        marca TEXT NOT NULL,
        modelo TEXT NOT NULL,
        numero_serie TEXT NOT NULL UNIQUE,
        data_aquisicao DATE,
        status TEXT DEFAULT 'Disponivel',
        setor_responsavel TEXT,
        observacoes TEXT
    )");

    // Criação da tabela 'celulares'
    $db->exec("CREATE TABLE IF NOT EXISTS celulares (
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
    )");

    // Criação da tabela 'tablets'
    $db->exec("CREATE TABLE IF NOT EXISTS tablets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        marca TEXT NOT NULL,
        modelo TEXT NOT NULL,
        numero_serie TEXT NOT NULL UNIQUE,
        data_aquisicao DATE,
        status TEXT DEFAULT 'Disponivel',
        setor_responsavel TEXT,
        imei1 TEXT,
        observacoes TEXT
    )");

    // Criação da tabela 'fones'
    $db->exec("CREATE TABLE IF NOT EXISTS fones (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        marca TEXT NOT NULL,
        modelo TEXT NOT NULL,
        patrimonio TEXT NOT NULL UNIQUE,
        data_aquisicao DATE,
        status TEXT DEFAULT 'Disponivel',
        observacoes TEXT
    )");

    // Criação da tabela 'numeros'
    $db->exec("CREATE TABLE IF NOT EXISTS numeros (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        numero TEXT NOT NULL UNIQUE,
        status TEXT DEFAULT 'Disponivel',
        observacoes TEXT
    )");

    // Criação da tabela 'vinculos'
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

    echo "Banco de dados criado com sucesso!";
} catch (PDOException $e) {
    die("Erro ao criar banco de dados: " . $e->getMessage());
}
?>
