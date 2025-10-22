<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');


require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/controllers/controllers.php';
// ------------------ ROUTING LOGIC ------------------ //

$method = $_SERVER['REQUEST_METHOD'];
$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

switch ($method) {
    // case 'GET':
    //     if ($uri === '/strings/filter-by-natural-language') {
    //         handleNaturalLanguageFilter($conn);
    //     } elseif ($uri === '/strings') {
    //         handleGetAllStrings($conn);
    //     } elseif (preg_match('#^/strings/(.+)$#', $uri)) {
    //         $value = rawurldecode(substr($uri, strlen('/strings/')));
    //         handleGetString($conn, $value);
    //     }
    //     break;
    case 'POST':
        if ($uri === '/strings') {
            handleCreateString($conn);
        }
        break;


    // case 'DELETE':
    //     if (preg_match('#^/strings/(.+)$#', $uri)) {
    //         $value = rawurldecode(substr($uri, strlen('/strings/')));
    //         handleDeleteString($conn, $value);
    //     }
    //     break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}
