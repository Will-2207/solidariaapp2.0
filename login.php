<?php
session_start();
require_once 'src/AuthManager.php';
require_once 'src/Database.php';

use SolidariApp\AuthManager;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pass  = $_POST['pass'] ?? '';

    $usuario = AuthManager::verificarLogin($email, $pass);

    if ($usuario) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Credenciales incorrectas.";
    }
}
?>
<form method="POST">
    <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
    <input type="email" name="email" required placeholder="Email">
    <input type="password" name="pass" required placeholder="Contraseña">
    <button type="submit">Entrar</button>
</form>
