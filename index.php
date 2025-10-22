<?php
declare(strict_types=1);
// require_once __DIR__ . '/src/routes/route.php';
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/../controllers/controllers.php';


date_default_timezone_get();
header('content-type: application/json; charset=utf-8');
// <?php 
// require_once __DIR__ . '/../config/database.php';
// require_once __DIR__ . '/../config/database.php';

//  routing based on request method
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uri = rtrim($uri, '/');

switch($method){
    case 'POST':
        if($uri === '/strings'){
            handleCreateString($conn);
        }
        break;
    case 'GET':
        if(preg_match('#^/strings/filter-by-natural-language$#', $uri)){
            handleNaturalLanguageFilter($conn);
        }
        elseif(preg_match('#^/strings$#',$uri)){
            handleGetAllStrings($conn);
        }
        elseif(preg_match('#^/strings/(.+)$#',$uri)){
            $value = rawurldecode(substr($uri, strlen('/strings/')));
            handleGetString($conn, $value);
        };
        break;
    case 'DELETE':
        if(preg_match('#^/strings/(.+)$#',$uri)){
            $value = rawurldecode(substr($uri, strlen('/strings/')));
            handleDeleteString($conn, $value);
        }
        break; 
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint Not found']);
        break;
}