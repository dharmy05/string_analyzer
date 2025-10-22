<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');


require_once __DIR__ . '/src/config/database.php';

// ------------------ ROUTING LOGIC ------------------ //

$method = $_SERVER['REQUEST_METHOD'];
$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

switch ($method) {
    case 'POST':
        if ($uri === '/strings') {
            handleCreateString($conn);
        }
        break;

    case 'GET':
        if ($uri === '/strings/filter-by-natural-language') {
            handleNaturalLanguageFilter($conn);
        } elseif ($uri === '/strings') {
            handleGetAllStrings($conn);
        } elseif (preg_match('#^/strings/(.+)$#', $uri)) {
            $value = rawurldecode(substr($uri, strlen('/strings/')));
            handleGetString($conn, $value);
        }
        break;

    case 'DELETE':
        if (preg_match('#^/strings/(.+)$#', $uri)) {
            $value = rawurldecode(substr($uri, strlen('/strings/')));
            handleDeleteString($conn, $value);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

// ------------------ FUNCTIONS BELOW ------------------ //

function handleCreateString(PDO $conn) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['value'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing "value" field']);
        exit;
    }
    if (!is_string($data['value'])) {
        http_response_code(422);
        echo json_encode(['error' => '"value" must be a string']);
        exit;
    }

    $value = $data['value'];
    $props = analyzeString($value);
    $id = $props['sha256_hash'];

    $stmt = $conn->prepare("SELECT id FROM strings WHERE id = :id");
    $stmt->execute([':id' => $id]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'String already exists']);
        exit;
    }

    $now = gmdate('Y-m-d\TH:i:s\Z');
    $stmt = $conn->prepare("
        INSERT INTO strings (id, value, length, is_palindrome, unique_characters, 
        word_count, sha256_hash, character_frequency_map, created_at)
        VALUES (:id, :value, :length, :is_palindrome, :unique_characters, :word_count,
        :sha256_hash, :character_frequency_map, :created_at)
    ");

    $stmt->execute([
        ':id' => $id,
        ':value' => $value,
        ':length' => $props['length'],
        ':is_palindrome' => $props['is_palindrome'] ? 1 : 0,
        ':unique_characters' => $props['unique_characters'],
        ':word_count' => $props['word_count'],
        ':sha256_hash' => $props['sha256_hash'],
        ':character_frequency_map' => json_encode($props['character_frequency_map']),
        ':created_at' => $now
    ]);

    http_response_code(201);
    echo json_encode([
        'id' => $id,
        'value' => $value,
        'properties' => $props,
        'created_at' => $now
    ], JSON_PRETTY_PRINT);
    exit;
}

function handleGetString($conn, $value) {
    $id = hash('sha256', $value);
    $stmt = $conn->prepare("SELECT * FROM strings WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'String not found']);
        exit;
    }

    $row['character_frequency_map'] = json_decode($row['character_frequency_map'], true);

    echo json_encode([
        'id' => $row['id'],
        'value' => $row['value'],
        'properties' => [
            'length' => (int)$row['length'],
            'is_palindrome' => (bool)$row['is_palindrome'],
            'unique_characters' => (int)$row['unique_characters'],
            'word_count' => (int)$row['word_count'],
            'sha256_hash' => $row['sha256_hash'],
            'character_frequency_map' => $row['character_frequency_map']
        ],
        'created_at' => $row['created_at']
    ], JSON_PRETTY_PRINT);
    exit;
}

function handleGetAllStrings($conn) {
    $query = "SELECT * FROM strings WHERE 1=1";
    $params = [];

    if (isset($_GET['is_palindrome'])) {
        $query .= " AND is_palindrome = :is_palindrome";
        $params[':is_palindrome'] = $_GET['is_palindrome'] ? 1 : 0;
    }

    if (isset($_GET['min_length'])) {
        $query .= " AND length >= :min_length";
        $params[':min_length'] = (int)$_GET['min_length'];
    }

    if (isset($_GET['max_length'])) {
        $query .= " AND length <= :max_length";
        $params[':max_length'] = (int)$_GET['max_length'];
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $rows, 'count' => count($rows)], JSON_PRETTY_PRINT);
    exit;
}

function handleDeleteString($conn, $value) {
    $id = hash('sha256', $value);
    $stmt = $conn->prepare("DELETE FROM strings WHERE id = :id");
    $stmt->execute([':id' => $id]);
    http_response_code(204);
    exit;
}

function handleNaturalLanguageFilter($conn) {
    if (!isset($_GET['query'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing query parameter']);
        exit;
    }
    $_GET = array_merge($_GET, parseNaturalQuery($_GET['query']));
    handleGetAllStrings($conn);
}

function analyzeString($value) {
    $len = mb_strlen($value);
    $freq = [];
    for ($i = 0; $i < $len; $i++) {
        $char = mb_substr($value, $i, 1);
        $freq[$char] = ($freq[$char] ?? 0) + 1;
    }
    $hash = hash('sha256', $value);
    $isPalindrome = preg_replace('/[^a-z0-9]/i', '', strtolower($value)) === strrev(preg_replace('/[^a-z0-9]/i', '', strtolower($value)));

    return [
        'length' => $len,
        'is_palindrome' => $isPalindrome,
        'unique_characters' => count($freq),
        'word_count' => str_word_count($value),
        'sha256_hash' => $hash,
        'character_frequency_map' => $freq
    ];
}

function parseNaturalQuery($text) {
    $filters = [];
    $text = strtolower($text);

    if (str_contains($text, 'palindrome')) $filters['is_palindrome'] = 1;
    if (preg_match('/longer than (\d+) characters/', $text, $match)) $filters['min_length'] = $match[1];

    return $filters;
}
