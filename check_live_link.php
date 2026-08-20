<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php';
$conexao = conectar_bd();

$testIds = [644798, 644799, 644800, 646474, 646282];
foreach ($testIds as $id) {
    $stmt = $conexao->prepare("SELECT id, name, link, tipo_link FROM streams WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "id={$row['id']} name='{$row['name']}' tipo='{$row['tipo_link']}'\n";
        echo "link=" . ($row['link'] ?? 'NULL') . "\n\n";
    }
}
