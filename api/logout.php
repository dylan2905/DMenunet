<?php
// Inicia la sesión para poder acceder a las variables de sesión.
session_start();

// Destruye todas las variables de la sesión.
$_SESSION = array();

// Si se desea destruir la cookie de sesión, también es necesario eliminar la cookie.
// Nota: ¡Esto destruirá la sesión, y no la hará útil de nuevo!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruye la sesión.
session_destroy();

// Redirige al usuario a la página de inicio de sesión.
header("Location: ../login.html");
exit;
?>