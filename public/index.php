<?php

// Ten plik jest dostępny tylko dla statycznych plików w public/
// Routing jest obsługiwany przez główny router.php

http_response_code(403);
echo json_encode([
    'error' => 'Direct access to public/index.php is not allowed',
    'message' => 'This endpoint is reserved for static files'
]);
exit;