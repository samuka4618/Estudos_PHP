<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['erro'] = "Acesso negado";
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro'] = "Método inválido";
    header("Location: colaboradores.php");
    exit;
}

$colaborador_id = $_POST['colaborador_id'] ?? null;
$novo_status = $_POST['novo_status'] ?? null;

try {
    $db->beginTransaction();
    
    // 1. Atualiza status do colaborador
    $stmt = $db->prepare("UPDATE colaboradores SET status = ? WHERE id = ?");
    $stmt->execute([$novo_status, $colaborador_id]);
    
    // 2. Se for desligamento, libera equipamentos
    if ($novo_status === 'Desligado') {
        // Busca equipamento atual
        $stmt = $db->prepare("SELECT equipamento_atual_id FROM colaboradores WHERE id = ?");
        $stmt->execute([$colaborador_id]);
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
                $stmt->execute([$colaborador_id]);
                
                // Registra movimentação
                $stmt = $db->prepare("INSERT INTO movimentacoes 
                    (colaborador_origem_id, colaborador_destino_id, equipamento_tipo, equipamento_id, usuario_responsavel_id, observacoes)
                    VALUES (?, NULL, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $colaborador_id,
                    $tipo,
                    $equipamento_id,
                    $_SESSION['usuario_id'],
                    "Colaborador desligado - equipamento devolvido ao estoque"
                ]);
            }
        }
    }
    
    $db->commit();
    
    $_SESSION['mensagem'] = [
        'tipo' => 'success',
        'texto' => 'Status atualizado com sucesso!'
    ];
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['erro'] = "Erro ao atualizar status: " . $e->getMessage();
}

header("Location: colaboradores.php");
exit;
?>