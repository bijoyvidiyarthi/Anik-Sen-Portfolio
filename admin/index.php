<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";

use App\Auth;

if (Auth::check()) {
    require __DIR__ . "/dashboard.php";
} else {
    require __DIR__ . "/login.php";
}
