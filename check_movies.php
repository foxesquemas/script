<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php';
$conexao = conectar_bd();

echo "=== First 5 movies ===\n";
$stmt = $conexao->prepare("SELECT id, name, link, tipo_link FROM streams WHERE stream_type = 'movie' LIMIT 5");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "id={$row['id']} name='{$row['name']}' tipo='{$row['tipo_link']}' link='" . substr($row['link'] ?? '', 0, 100) . "'\n";
}
