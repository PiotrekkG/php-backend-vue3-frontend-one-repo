<?php

// Trasa dla strony głównej - zwraca zawartość index.html z katalogu frontOutput
app()->all('/', function() {
    http_response_code(200);
    readfile(__DIR__ . '/../frontOutput/index.html');
    exit;
});

// Trasa dla wszystkich nieznanych endpointów - zwraca index.html, aby umożliwić obsługę routingu po stronie frontendu
app()->setNotFound(function() {
    http_response_code(200);
    readfile(__DIR__ . '/../frontOutput/index.html');
    exit;
});

// home route example
// app()->get('/home', function() {
//     response()->json(['message' => 'Welcome to the Home Page!']);
// });