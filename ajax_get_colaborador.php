<?php
require 'conexao.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('ID do colaborador não fornecido');
    }

    $colaboradorId = (int)$_GET['id'];

    // 1. Busca informações básicas do colaborador
    $stmt = $db->prepare("SELECT id, nome, status, equipamento_atual_id FROM colaboradores WHERE id = ?");
    $stmt->execute([$colaboradorId]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$colaborador) {
        throw new Exception('Colaborador não encontrado');
    }

    $response = [
        'success' => true,
        'colaborador' => [
            'id' => $colaborador['id'],
            'nome' => $colaborador['nome'],
            'status' => $colaborador['status'],
            'equipamento_atual_id' => $colaborador['equipamento_atual_id'],
            'equipamento_tipo' => null,
            'equipamento_info' => null
        ]
    ];

    // 2. Se o colaborador tem equipamento atual, busca informações detalhadas
    if (!empty($colaborador['equipamento_atual_id'])) {
        // Verifica em qual tabela está o equipamento
        $tipos = ['notebooks', 'desktops', 'celulares', 'tablets', 'numeros', 'fones'];
        
        foreach ($tipos as $tipo) {
            // Consulta genérica para verificar existência
            $stmt = $db->prepare("SELECT id FROM $tipo WHERE id = ? LIMIT 1");
            $stmt->execute([$colaborador['equipamento_atual_id']]);
            
            if ($stmt->fetch()) {
                $response['colaborador']['equipamento_tipo'] = $tipo;
                
                // Consulta específica para obter detalhes do equipamento
                $fields = [
                    'notebooks' => 'id, marca, modelo, numero_serie, patrimonio',
                    'desktops' => 'id, marca, modelo, numero_serie, patrimonio',
                    'celulares' => 'id, marca, modelo, numero_serie, imei',
                    'tablets' => 'id, marca, modelo, numero_serie, imei',
                    'numeros' => 'id, numero, operadora, plano',
                    'fones' => 'id, marca, modelo, numero_serie'
                ];
                
                $query = "SELECT " . $fields[$tipo] . " FROM $tipo WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$colaborador['equipamento_atual_id']]);
                $equipamento = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($equipamento) {
                    $response['colaborador']['equipamento_info'] = $equipamento;
                }
                
                break;
            }
        }
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no banco de dados',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}