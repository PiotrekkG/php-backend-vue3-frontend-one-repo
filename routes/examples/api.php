<?php

// Przykłady tras używające app() i response()

// Podstawowa trasa GET
app()->get('/', function() {
    response()->json([
        'message' => 'Welcome to the API',
        'version' => '1.0.0'
    ]);
});

// Trasa z parametrem
app()->get('/api/user/{id}', function($id) {
    response()->json([
        'user_id' => $id,
        'name' => 'User ' . $id
    ]);
});

// Trasa GET z wieloma parametrami
app()->get('/api/user/{id}/posts/{post_id}', function($id, $post_id) {
    response()->json([
        'user_id' => $id,
        'post_id' => $post_id,
        'title' => 'Post title for user ' . $id
    ]);
});

// Trasa POST z danymi JSON
app()->post('/api/users', function() {
    $data = request()->json();
    
    response()->json([
        'message' => 'User created successfully',
        'data' => $data
    ], 201);
});

// Trasa PUT z parametrem i danymi
app()->put('/api/user/{id}', function($id) {
    $data = request()->json();
    
    response()->json([
        'message' => 'User updated successfully',
        'user_id' => $id,
        'updated_data' => $data
    ]);
});

// Trasa DELETE
app()->delete('/api/user/{id}', function($id) {
    response()->json([
        'message' => 'User deleted successfully',
        'deleted_user_id' => $id
    ]);
});

// Trasa z różnymi typami odpowiedzi
app()->get('/api/info', function() {
    // Możesz użyć różnych metod response
    response()
        ->header('X-API-Version', '1.0')
        ->json([
            'status' => 'ok',
            'timestamp' => time()
        ]);
});

// Przykład użycia group() - grupuje trasy z prefiksem /api
app()->group('/api', function() {

    // Ta trasa będzie dostępna pod /api/error
    app()->get('/error', function() {
        response()->json([
            'error' => 'Something went wrong',
            'code' => 500
        ], 500);
    });

    // Ta trasa będzie dostępna pod /api/health
    app()->get('/health', function() {
        response()->text('OK');
    });
    
    // Ta trasa będzie dostępna pod /api/status
    app()->get('/status', function() {
        response()->json([
            'status' => 'running',
            'timestamp' => time(),
            'memory_usage' => memory_get_usage(true)
        ]);
    });

});

// === PRZYKŁADY UŻYCIA REGEXÓW W TRASACH ===

// Trasa tylko dla liczb - (\d+) zamiast {id}
app()->get('/user/(\d+)', function($userId) {
    response()->json([
        'message' => 'User with numeric ID',
        'user_id' => (int)$userId,
        'type' => 'numeric'
    ]);
});

// Trasa dla UUID - precyzyjny regex
app()->get('/order/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})', function($orderId) {
    response()->json([
        'message' => 'Order with UUID',
        'order_id' => $orderId,
        'type' => 'uuid'
    ]);
});

// Trasa dla slug-ów (małe litery, cyfry, myślniki)
app()->get('/post/([a-z0-9-]+)', function($slug) {
    response()->json([
        'message' => 'Post by slug',
        'slug' => $slug,
        'type' => 'slug'
    ]);
});

// Kombinacja regex i prostych parametrów
app()->get('/category/(\d+)/product/{name}', function($categoryId, $productName) {
    response()->json([
        'message' => 'Product in category',
        'category_id' => (int)$categoryId,  // z regex (\d+)
        'product_name' => $productName,     // z prostego {name}
        'type' => 'mixed'
    ]);
});

// Zaawansowany regex - opcjonalne parametry z ?
app()->get('/api/version/(\d+)\.(\d+)(?:\.(\d+))?', function($major, $minor, $patch = null) {
    response()->json([
        'message' => 'API Version',
        'version' => [
            'major' => (int)$major,
            'minor' => (int)$minor,
            'patch' => $patch ? (int)$patch : 0
        ]
    ]);
});