<?php

// // Trasa dla strony głównej - zwraca zawartość index.html z katalogu frontOutput
// app()->all('/', function () {
//     http_response_code(200);
//     readfile(__DIR__ . '/../frontOutput/index.html');
//     exit;
// });

// Trasa dla wszystkich nieznanych endpointów - zwraca index.html, aby umożliwić obsługę routingu po stronie frontendu
app()->setNotFound(function ($uri, $method) {
    http_response_code(200);
    readfile(__DIR__ . '/../frontOutput/index.html');
    exit;
    // response()->json(['message' => 'Endpoint not found', 'path' => $_SERVER['REQUEST_URI'], 'uri' => $uri, 'method' => $method], 404);
});

app()->get('/info', function () {
    response()->json(['time' => time()]);
});

app()->get('/db', function () {
    $tables = db()->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    response()->json(['tables' => $tables]);
});

// home route example
// app()->get('/home', function() {
//     response()->json(['message' => 'Welcome to the Home Page!']);
// });