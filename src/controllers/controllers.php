<?php
require_once __DIR__ . '/../services/stringService.php';
require_once __DIR__ . '/../config/database.php';
function handleCreateString($conn){
    // Read the raw jSON from the request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // validate the input
    if(!isset($data['value'])){
        http_response_code(400);
        echo json_encode (['error' => 'Missing "value" field']);
        exit;
    };
    if(!is_string($data['value'])){
        http_response_code(422);
        echo json_encode (['error' => '"value" must be a string']);
        exit;
    };

    $value = $data['value'];

    // Analyze the string
    $props = analyzeString($value);
    $id = $props['sha256_hash'];

    // checking if already exists
    $stmt = $conn->prepare("SELECT * FROM strings WHERE id = :id");
    $stmt -> execute([':id' => $id]);
    if($stmt->fetch()){
        http_response_code(409);
        echo json_encode(['error' => 'String already exists']);
        exit;
    }

    // SAVE TO DB
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $stmt = $conn->prepare("INSERT INTO strings (id, value, length, is_palindrome, 
    unique_characters, word_count, sha256_hash, character_frequency_map, created_at)
    VALUES (:id, :value, :length, :is_palindrome, :unique_characters, :word_count, :sha256_hash,
     :character_frequency_map, :created_at)");

    $stmt->execute([
        ':id' => $id,
        ':value' => $value,
        ':length' => $props['length'],
        ':is_palindrome' => $props['is_palindrome'] ? 1 : 0,
        ':unique_characters' => $props['unique_characters'],
        ':word_count' => $props['word_count'],
        ':sha256_hash' => $props['sha256_hash'],
        ':character_frequency_map' => json_encode($props['character_frequency_map'], JSON_UNESCAPED_UNICODE),
        ':created_at' => $now
    ]);

    // return JSON response (201 Created)
    http_response_code(201);
    echo json_encode([
        'id' => $id,
        'value' => $value,
        'properties' => $props,
        'created_at' => $now
    ], JSON_PRETTY_PRINT);
    exit;
}


function handleGetString($conn, $value){
$id = hash('sha256', $value);

$stmt = $conn -> prepare("SELECT * FROM strings WHERE id = :id");
$stmt -> execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row){
    http_response_code(404);
    echo json_encode(['error' => 'String not found']);
    exit;
}
// format character_frequency_map back to associative array
$row['character_frequency_map'] = json_decode($row['character_frequency_map'], true);

// send response
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
};


// Function to handle getALLStrings
function handleGetAllStrings($conn){
    $query = "SELECT * FROM strings WHERE 1=1";
    $params = [];
    $filters = [];

    if(isset($_GET['is_palindrome'])){
        $filters['is_palindrome']  = filter_var($_GET['is_palindrome'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if($filters['is_palindrome'] === null){
            http_response_code(400);
            echo json_encode(['error' => 'Invalid is_palindrome value']);
            exit;
        }
        $query .= " AND is_palindrome = :is_palindrome";
        $params[':is_palindrome'] = $filters['is_palindrome'] ? 1 : 0;
    }
    if(isset($_GET['min_length'])){
        $filters['min_length'] = (int)$_GET['min_length'];
        $query .= " AND length >= :min_length";
        $params[':min_length'] = $filters['min_length'];
    }
    if(isset($_GET['max_length'])){
        $filters['max_length'] = (int)$_GET['max_length'];
        $query .= " AND length <= :max_length";
        $params[':max_length'] = $filters['max_length'];
    }
    if(isset($_GET['word_count'])){
        $filters['word_count'] = (int)$_GET['word_count'];
        $query .= " AND word_count = :word_count";
        $params[':word_count'] = $filters['word_count'];
    }
    if(isset($_GET['contains_character'])){
        $char = $_GET['contains_character'];
        if(mb_strlen($char) !== 1){
            http_response_code(400);
            echo json_encode(['error' => 'contains_character must be a single character']);
            exit;
        }
        $filters['contains_character'] = $char;
        $query .= " AND value LIKE :contains_character";
        $params[':contains_character'] = '%' . $char . '%';
    }
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach($rows as $row){
        $data[] = [
            'id' => $row['id'],
            'value' => $row['value'],
            'properties' => [
                'length' => (int)$row['length'],
                'is_palindrome' => (bool)$row['is_palindrome'],
                'unique_characters' => (int)$row['unique_characters'],
                'word_count' => (int)$row['word_count'],
                'sha256_hash' => $row['sha256_hash'],
                'character_frequency_map' => json_decode($row['character_frequency_map'], true)
            ],
            'created_at' => $row['created_at']
        ];
    }
    echo json_encode([
        'data'=>$data,
        'count' => count($data),
        'filters_applied' => $filters
    ], JSON_PRETTY_PRINT);
}

function handleDeleteString($conn, $value) {
    if (!$value || !is_string($value)) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid or missing string value']);
        exit;
    }

    // Compute the SHA-256 hash of the string
    $id = hash('sha256', $value);

    // Check if the string exists
    $stmt = $conn->prepare("SELECT 1 FROM strings WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if (!$stmt->fetch()) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'String not found']);
        exit;
    }

    // Delete the string from the database
    $stmt = $conn->prepare("DELETE FROM strings WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // 204 = No Content (don't send a body)
    http_response_code(204);
    exit;
}



function handleNaturalLanguageFilter($conn) {
    if (!isset($_GET['query']) || trim($_GET['query']) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing query parameter']);
        exit;
    }

    $queryText = strtolower(trim($_GET['query']));
    $filters = [];

    // Parse natural language heuristically
    if (str_contains($queryText, 'single word')) {
        $filters['word_count'] = 1;
    }

    if (str_contains($queryText, 'palindrome') || str_contains($queryText, 'palindromic')) {
        $filters['is_palindrome'] = true;
    }

    if (preg_match('/longer than (\d+) characters/', $queryText, $match)) {
        $filters['min_length'] = (int)$match[1] + 1;
    }

    if (preg_match('/containing the letter (\w)/', $queryText, $match)) {
        $filters['contains_character'] = $match[1];
    }

    if (str_contains($queryText, 'first vowel')) {
        $filters['contains_character'] = 'a';
    }

    // If nothing understood
    if (empty($filters)) {
        http_response_code(400);
        echo json_encode(['error' => 'Unable to parse natural language query']);
        exit;
    }

    // Use same logic as handleGetAllStrings but with parsed filters
    $_GET = array_merge($_GET, $filters);
    handleGetAllStrings($conn);
}