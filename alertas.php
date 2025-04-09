<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<?php if (isset($_SESSION['mensagem'])): ?>
<div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?> alert-dismissible fade show mt-3">
    <i class="bi <?= 
        $_SESSION['mensagem']['tipo'] === 'success' ? 'bi-check-circle-fill' : 
        ($_SESSION['mensagem']['tipo'] === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill')
    ?> me-2"></i>
    <?= $_SESSION['mensagem']['texto'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['mensagem']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['erro'])): ?>
<div class="alert alert-danger alert-dismissible fade show mt-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <?= $_SESSION['erro'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['erro']); ?>
<?php endif; ?>