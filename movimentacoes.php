<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'conexao.php';

// Verifica permissão
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['erro'] = "Por favor, faça login para acessar o sistema";
    header("Location: index.php");
    exit;
}

// Verifica permissão específica
if ($_SESSION['nivel_acesso'] !== 'admin' && $_SESSION['nivel_acesso'] !== 'infra') {
    $_SESSION['erro'] = "Acesso negado - Permissão insuficiente";
    header("Location: dashboard.php");
    exit;
}

// Processar movimentação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verifica se a coluna 'ativo' existe na tabela 'movimentacoes'
        $colunasTabela = $db->query("PRAGMA table_info(movimentacoes)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('ativo', $colunasTabela)) {
            // Adiciona a coluna 'ativo' se ela não existir
            $db->exec("ALTER TABLE movimentacoes ADD COLUMN ativo INTEGER DEFAULT 1");
        }

        $db->beginTransaction();

        // Validações básicas
        $requiredFields = ['colaborador_destino_id', 'equipamento_tipo', 'equipamento_id'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . str_replace('_', ' ', $field) . " é obrigatório");
            }
        }

        // Verifica se o equipamento já está associado a um colaborador
        $stmt = $db->prepare("SELECT colaborador_destino_id FROM movimentacoes WHERE equipamento_id = ? AND equipamento_tipo = ? AND ativo = 1");
        $stmt->execute([$_POST['equipamento_id'], $_POST['equipamento_tipo']]);
        $colaboradorOrigemId = $stmt->fetchColumn();

        // Desativa a movimentação anterior, se existir
        if ($colaboradorOrigemId) {
            $stmt = $db->prepare("UPDATE movimentacoes SET ativo = 0 WHERE equipamento_id = ? AND equipamento_tipo = ? AND ativo = 1");
            $stmt->execute([$_POST['equipamento_id'], $_POST['equipamento_tipo']]);
        }

        // Registra a nova movimentação
        $stmt = $db->prepare("INSERT INTO movimentacoes 
            (equipamento_tipo, equipamento_id, colaborador_origem_id, colaborador_destino_id, usuario_responsavel_id, observacoes, ativo)
            VALUES (?, ?, ?, ?, ?, ?, 1)");
        
        $stmt->execute([
            $_POST['equipamento_tipo'],
            $_POST['equipamento_id'],
            $colaboradorOrigemId,
            $_POST['colaborador_destino_id'],
            $_SESSION['usuario_id'],
            $_POST['observacoes'] ?? null
        ]);

        // Atualiza o status do equipamento para "Em uso"
        $stmt = $db->prepare("UPDATE {$_POST['equipamento_tipo']} SET status = 'Em uso' WHERE id = ?");
        $stmt->execute([$_POST['equipamento_id']]);

        $db->commit();

        $_SESSION['mensagem'] = [
            'tipo' => 'success',
            'texto' => 'Movimentação registrada com sucesso!'
        ];
    } catch (Exception $e) {
        // Verifica se há uma transação ativa antes de chamar rollBack
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['erro'] = "Erro ao registrar movimentação: " . $e->getMessage();
    }

    header("Location: movimentacoes.php");
    exit;
}

// Busca dados para os selects
$colaboradores = $db->query("SELECT id, nome FROM colaboradores WHERE status = 'Ativo' ORDER BY nome")->fetchAll();

// Busca histórico de movimentações
$movimentacoes = $db->query("
    SELECT m.*, 
           co.nome as origem_nome, 
           cd.nome as destino_nome,
           u.nome as responsavel_nome,
           e.tipo as equipamento_tipo
    FROM movimentacoes m
    LEFT JOIN colaboradores co ON co.id = m.colaborador_origem_id
    JOIN colaboradores cd ON cd.id = m.colaborador_destino_id
    JOIN usuarios u ON u.id = m.usuario_responsavel_id
    LEFT JOIN (
        SELECT id, 'notebooks' as tipo FROM notebooks
        UNION SELECT id, 'desktops' as tipo FROM desktops
        UNION SELECT id, 'celulares' as tipo FROM celulares
        UNION SELECT id, 'tablets' as tipo FROM tablets
        UNION SELECT id, 'numeros' as tipo FROM numeros
        UNION SELECT id, 'fones' as tipo FROM fones
    ) e ON e.id = m.equipamento_id
    ORDER BY m.data_movimentacao DESC
    LIMIT 50
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentações - Controle de Equipamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card-header {
            font-weight: bold;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        .badge-equipamento {
            font-size: 0.8em;
            padding: 5px 8px;
            border-radius: 4px;
        }
        .equipamento-info {
            font-size: 0.9rem;
            margin-top: 5px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        #currentEquipment {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="bi bi-arrow-left-right"></i> Movimentações</h2>
        
        <?php include 'alertas.php'; ?>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nova Movimentação</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formMovimentacao">
                            <div class="mb-3">
                                <label class="form-label">Colaborador Destino*</label>
                                <select class="form-select" name="colaborador_destino_id" id="colaboradorDestino" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($colaboradores as $colab): ?>
                                        <option value="<?= $colab['id'] ?>"><?= htmlspecialchars($colab['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="currentEquipment" class="equipamento-info d-none">
                                    <strong>Equipamento atual:</strong>
                                    <span id="currentEquipmentText"></span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tipo de Equipamento*</label>
                                <select class="form-select" name="equipamento_tipo" id="tipoEquipamento" required>
                                    <option value="">Selecione...</option>
                                    <option value="notebooks">Notebook</option>
                                    <option value="desktops">Desktop</option>
                                    <option value="celulares">Celular</option>
                                    <option value="tablets">Tablet</option>
                                    <option value="numeros">Número</option>
                                    <option value="fones">Fone</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Equipamento*</label>
                                <select class="form-select" name="equipamento_id" id="equipamentoSelect" required>
                                    <option value="">Selecione o tipo primeiro</option>
                                </select>
                                <div id="equipamentoInfo" class="equipamento-info d-none">
                                    <strong>Informações:</strong>
                                    <span id="equipamentoInfoText"></span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" name="observacoes" rows="3"></textarea>
                            </div>
                            
                            <input type="hidden" name="colaborador_origem_id" id="colaboradorOrigem">
                            <input type="hidden" name="equipamento_atual_id" id="equipamentoAtual">
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Registrar Movimentação
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Histórico Recente</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Equipamento</th>
                                        <th>Origem</th>
                                        <th>Destino</th>
                                        <th>Responsável</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($movimentacoes)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Nenhuma movimentação registrada</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($movimentacoes as $mov): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($mov['data_movimentacao'])) ?></td>
                                            <td>
                                                <span class="badge-equipamento bg-<?= 
                                                    $mov['equipamento_tipo'] === 'numeros' ? 'info' : 
                                                    ($mov['equipamento_tipo'] === 'fones' ? 'warning' : 'primary')
                                                ?>">
                                                    <?= ucfirst($mov['equipamento_tipo']) ?>
                                                </span>
                                                #<?= $mov['equipamento_id'] ?>
                                            </td>
                                            <td><?= $mov['origem_nome'] ?? 'Estoque' ?></td>
                                            <td><?= htmlspecialchars($mov['destino_nome']) ?></td>
                                            <td><?= htmlspecialchars($mov['responsavel_nome']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Carrega equipamentos disponíveis quando seleciona o tipo
        document.getElementById('tipoEquipamento').addEventListener('change', function() {
            const tipo = this.value;
            const select = document.getElementById('equipamentoSelect');
            const infoDiv = document.getElementById('equipamentoInfo');
            
            if (!tipo) {
                select.innerHTML = '<option value="">Selecione o tipo primeiro</option>';
                infoDiv.classList.add('d-none');
                return;
            }
            
            fetch(`ajax_get_equipamentos.php?tipo=${tipo}&status=Disponivel`)
                .then(response => {
                    console.log(`Requisição para tipo: ${tipo}, status: Disponivel`);
                    if (!response.ok) {
                        return response.json().then(err => {
                            console.error('Erro na resposta do servidor:', err);
                            throw new Error(err.error || 'Erro desconhecido ao carregar equipamentos');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);

                    let options = '<option value="">Selecione...</option>';
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(equip => {
                            let descricao = '';
                            let extraInfo = [];

                            if (tipo === 'numeros') {
                                descricao = `Número: ${equip.numero || equip.id}`;
                                if (equip.operadora) extraInfo.push(`Operadora: ${equip.operadora}`);
                                if (equip.plano) extraInfo.push(`Plano: ${equip.plano}`);
                            } else if (tipo === 'fones') {
                                descricao = `Fone: ${equip.modelo || equip.marca || 'ID: ' + equip.id}`;
                                if (equip.numero_serie) extraInfo.push(`Série: ${equip.numero_serie}`);
                                if (equip.patrimonio) extraInfo.push(`Patrimônio: ${equip.patrimonio}`);
                            } else {
                                descricao = `${equip.nome || equip.marca || ''} ${equip.modelo || ''}`.trim();
                                if (!descricao) descricao = `Equipamento ID: ${equip.id}`;
                                if (equip.numero_serie) extraInfo.push(`Série: ${equip.numero_serie}`);
                                if (equip.patrimonio) extraInfo.push(`Patrimônio: ${equip.patrimonio}`);
                                if (equip.imei) extraInfo.push(`IMEI: ${equip.imei}`);
                            }

                            options += `<option value="${equip.id}" data-info="${extraInfo.join(' | ')}">${descricao}</option>`;
                        });
                    } else {
                        options += '<option value="">Nenhum equipamento disponível</option>';
                    }

                    select.innerHTML = options;
                    infoDiv.classList.add('d-none');
                })
                .catch(error => {
                    console.error('Erro ao carregar equipamentos:', error);
                    select.innerHTML = '<option value="">Erro ao carregar equipamentos</option>';
                    document.getElementById('equipamentoInfoText').textContent = error.message;
                    infoDiv.classList.remove('d-none');
                });
        });

        // Mostra informações do equipamento selecionado
        document.getElementById('equipamentoSelect').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('equipamentoInfo');
            
            if (selected.value && selected.dataset.info) {
                document.getElementById('equipamentoInfoText').textContent = selected.dataset.info;
                infoDiv.classList.remove('d-none');
            } else {
                infoDiv.classList.add('d-none');
            }
        });

        // Busca informações do colaborador atual e do equipamento
        document.getElementById('colaboradorDestino').addEventListener('change', function() {
            const colaboradorId = this.value;
            const currentEquipmentDiv = document.getElementById('currentEquipment');
            const equipamentoAtualInput = document.getElementById('equipamentoAtual');
            const colaboradorOrigemInput = document.getElementById('colaboradorOrigem');
            
            if (!colaboradorId) {
                currentEquipmentDiv.classList.add('d-none');
                equipamentoAtualInput.value = '';
                colaboradorOrigemInput.value = '';
                return;
            }
            
            fetch(`ajax_get_colaborador.php?id=${colaboradorId}`)
                .then(response => {
                    if (!response.ok) {
                        console.error('Erro na resposta do servidor:', response.status, response.statusText);
                        return response.json().then(err => {
                            throw new Error(err.error || 'Erro desconhecido ao buscar colaborador');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Erro retornado pelo servidor:', data.error);
                        throw new Error(data.error);
                    }

                    equipamentoAtualInput.value = data.equipamento?.equipamento_id || '';
                    colaboradorOrigemInput.value = data.colaborador.id;

                    if (data.equipamento) {
                        const equip = data.equipamento;
                        let info = '';

                        if (equip.tipo === 'numeros') {
                            info = `Número: ${equip.numero || equip.id}`;
                            if (equip.operadora) info += ` (${equip.operadora})`;
                        } else if (equip.tipo === 'fones') {
                            info = `Fone: ${equip.modelo || equip.marca || ''}`;
                            if (equip.numero_serie) info += ` - Série: ${equip.numero_serie}`;
                        } else {
                            info = `${equip.nome || equip.marca || ''} ${equip.modelo || ''}`.trim();
                            if (equip.numero_serie) info += ` - Série: ${equip.numero_serie}`;
                            if (equip.patrimonio) info += ` - Patrimônio: ${equip.patrimonio}`;
                        }

                        document.getElementById('currentEquipmentText').textContent = info;
                        currentEquipmentDiv.classList.remove('d-none');
                    } else {
                        document.getElementById('currentEquipmentText').textContent = 'Nenhum equipamento associado';
                        currentEquipmentDiv.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar colaborador:', error.message);
                    currentEquipmentDiv.classList.add('d-none');
                    alert(error.message);
                });
        });
    </script>
</body>
</html>