<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'conexao.php';

// Verifica login - redireciona se não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['erro'] = "Por favor, faça login para acessar o sistema";
    header("Location: index.php");
    exit;
}

// Buscar estatísticas
try {
    $stats = [
        'notebooks' => $db->query("SELECT COUNT(*) FROM notebooks")->fetchColumn(),
        'desktops' => $db->query("SELECT COUNT(*) FROM desktops")->fetchColumn(),
        'celulares' => $db->query("SELECT COUNT(*) FROM celulares")->fetchColumn(),
        'tablets' => $db->query("SELECT COUNT(*) FROM tablets")->fetchColumn(),
        'colaboradores' => $db->query("SELECT COUNT(*) FROM colaboradores WHERE status = 'Ativo'")->fetchColumn()
    ];

    // Últimos equipamentos cadastrados (5 de cada tipo)
    $ultimos = [];
    $tipos = ['notebooks', 'desktops', 'celulares', 'tablets', 'fones'];
    foreach ($tipos as $tipo) {
        $ultimos[$tipo] = $db->query("SELECT * FROM $tipo ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    }

    $contagem_notebooks = $db->query("SELECT COUNT(*) as total FROM notebooks")->fetch(PDO::FETCH_ASSOC)['total'];
    $contagem_desktops = $db->query("SELECT COUNT(*) as total FROM desktops")->fetch(PDO::FETCH_ASSOC)['total'];
    $contagem_celulares = $db->query("SELECT COUNT(*) as total FROM celulares")->fetch(PDO::FETCH_ASSOC)['total'];
    $contagem_tablets = $db->query("SELECT COUNT(*) as total FROM tablets")->fetch(PDO::FETCH_ASSOC)['total'];
    $contagem_numeros = $db->query("SELECT COUNT(*) as total FROM numeros")->fetch(PDO::FETCH_ASSOC)['total'];
    $contagem_fones = $db->query("SELECT COUNT(*) as total FROM fones")->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro ao carregar dados: " . $e->getMessage();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Controle de Equipamentos</title>
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
        /* Estilo para todos os cards */
        .dashboard-card {
            border-radius: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .dashboard-card .card-body {
            padding: 1.5rem;
            text-align: center;
        }
        .dashboard-card .card-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .dashboard-card .display-6 {
            font-size: 2.5rem;
            font-weight: 600;
        }
        
        /* Cores sutis para os 4 primeiros cards */
        .card-notebooks {
            background-color: #e3f2fd;
            border-left: 4px solid #2196F3;
        }
        .card-desktops {
            background-color: #e3f2fd;
            border-left: 4px solid #4CAF50;
        }
        .card-celulares {
            background-color: #e3f2fd;
            border-left: 4px solid #FF9800;
        }
        .card-tablets {
            background-color: #e3f2fd;
            border-left: 4px solid #9C27B0;
        }
        .card-numeros {
            background-color: #e3f2fd;
            border-left: 4px solid #FF5722;
        }
        .card-fones {
            background-color: #e3f2fd;
            border-left: 4px solid #795548;
        }
        .card-colaboradores {
            background-color: #e3f2fd;
            border-left: 4px solid #3F51B5;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
        
        <?php include 'alertas.php'; ?>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <!-- Primeiros 4 cards com estilo moderno e cores sutis -->
            <div class="col-md-3 mb-3">
                <div class="dashboard-card card-notebooks">
                    <div class="card-body">
                        <h5 class="card-title">Notebooks</h5>
                        <p class="card-text display-6"><?= $stats['notebooks'] ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card card-desktops">
                    <div class="card-body">
                        <h5 class="card-title">Desktops</h5>
                        <p class="card-text display-6"><?= $stats['desktops'] ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card card-celulares">
                    <div class="card-body">
                        <h5 class="card-title">Celulares</h5>
                        <p class="card-text display-6"><?= $stats['celulares'] ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card card-tablets">
                    <div class="card-body">
                        <h5 class="card-title">Tablets</h5>
                        <p class="card-text display-6"><?= $stats['tablets'] ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Cards adicionais com cores vibrantes -->
            <div class="col-md-3 mb-3">
                <div class="dashboard-card card-numeros">
                    <div class="card-body">
                        <h5 class="card-title">Números</h5>
                        <p class="card-text display-6"><?= $contagem_numeros ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card card-fones">
                    <div class="card-body">
                        <h5 class="card-title">Fones</h5>
                        <p class="card-text display-6"><?= $contagem_fones ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card card-colaboradores">
                    <div class="card-body">
                        <h5 class="card-title">Colaboradores</h5>
                        <p class="card-text display-6"><?= $stats['colaboradores'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimos Cadastros -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Últimos Notebooks</h5>
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
                                    <?php foreach ($ultimos['notebooks'] as $notebook): ?>
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
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Últimos Desktops</h5>
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
                                    <?php foreach ($ultimos['desktops'] as $desktop): ?>
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
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Últimos Celulares</h5>
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
                                        <th>IMEI</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimos['celulares'] as $celular): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($celular['nome'] ?? 'Não informado') ?></td>
                                        <td><?= htmlspecialchars($celular['marca']) ?></td>
                                        <td><?= htmlspecialchars($celular['modelo']) ?></td>
                                        <td><?= htmlspecialchars($celular['numero_serie']) ?></td>
                                        <td><?= htmlspecialchars($celular['imei_1']) ?></td>
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
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Últimos Tablets</h5>
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
                                        <th>IMEI</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimos['tablets'] as $tablet): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tablet['nome'] ?? 'Não informado') ?></td>
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
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Últimos Fones</h5>
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
                                    <?php foreach ($ultimos['fones'] as $fone): ?>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>