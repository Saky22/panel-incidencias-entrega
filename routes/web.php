<?php

use Illuminate\Support\Facades\Route;

// Las rutas de cada bounded context se registran desde su ServiceProvider (src/*/UI/Http/routes.php).
Route::redirect('/', '/incidents');
