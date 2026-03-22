<?php

// Główny router aplikacji - obsługuje wszystkie żądania

// Ścieżka do katalogu głównego aplikacji
$_appRoot = __DIR__;

// Ładowanie plików konfiguracyjnych i zależności
require_once $_appRoot . '/config/config.php';

// Ładowanie naszych funkcji pomocniczych
$functions = glob($_appRoot . '/helpers/*.php');
foreach ($functions as $function) {
    require_once $function;
}

// Ładowanie naszego routera
require_once $_appRoot . '/myleaf/myleaf.php';

// Ładowanie wszystkich plików tras
$routes = glob($_appRoot . '/routes/*.php');
foreach ($routes as $route) {
    require_once $route;
}

db()->autoConnect(); // Inicjalizacja połączenia z bazą danych

// session_id('mysessionid'); // Ustawienie niestandardowego ID sesji (opcjonalne)
session_start(); // Inicjalizacja sesji

app()->setBasePath(BASE_PATH ?? '/'); // Ustawienie ścieżki bazowej aplikacji

// Uruchomienie routera
app()->run();
