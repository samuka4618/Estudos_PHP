<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-pc-display-horizontal me-2"></i> Controle de Equipamentos
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" 
                       href="dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </li>
                
                <?php if (isset($_SESSION['nivel_acesso']) && ($_SESSION['nivel_acesso'] === 'admin' || $_SESSION['nivel_acesso'] === 'infra')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'equipamentos.php' ? 'active' : '' ?>" 
                       href="equipamentos.php">
                        <i class="bi bi-pc me-1"></i> Equipamentos
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'colaboradores.php' ? 'active' : '' ?>" 
                       href="colaboradores.php">
                        <i class="bi bi-people me-1"></i> Colaboradores
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'movimentacoes.php' ? 'active' : '' ?>" 
                       href="movimentacoes.php">
                        <i class="bi bi-arrow-left-right me-1"></i> Movimentações
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text">
                            <i class="bi bi-person-badge me-2"></i>
                            <?= isset($_SESSION['nivel_acesso']) ? ucfirst($_SESSION['nivel_acesso']) : 'N/A' ?>
                        </span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Sair
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>