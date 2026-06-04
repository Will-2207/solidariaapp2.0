<?php
// 1. Iniciar sesión y seguridad
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Incluir conexiones (Ajusta los paths según tu carpeta 'includes')
require_once 'includes/db.php'; // PostgreSQL (PDO)
require_once 'includes/mongodb.php'; // Tu cliente de MongoDB

// 3. Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $asunto = htmlspecialchars($_POST['asunto']);
    $mensaje = htmlspecialchars($_POST['mensaje']);
    $prioridad = $_POST['prioridad'];

    try {
        // A. Iniciar transacción en PostgreSQL (Integridad)
        $pdo->beginTransaction();

        $sql = "INSERT INTO tickets_soporte (usuario_id, asunto, prioridad, estado, created_at) 
                VALUES (:uid, :asunto, :prioridad, 'pendiente', NOW()) RETURNING id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $usuario_id,
            ':asunto' => $asunto,
            ':prioridad' => $prioridad
        ]);
        $ticket_id = $stmt->fetchColumn();

        // B. Guardar detalles en MongoDB (Flexibilidad de datos)
        $mongo_collection = $mongo_db->tickets_detalles;
        $mongo_collection->insertOne([
            'ticket_sql_id' => $ticket_id,
            'mensaje' => $mensaje,
            'fecha' => new MongoDB\BSON\UTCDateTime(),
            'usuario_id' => $usuario_id
        ]);

        // Confirmar todo
        $pdo->commit();
        $exito = "Ticket enviado correctamente. ID: " . $ticket_id;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al procesar soporte: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Soporte Técnico</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Crear Ticket de Soporte</h2>

        <?php if (isset($exito)): ?>
            <div class="alert success"><?php echo $exito; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="soporte.php" method="POST">
            <label>Asunto:</label>
            <input type="text" name="asunto" required>

            <label>Prioridad:</label>
            <select name="prioridad">
                <option value="baja">Baja</option>
                <option value="media">Media</option>
                <option value="alta">Alta</option>
            </select>

            <label>Mensaje Detallado:</label>
            <textarea name="mensaje" required rows="5"></textarea>

            <button type="submit">Enviar Ticket</button>
        </form>
        <a href="index.php">Volver al Dashboard</a>
    </div>
</body>
</html>
