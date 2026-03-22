<?php

function rejectUnlogged() {
    if (!Auth::check()) {
        response()->json(['error' => 'UNAUTHORIZED_ACCESS', 'message' => 'You must be logged in to access this resource'], 403);
        exit;
    }
}