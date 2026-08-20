<?php
header("Access-Control-Allow-Origin: *");

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');

$conexao = conectar_bd();

if (!$conexao) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro fatal: não foi possível conectar ao banco de dados."]);
    exit();
}

// XTream playlist via GET (type=m3u_plus)
$username = $_GET['username'] ?? null;
$password = $_GET['password'] ?? null;
$type     = $_GET['type'] ?? null;

if ($username && $password && $type === 'm3u_plus') {
    $cf = $_SERVER['HTTP_CF_VISITOR'] ?? '';
    if (is_string($cf) && $cf !== '') {
        $parsed = json_decode($cf, true);
        $scheme = (is_array($parsed) && ($parsed['scheme'] ?? '') === 'https') ? 'https' : 'http';
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    }
    $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    $stmt = $conexao->prepare("SELECT * FROM clientes WHERE usuario = :u AND senha = :s LIMIT 1");
    $stmt->execute([':u' => $username, ':s' => $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $adulto = (int)($user['adulto'] ?? 0);
        $stmt = $conexao->prepare("SELECT * FROM streams WHERE stream_type = 'live' " . ($adulto === 0 ? "AND is_adult = '0'" : "") . " ORDER BY name ASC");
        $stmt->execute();

        header('Content-Type: audio/x-mpegurl; charset=utf-8');
        header('Content-Disposition: attachment; filename=playlist.m3u');
        echo "#EXTM3U\r\n";

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $name = htmlspecialchars_decode($row["name"] ?? "", ENT_QUOTES);
            $streamUrl = "{$baseUrl}/live/{$username}/{$password}/{$row["id"]}.m3u8";
            echo "#EXTINF:-1,{$name}\r\n{$streamUrl}\r\n";
        }
        exit;
    }

    http_response_code(401);
    echo json_encode(["user_info" => ["auth" => 0]]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);

        $texto_procurar = $data['link_m3u'] ?? '';
        $texto_substituir = $data['nova_url'] ?? '';

        if (empty($texto_procurar)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "O campo 'Link da M3U' (Texto a Procurar) não pode estar vazio."]);
            exit();
        }

        $conexao->beginTransaction();
        $total_afetado = 0;

        // Atualiza canais e filmes (assumindo que estão na tabela 'streams')
        $sql_streams = "UPDATE streams SET stream_source = REPLACE(stream_source, ?, ?)";
        $stmt_streams = $conexao->prepare($sql_streams);
        $stmt_streams->execute([$texto_procurar, $texto_substituir]);
        $total_afetado += $stmt_streams->rowCount();

        // Atualiza filmes que podem estar em uma tabela separada 'movies'
        $sql_movies = "UPDATE movies SET stream_source = REPLACE(stream_source, ?, ?)";
        $stmt_movies = $conexao->prepare($sql_movies);
        $stmt_movies->execute([$texto_procurar, $texto_substituir]);
        $total_afetado += $stmt_movies->rowCount();
        
        // Atualiza séries que podem estar em uma tabela separada 'series_episodes'
        $sql_series = "UPDATE series_episodes SET stream_source = REPLACE(stream_source, ?, ?)";
        $stmt_series = $conexao->prepare($sql_series);
        $stmt_series->execute([$texto_procurar, $texto_substituir]);
        $total_afetado += $stmt_series->rowCount();

        $conexao->commit();

        if ($total_afetado > 0) {
            echo json_encode(["status" => "success", "message" => "Atualização concluída! " . $total_afetado . " links foram modificados."]);
        } else {
            // Importante: retorna 'success' aqui para não mostrar erro, apenas um aviso.
            echo json_encode(["status" => "success", "message" => "Operação concluída, mas nenhum link correspondente foi encontrado para alterar."]);
        }

    } catch (PDOException $e) {
        $conexao->rollBack();
        error_log('Erro na atualização em massa: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Erro de banco de dados: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método de requisição inválido."]);
}
?>