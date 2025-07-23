<?php
// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirect_with_error($message, $fallback = 'dashboard.php', $preserve_input = false)
{

    $_SESSION['error_message'] = $message;

    if ($preserve_input) {
        $_SESSION['old_input'] = $_POST;
    }

    $target = $_POST['return_to'] ?? $_SERVER['HTTP_REFERER'] ?? $fallback;
    header("Location: $target");
    exit;
}


