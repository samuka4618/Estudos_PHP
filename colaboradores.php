<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'conexao.php';

// Verifica permissão
if (!isset($_SESSION['usuario_id']) || ($_SESSION['nivel_acesso'] !== 'admin' && $_SESSION['nivel_acesso'] !== 'infra')) {
    $_SESSION['erro'] = "Acesso negado";
    header("Location: index.php");
    exit;
}

// Verifica e adiciona a coluna equipamento_atual_id, se necessário
try {
    $colunaExiste = $db->query("PRAGMA table_info(colaboradores)")->fetchAll(PDO::FETCH_ASSOC);
    $colunaEncontrada = false;

    foreach ($colunaExiste as $coluna) {
        if ($coluna['name'] === 'equipamento_atual_id') {
            $colunaEncontrada = true;
            break;
        }
    }

    if (!$colunaEncontrada) {
        $db->exec("ALTER TABLE colaboradores ADD COLUMN equipamento_atual_id INTEGER NULL");
    }
} catch (PDOException $e) {
    die("Erro ao verificar ou adicionar coluna: " . $e->getMessage());
}

// Processar formulário de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    try {
        // Verifica se o ID está disponível
        do {
            $novoId = rand(1, 999999); // Gera um ID aleatório
            $stmt = $db->prepare("SELECT COUNT(*) FROM colaboradores WHERE id = ?");
            $stmt->execute([$novoId]);
            $idDisponivel = $stmt->fetchColumn() == 0;
        } while (!$idDisponivel);

        $stmt = $db->prepare("INSERT INTO colaboradores 
            (id, nome, cpf, setor, email, status)
            VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $novoId,
            $_POST['nome'],
            $_POST['cpf'],
            $_POST['setor'],
            $_POST['email'],
            $_POST['status']
        ]);
        
        $_SESSION['mensagem'] = [
            'tipo' => 'success',
            'texto' => 'Colaborador cadastrado com sucesso!'
        ];
        
        header("Location: colaboradores.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao cadastrar colaborador: " . $e->getMessage();
    }
}

// Processar atualização de status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['colaborador_id'])) {
    try {
        $db->beginTransaction();
        
        // 1. Atualiza status do colaborador
        $stmt = $db->prepare("UPDATE colaboradores SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['novo_status'], $_POST['colaborador_id']]);
        
        // 2. Se for desligamento, libera equipamentos
        if ($_POST['novo_status'] === 'Desligado') {
            // Busca equipamento atual
            $stmt = $db->prepare("SELECT equipamento_atual_id FROM colaboradores WHERE id = ?");
            $stmt->execute([$_POST['colaborador_id']]);
            $equipamento_id = $stmt->fetchColumn();
            
            if ($equipamento_id) {
                // Descobre o tipo do equipamento
                $tipo = null;
                $tipos = ['notebooks', 'desktops', 'celulares', 'tablets'];
                
                foreach ($tipos as $t) {
                    $stmt = $db->prepare("SELECT 1 FROM $t WHERE id = ?");
                    $stmt->execute([$equipamento_id]);
                    if ($stmt->fetch()) {
                        $tipo = $t;
                        break;
                    }
                }
                
                if ($tipo) {
                    // Atualiza status do equipamento
                    $stmt = $db->prepare("UPDATE $tipo SET status = 'Disponivel' WHERE id = ?");
                    $stmt->execute([$equipamento_id]);
                    
                    // Remove vínculo do colaborador
                    $stmt = $db->prepare("UPDATE colaboradores SET equipamento_atual_id = NULL WHERE id = ?");
                    $stmt->execute([$_POST['colaborador_id']]);
                    
                    // Registra movimentação
                    if (tabelaExiste($db, 'vinculos')) {
                        $stmt = $db->prepare("INSERT INTO vinculos 
                            (colaborador_id, equipamento_tipo, equipamento_id, data_entrega, status, observacoes)
                            VALUES (?, ?, ?, DATE('now'), 'Devolvido', ?)");
                        
                        $stmt->execute([
                            $_POST['colaborador_id'],
                            $tipo,
                            $equipamento_id,
                            "Colaborador desligado - equipamento devolvido ao estoque"
                        ]);
                    }
                }
            }
        }
        
        $db->commit();
        
        $_SESSION['mensagem'] = [
            'tipo' => 'success',
            'texto' => 'Status do colaborador atualizado com sucesso!'
        ];
        
        header("Location: colaboradores.php");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['erro'] = "Erro ao atualizar status: " . $e->getMessage();
    }
}

// Listar colaboradores com informações de equipamentos
$colaboradores = $db->query("
    SELECT c.*, 
           n.marca as notebook_marca, n.modelo as notebook_modelo, n.numero_serie as notebook_serie,
           d.marca as desktop_marca, d.modelo as desktop_modelo, d.numero_serie as desktop_serie,
           cel.marca as celular_marca, cel.modelo as celular_modelo, cel.numero_serie as celular_serie,
           t.marca as tablet_marca, t.modelo as tablet_modelo, t.numero_serie as tablet_serie
    FROM colaboradores c
    LEFT JOIN notebooks n ON n.id = c.equipamento_atual_id AND n.status = 'Em uso'
    LEFT JOIN desktops d ON d.id = c.equipamento_atual_id AND d.status = 'Em uso'
    LEFT JOIN celulares cel ON cel.id = c.equipamento_atual_id AND cel.status = 'Em uso'
    LEFT JOIN tablets t ON t.id = c.equipamento_atual_id AND t.status = 'Em uso'
    ORDER BY c.nome
")->fetchAll(PDO::FETCH_ASSOC);

function tabelaExiste($db, $tabela) {
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$tabela'");
    return $result->fetch() !== false;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colaboradores - Controle de Equipamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .equipamento-info {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .badge-status {
            min-width: 80px;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="bi bi-people"></i> Colaboradores</h2>
        
        <?php include 'alertas.php'; ?>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-plus"></i> Novo Colaborador</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nome Completo*</label>
                                <input type="text" class="form-control" name="nome" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CPF*</label>
                                <input type="text" class="form-control" name="cpf" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Setor*</label>
                                <select class="form-select" name="setor" required>
                                    <option value="">Selecione...</option>
                                    <option value="TI">TI</option>
                                    <option value="Comercial">Comercial</option>
                                    <option value="Financeiro">Financeiro</option>
                                    <option value="RH">RH</option>
                                    <option value="Operações">Operações</option>
                                    <option value="Controladoria">Controladoria</option>
                                    <option value="Diretoria">Diretoria</option>
                                    <option value="Qualidade">Qualidade</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Pricing">Pricing</option>
                                    <option value="Supply chain">Supply chain</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-mail</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status*</label>
                                <select class="form-select" name="status" required>
                                    <option value="Ativo">Ativo</option>
                                    <option value="Desligado">Desligado</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Cadastrar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> Atualizar Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Colaborador*</label>
                                        <select class="form-select" name="colaborador_id" required>
                                            <option value="">Selecione...</option>
                                            <?php foreach ($colaboradores as $colab): ?>
                                                <option value="<?= $colab['id'] ?>"><?= htmlspecialchars($colab['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Novo Status*</label>
                                        <select class="form-select" name="novo_status" required>
                                            <option value="Ativo">Ativo</option>
                                            <option value="Desligado">Desligado</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info text-white">
                                <i class="bi bi-check-circle"></i> Atualizar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Lista de Colaboradores</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Nome</th>
                                <th>Setor</th>
                                <th>Status</th>
                                <th>Equipamentos</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($colaboradores)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Nenhum colaborador cadastrado</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($colaboradores as $colab): ?>
                                <tr>
                                    <td><?= htmlspecialchars($colab['nome']) ?></td>
                                    <td><?= htmlspecialchars($colab['setor']) ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= $colab['status'] === 'Ativo' ? 'success' : 'secondary' ?> badge-status">
                                            <?= htmlspecialchars($colab['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($colab['notebook_marca']): ?>
                                            <div class="equipamento-info">
                                                <i class="bi bi-laptop"></i> <?= htmlspecialchars($colab['notebook_marca']) ?> <?= htmlspecialchars($colab['notebook_modelo']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($colab['desktop_marca']): ?>
                                            <div class="equipamento-info">
                                                <i class="bi bi-pc-display"></i> <?= htmlspecialchars($colab['desktop_marca']) ?> <?= htmlspecialchars($colab['desktop_modelo']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($colab['celular_marca']): ?>
                                            <div class="equipamento-info">
                                                <i class="bi bi-phone"></i> <?= htmlspecialchars($colab['celular_marca']) ?> <?= htmlspecialchars($colab['celular_modelo']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($colab['tablet_marca']): ?>
                                            <div class="equipamento-info">
                                                <i class="bi bi-tablet"></i> <?= htmlspecialchars($colab['tablet_marca']) ?> <?= htmlspecialchars($colab['tablet_modelo']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!$colab['notebook_marca'] && !$colab['desktop_marca'] && !$colab['celular_marca'] && !$colab['tablet_marca']): ?>
                                            <span class="text-muted">Nenhum equipamento</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="movimentacoes.php?colaborador_id=<?= $colab['id'] ?>" class="btn btn-sm btn-outline-primary" title="Vincular equipamento">
                                            <i class="bi bi-plus-circle"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>