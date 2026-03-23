# API Framework - Bezpieczna Struktura

## 🔒 Bezpieczeństwo

Aplikacja została skonfigurowana tak, aby wszystkie żądania przechodziły przez folder `/public/`, co zapobiega bezpośredniemu dostępowi do plików źródłowych.

## 📁 Struktura katalogów

```
/api/
├── public/              # Jedyny publicznie dostępny folder
│   ├── index.php       # Punkt wejścia aplikacji
│   └── .htaccess       # Konfiguracja routingu
├── myleaf/             # Framework (zabezpieczony)
│   ├── functions.php   # Router i funkcje pomocnicze
│   └── .htaccess       # Blokada dostępu
├── routes/             # Definicje tras (zabezpieczone)
│   ├── api.php         # Przykładowe trasy
│   ├── examples.php    # Przykłady z walidacją
│   └── .htaccess       # Blokada dostępu
├── config.php          # Konfiguracja (zabezpieczona)
├── mysql.php           # Połączenie z bazą (zabezpieczone)
├── index.php           # Przekierowanie do /public/
└── .htaccess           # Główne zabezpieczenia
```

## 🚀 Dostęp do API

### ✅ Prawidłowe URL-e:
- `http://localhost/php-backend-vue3-frontend-one-repo/api/public/`
- `http://localhost/php-backend-vue3-frontend-one-repo/api/public/api/users`
- `http://localhost/php-backend-vue3-frontend-one-repo/api/public/api/user/123`

### ❌ Zablokowane URL-e:
- `http://localhost/php-backend-vue3-frontend-one-repo/api/config.php` (403 Forbidden)
- `http://localhost/php-backend-vue3-frontend-one-repo/api/myleaf/functions.php` (403 Forbidden)
- `http://localhost/php-backend-vue3-frontend-one-repo/api/routes/api.php` (403 Forbidden)

## 🛡️ Zabezpieczenia

1. **Blokada plików źródłowych** - `.htaccess` blokuje dostęp do PHP, konfiguracji
2. **Blokada katalogów** - `myleaf/`, `routes/` są niedostępne z web
3. **Bezpieczne nagłówki** - X-XSS-Protection, X-Frame-Options, itd.
4. **Routing centralny** - wszystko przechodzi przez `public/index.php`

## 📝 Przykłady użycia

```php
// W pliku routes/api.php
app()->get('/api/users', function() {
    response()->json(['users' => []]);
});

app()->post('/api/user/{id}', function($id) {
    $data = request()->json();
    response()->json(['user_id' => $id, 'data' => $data]);
});
```

## 🔧 Testowanie

Sprawdź czy zabezpieczenia działają:

1. Otwórz: `http://localhost/php-backend-vue3-frontend-one-repo/api/public/` ✅
2. Spróbuj: `http://localhost/php-backend-vue3-frontend-one-repo/api/config.php` ❌ (powinno być 403)
3. Spróbuj: `http://localhost/php-backend-vue3-frontend-one-repo/api/myleaf/functions.php` ❌ (powinno być 403)