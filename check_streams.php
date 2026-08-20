<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php';
$conexao = conectar_bd();

echo "=== Streams (first 20 live) ===\n";
$stmt = $conexao->prepare("SELECT id, name, stream_type, link, tipo_link FROM streams WHERE stream_type = 'live' LIMIT 20");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "id={$row['id']} name='{$row['name']}' tipo_link='{$row['tipo_link']}' link='" . substr($row['link'] ?? '', 0, 80) . "'\n";
}

echo "\n=== Stream count by type ===\n";
$stmt = $conexao->query("SELECT stream_type, COUNT(*) as c FROM streams GROUP BY stream_type");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['stream_type']}: {$row['c']}\n";
}
