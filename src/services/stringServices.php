<?php
function analyzeString($value){
    $length = mb_strlen($value);
    $trimmed = trim($value);
    $wordCount = $trimmed === '' ? 0 : count(preg_split('/\s+/', $trimmed));

    // character frequency map
    $freq = [];
    for($i = 0; $i < $length; $i++){
        $char = mb_substr($value, $i, 1);
        $freq[$char] = ($freq[$char] ?? 0) + 1;
    }

    // unique characters
    $uniqueCharacters = count($freq);

    // SHA256 hash
    $hash = hash('sha256', $value);
    // palindrome check
    $normalized = mb_strtolower($value);
    $normalized = preg_replace('/[^a-z0-9]/i', '', $normalized);
    $isPalindrome = $normalized === strrev($normalized);

    return [
        'length' => $length,
        'is_palindrome' => $isPalindrome,
        'unique_characters' => $uniqueCharacters,
        'word_count' => $wordCount,
        'sha256_hash' => $hash,
        'character_frequency_map' => $freq
    ];
}