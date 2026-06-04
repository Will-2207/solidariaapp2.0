<?php
// 1. PRIMERO: Seguridad y Sesiones
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. SEGUNDO: Incluir configuraciones (BD, etc)
require_once 'includes/db.php'; // Ajusta la ruta según tu estructura real

// 3. TERCERO: Lógica de negocio (Consultas a BD)
try {
    // Ejemplo: obtener estadísticas o datos para mostrar
    // $stmt = $pdo->query("SELECT * FROM donaciones ORDER BY created_at DESC");
    // $donaciones = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

// 4. CUARTO: Vista HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Control - Proyecto Solidario</title>
    </head>
<body>
    <header>
        <h1>Dashboard</h1>
        <nav>
            <a href="logout.php">Cerrar Sesión</a>
        </nav>
    </header>

    <main>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>

        </main>
</body>
</html>
