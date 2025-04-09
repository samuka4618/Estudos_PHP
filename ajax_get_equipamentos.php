<?php
require 'conexao.php';

header('Content-Type: application/json');

try {
    // Verifica se o tipo de equipamento é válido
    if (!isset($_GET['tipo']) || !in_array($_GET['tipo'], ['notebooks', 'desktops', 'celulares', 'tablets', 'numeros', 'fones'])) {
        throw new Exception('Tipo de equipamento inválido');
    }

    $tipo = $_GET['tipo'];
    $status = $_GET['status'] ?? 'Disponivel';

    // Obtém as colunas disponíveis na tabela
    $colunasTabela = $db->query("PRAGMA table_info($tipo)")->fetchAll(PDO::FETCH_COLUMN, 1);

    // Define os campos dinamicamente com base nas colunas disponíveis
    $campos = array_intersect(
        ['id', 'nome', 'marca', 'modelo', 'numero_serie', 'patrimonio', 'imei', 'numero', 'operadora', 'plano', 'status'],
        $colunasTabela
    );

    if (empty($campos)) {
        throw new Exception("Nenhuma coluna válida encontrada na tabela '$tipo'");
    }

    $query = "SELECT " . implode(', ', $campos) . " FROM $tipo WHERE status = ? ORDER BY ";
    $query .= in_array('numero', $colunasTabela) ? 'numero' : (in_array('marca', $colunasTabela) ? 'marca, modelo' : 'id');

    $stmt = $db->prepare($query);
    $stmt->execute([$status]);
    $equipamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retorna uma lista vazia se nenhum equipamento for encontrado
    echo json_encode($equipamentos);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}