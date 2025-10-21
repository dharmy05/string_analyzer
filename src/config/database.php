<?php

define ('DB_FILE',  __DIR__ . '/../../data/strings.sqlite');


// establishing connection to the database
try{
    $conn = new PDO('sqlite:' . DB_FILE);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    initDb($conn);
}catch(Exception $e){
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

function initDb($conn) { 
    // Create new table
    $conn->exec("CREATE TABLE IF NOT EXISTS strings (
        id TEXT PRIMARY KEY,
        value TEXT NOT NULL,
        length INTEGER NOT NULL,
        is_palindrome BOOLEAN NOT NULL,
        unique_characters INTEGER NOT NULL,
        word_count INTEGER NOT NULL,
        sha256_hash TEXT NOT NULL,
        character_frequency_map TEXT NOT NULL,
        created_at TEXT NOT NULL
    )");
}

