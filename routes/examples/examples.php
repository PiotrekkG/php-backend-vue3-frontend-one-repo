<?php

// Przykłady użycia funkcji request()

// Trasa demonstrująca różne sposoby pobierania danych z żądania
app()->post('/api/demo/request', function() {
    
    // Pobieranie wszystkich danych JSON
    $allData = request()->json();
    
    // Pobieranie konkretnego pola z JSON z domyślną wartością
    $name = request()->input('name', 'Unknown');
    $email = request()->input('email');
    
    // Pobieranie parametrów GET
    $page = request()->get('page', 1);
    $limit = request()->get('limit', 10);
    
    // Informacje o żądaniu
    $method = request()->method();
    $uri = request()->uri();
    
    response()->json([
        'request_info' => [
            'method' => $method,
            'uri' => $uri
        ],
        'json_data' => $allData,
        'extracted_fields' => [
            'name' => $name,
            'email' => $email
        ],
        'query_params' => [
            'page' => $page,
            'limit' => $limit
        ]
    ]);
});

// Przykład walidacji danych
app()->post('/api/users/create', function() {
    $name = request()->input('name');
    $email = request()->input('email');
    
    // Prosta walidacja
    if (empty($name) || empty($email)) {
        response()->json([
            'error' => 'Name and email are required',
            'code' => 'VALIDATION_ERROR'
        ], 400);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        response()->json([
            'error' => 'Invalid email format',
            'code' => 'INVALID_EMAIL'
        ], 400);
        return;
    }
    
    // Symulacja zapisu do bazy danych
    $userId = rand(1000, 9999);
    
    response()->json([
        'message' => 'User created successfully',
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ], 201);
});