<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'conexao.php';

// Verifica permissão (admin ou infra)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['nivel_acesso'] !== 'admin' && $_SESSION['nivel_acesso'] !== 'infra')) {
    $_SESSION['erro'] = "Acesso negado";
    header("Location: index.php");
    exit;
}

// Processar formulário de Notebook
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo_equipamento'])) {
    try {
        $tabela = $_POST['tipo_equipamento'];
        
        if (in_array($tabela, ['notebooks', 'desktops', 'celulares', 'tablets', 'numeros', 'fones'])) {
            // Verifica se as colunas necessárias existem no banco de dados
            $colunasTabela = $db->query("PRAGMA table_info($tabela)")->fetchAll(PDO::FETCH_COLUMN, 1);

            if (in_array('nome', $colunasTabela) && in_array($tabela, ['notebooks', 'desktops', 'celulares', 'tablets'])) {
                $campos[] = 'nome';
                $valores[] = $_POST['nome'];
            }

            if (in_array('patrimonio', $colunasTabela) && $tabela === 'fones') {
                $campos[] = 'patrimonio';
                $valores[] = $_POST['patrimonio'];
            }

            // Verifica se o ID está disponível
            do {
                $novoId = rand(1, 999999); // Gera um ID aleatório
                $stmt = $db->prepare("SELECT COUNT(*) FROM $tabela WHERE id = ?");
                $stmt->execute([$novoId]);
                $idDisponivel = $stmt->fetchColumn() == 0;
            } while (!$idDisponivel);

            $campos = ['id', 'marca', 'modelo', 'numero_serie', 'data_aquisicao', 'status', 'setor_responsavel', 'observacoes'];
            $valores = [
                $novoId,
                $_POST['marca'],
                $_POST['modelo'],
                $_POST['numero_serie'],
                $_POST['data_aquisicao'],
                $_POST['status'],
                $_POST['setor_responsavel'],
                $_POST['observacoes']
            ];

            $stmt = $db->prepare("INSERT INTO $tabela (" . implode(', ', $campos) . ") VALUES (" . str_repeat('?, ', count($campos) - 1) . "?)");
            $stmt->execute($valores);
            
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => ucfirst($tabela) . ' cadastrado com sucesso!'
            ];
        }
        
        // Recarrega a página para evitar reenvio do formulário
        header("Location: equipamentos.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao cadastrar equipamento: " . $e->getMessage();
    }
}

// Listar equipamentos
$notebooks = $db->query("SELECT * FROM notebooks ORDER BY marca, modelo")->fetchAll(PDO::FETCH_ASSOC);

$desktops = $db->query("SELECT * FROM desktops ORDER BY marca, modelo")->fetchAll(PDO::FETCH_ASSOC);

// Verifica se a consulta retornou resultados
if (!$desktops) {
    $desktops = []; // Garante que $desktops seja um array vazio se a consulta falhar
}

$celulares = $db->query("SELECT * FROM celulares ORDER BY marca, modelo")->fetchAll(PDO::FETCH_ASSOC);
// Verifica se a consulta retornou resultados
if (!$celulares) {
    $celulares = []; // Garante que $celulares seja um array vazio se a consulta falhar
}

$tablets = $db->query("SELECT * FROM tablets ORDER BY marca, modelo")->fetchAll(PDO::FETCH_ASSOC);
// Verifica se a consulta retornou resultados
if (!$tablets) {
    $tablets = []; // Garante que $tablets seja um array vazio se a consulta falhar
}

$numeros = $db->query("SELECT * FROM numeros ORDER BY numero")->fetchAll(PDO::FETCH_ASSOC);
// Verifica se a consulta retornou resultados
if (!$numeros) {
    $numeros = []; // Garante que $numeros seja um array vazio se a consulta falhar
}

$fones = $db->query("SELECT * FROM fones ORDER BY marca, modelo")->fetchAll(PDO::FETCH_ASSOC);
// Verifica se a consulta retornou resultados
if (!$fones) {
    $fones = []; // Garante que $fones seja um array vazio se a consulta falhar
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Equipamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="bi bi-pc"></i> Cadastro de Equipamentos</h2>
        
        <?php include 'alertas.php'; ?>

        <ul class="nav nav-tabs mt-4" id="equipamentosTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="notebooks-tab" data-bs-toggle="tab" data-bs-target="#notebooks" type="button">
                    <i class="bi bi-laptop"></i> Notebooks
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="desktops-tab" data-bs-toggle="tab" data-bs-target="#desktops" type="button">
                    <i class="bi bi-pc-display"></i> Desktops
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="celulares-tab" data-bs-toggle="tab" data-bs-target="#celulares" type="button">
                    <i class="bi bi-phone"></i> Celulares
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tablets-tab" data-bs-toggle="tab" data-bs-target="#tablets" type="button">
                    <i class="bi bi-tablet"></i> Tablets
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="numeros-tab" data-bs-toggle="tab" data-bs-target="#numeros" type="button">
                    <i class="bi bi-telephone"></i> Números
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="fones-tab" data-bs-toggle="tab" data-bs-target="#fones" type="button">
                    <i class="bi bi-headphones"></i> Fones
                </button>
            </li>
        </ul>
        
        <div class="tab-content border border-top-0 rounded-bottom p-3 mb-4" id="equipamentosTabContent">
            <!-- Aba Notebooks -->
            <div class="tab-pane fade show active" id="notebooks" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Novo Notebook</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="tipo_equipamento" value="notebooks">
                                    <div class="mb-3">
                                        <label class="form-label">Nome*</label>
                                        <input type="text" class="form-control" name="nome" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Marca*</label>
                                        <input type="text" class="form-control" name="marca" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Modelo*</label>
                                        <input type="text" class="form-control" name="modelo" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Número de Série*</label>
                                        <input type="text" class="form-control" name="numero_serie" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Data de Aquisição</label>
                                        <input type="date" class="form-control" name="data_aquisicao">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status*</label>
                                        <select class="form-select" name="status" required>
                                            <option value="Disponivel">Disponível</option>
                                            <option value="Em uso">Em uso</option>
                                            <option value="Manutencao">Manutenção</option>
                                            <option value="Descartado">Descartado</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Setor Responsável</label>
                                        <input type="text" class="form-control" name="setor_responsavel">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Observações</label>
                                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Cadastrar Notebook
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Notebooks Cadastrados</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Nome</th>
                                                <th>Marca</th>
                                                <th>Modelo</th>
                                                <th>Série</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($notebooks as $notebook): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($notebook['nome'] ?? 'Não informado') ?></td>
                                                <td><?= htmlspecialchars($notebook['marca']) ?></td>
                                                <td><?= htmlspecialchars($notebook['modelo']) ?></td>
                                                <td><?= htmlspecialchars($notebook['numero_serie']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $notebook['status'] === 'Disponivel' ? 'success' : 
                                                        ($notebook['status'] === 'Em uso' ? 'primary' : 'warning')
                                                    ?>">
                                                        <?= htmlspecialchars($notebook['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Aba Desktops -->
            <div class="tab-pane fade" id="desktops" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Novo Desktop</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="tipo_equipamento" value="desktops">
                                    <div class="mb-3">
                                        <label class="form-label">Nome*</label>
                                        <input type="text" class="form-control" name="nome" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Marca*</label>
                                        <input type="text" class="form-control" name="marca" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Modelo*</label>
                                        <input type="text" class="form-control" name="modelo" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Número de Série*</label>
                                        <input type="text" class="form-control" name="numero_serie" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Data de Aquisição</label>
                                        <input type="date" class="form-control" name="data_aquisicao">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status*</label>
                                        <select class="form-select" name="status" required>
                                            <option value="Disponivel">Disponível</option>
                                            <option value="Em uso">Em uso</option>
                                            <option value="Manutencao">Manutenção</option>
                                            <option value="Descartado">Descartado</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Setor Responsável</label>
                                        <input type="text" class="form-control" name="setor_responsavel">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Observações</label>
                                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Cadastrar Desktop
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Desktop Cadastrados</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Nome</th>
                                                <th>Marca</th>
                                                <th>Modelo</th>
                                                <th>Série</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($desktops as $desktop): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($desktop['nome'] ?? 'Não informado') ?></td>
                                                <td><?= htmlspecialchars($desktop['marca']) ?></td>
                                                <td><?= htmlspecialchars($desktop['modelo']) ?></td>
                                                <td><?= htmlspecialchars($desktop['numero_serie']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $desktop['status'] === 'Disponivel' ? 'success' : 
                                                        ($desktop['status'] === 'Em uso' ? 'primary' : 'warning')
                                                    ?>">
                                                        <?= htmlspecialchars($desktop['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Aba Celulares -->
            <div class="tab-pane fade" id="celulares" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Novo Celular</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="tipo_equipamento" value="celulares">
                                    <div class="mb-3">
                                        <label class="form-label">Nome*</label>
                                        <input type="text" class="form-control" name="nome" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Marca*</label>
                                        <input type="text" class="form-control" name="marca" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Modelo*</label>
                                        <input type="text" class="form-control" name="modelo" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Número de Série*</label>
                                        <input type="text" class="form-control" name="numero_serie" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Data de Aquisição</label>
                                        <input type="date" class="form-control" name="data_aquisicao">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Imei 1*</label>
                                        <input type="text" class="form-control" name="imei_1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Imei 2*</label>
                                        <input type="text" class="form-control" name="imei_2" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status*</label>
                                        <select class="form-select" name="status" required>
                                            <option value="Disponivel">Disponível</option>
                                            <option value="Em uso">Em uso</option>
                                            <option value="Manutencao">Manutenção</option>
                                            <option value="Descartado">Descartado</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Setor Responsável</label>
                                        <input type="text" class="form-control" name="setor_responsavel">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Observações</label>
                                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Cadastrar Celular
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Celulares Cadastrados</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Nome</th>
                                                <th>Marca</th>
                                                <th>Modelo</th>
                                                <th>Série</th>
                                                <th>IMEI 1</th>
                                                <th>IMEI 2</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($celulares as $celular): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($celular['nome'] ?? 'Não informado') ?></td>
                                                <td><?= htmlspecialchars($celular['marca']) ?></td>
                                                <td><?= htmlspecialchars($celular['modelo']) ?></td>
                                                <td><?= htmlspecialchars($celular['numero_serie']) ?></td>
                                                <td><?= htmlspecialchars($celular['imei_1']) ?></td>
                                                <td><?= htmlspecialchars($celular['imei_2']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $celular['status'] === 'Disponivel' ? 'success' : 
                                                        ($celular['status'] === 'Em uso' ? 'primary' : 'warning')
                                                    ?>">
                                                        <?= htmlspecialchars($celular['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Aba Tablets -->
            <div class="tab-pane fade" id="tablets" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Novo Tablet</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="tipo_equipamento" value="tablets">
                                    <div class="mb-3">
                                        <label class="form-label">Nome*</label>
                                        <input type="text" class="form-control" name="nome" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Marca*</label>
                                        <input type="text" class="form-control" name="marca" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Modelo*</label>
                                        <input type="text" class="form-control" name="modelo" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Número de Série*</label>
                                        <input type="text" class="form-control" name="numero_serie" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">IMEI 1*</label>
                                        <input type="text" class="form-control" name="imei_1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Data de Aquisição</label>
                                        <input type="date" class="form-control" name="data_aquisicao">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status*</label>
                                        <select class="form-select" name="status" required>
                                            <option value="Disponivel">Disponível</option>
                                            <option value="Em uso">Em uso</option>
                                            <option value="Manutencao">Manutenção</option>
                                            <option value="Descartado">Descartado</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Setor Responsável</label>
                                        <input type="text" class="form-control" name="setor_responsavel">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Observações</label>
                                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Cadastrar Tablet
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Tablets Cadastrados</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Nome</th>
                                                <th>Marca</th>
                                                <th>Modelo</th>
                                                <th>Série</th>
                                                <th>IMEI 1</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tablets as $tablet): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($tablet['nome']) ?></td>
                                                <td><?= htmlspecialchars($tablet['marca']) ?></td>
                                                <td><?= htmlspecialchars($tablet['modelo']) ?></td>
                                                <td><?= htmlspecialchars($tablet['numero_serie']) ?></td>
                                                <td><?= htmlspecialchars($tablet['imei_1']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $tablet['status'] === 'Disponivel' ? 'success' : 
                                                        ($tablet['status'] === 'Em uso' ? 'primary' : 'warning')
                                                    ?>">
                                                        <?= htmlspecialchars($tablet['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Aba Números -->
            <div class="tab-pane fade" id="numeros" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Novo Número</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="tipo_equipamento" value="numeros">
                                    <div class="mb-3">
                                        <label class="form-label">Número*</label>
                                        <input type="text" class="form-control" name="numero" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status*</label>
                                        <select class="form-select" name="status" required>
                                            <option value="Ativo">Ativo</option>
                                            <option value="Inativo">Inativo</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Cadastrar Número
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Números Cadastrados</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Número</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($numeros as $numero): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($numero['numero']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $numero['status'] === 'Ativo' ? 'success' : 'danger'
                                                    ?>">
                                                        <?= htmlspecialchars($numero['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Aba Fones -->
            <div class="tab-pane fade" id="fones" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Novo Fone</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="tipo_equipamento" value="fones">
                                    <div class="mb-3">
                                        <label class="form-label">Marca*</label>
                                        <input type="text" class="form-control" name="marca" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Modelo*</label>
                                        <input type="text" class="form-control" name="modelo" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Número de Série*</label>
                                        <input type="text" class="form-control" name="numero_serie" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Patrimônio*</label>
                                        <input type="text" class="form-control" name="patrimonio" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status*</label>
                                        <select class="form-select" name="status" required>
                                            <option value="Disponivel">Disponível</option>
                                            <option value="Em uso">Em uso</option>
                                            <option value="Manutencao">Manutenção</option>
                                            <option value="Descartado">Descartado</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Cadastrar Fone
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Fones Cadastrados</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Marca</th>
                                                <th>Modelo</th>
                                                <th>Série</th>
                                                <th>Patrimônio</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fones as $fone): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($fone['marca']) ?></td>
                                                <td><?= htmlspecialchars($fone['modelo']) ?></td>
                                                <td><?= htmlspecialchars($fone['numero_serie']) ?></td>
                                                <td><?= htmlspecialchars($fone['patrimonio'] ?? 'Não informado') ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $fone['status'] === 'Disponivel' ? 'success' : 
                                                        ($fone['status'] === 'Em uso' ? 'primary' : 'warning')
                                                    ?>">
                                                        <?= htmlspecialchars($fone['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Restaura a funcionalidade de animação de troca de abas
        const triggerTabList = document.querySelectorAll('#equipamentosTab button');
        triggerTabList.forEach(function (triggerEl) {
            const tabTrigger = new bootstrap.Tab(triggerEl);

            triggerEl.addEventListener('click', function (event) {
                event.preventDefault();
                tabTrigger.show();
            });
        });
    </script>
</body>
</html>