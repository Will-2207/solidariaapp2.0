<?php
namespace SolidariApp;

require_once __DIR__ . '/vendor/autoload.php';

// 1. CONFIGURACIÓN
define('MONGO_URI', getenv('MONGO_URI') ?: 'mongodb+srv://cartuji7_db_user:ClaveRedSolidaria2026@cluster7.30bhnqx.mongodb.net/?retryWrites=true&w=majority&appName=Cluster7');
define('MONGO_DB', getenv('MONGO_DB') ?: 'prueba1');
define('COL_DONACIONES', 'donaciones');
define('COL_SOPORTE', 'tickets_soporte');

// 2. CONEXIÓN
function getMongoClient(): \MongoDB\Client {
    return new \MongoDB\Client(MONGO_URI, ['tls' => true], [
        'typeMap' => ['array' => 'array', 'document' => 'array', 'root' => 'array'],
    ]);
}

// 3. HELPERS
function generarUUID(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function campo(array $data, string $key): string {
    return trim(strip_tags($data[$key] ?? ''));
}

// 4. PROCESAR SOPORTE
function procesarSoporte(array $datos): array {
    $nombre = campo($datos, 'nombre_contacto');
    $email = filter_var(trim($datos['email_contacto'] ?? ''), FILTER_VALIDATE_EMAIL);
    $categoria = campo($datos, 'categoria_fallo');
    $titulo = campo($datos, 'titulo_falla');
    $descripcion = campo($datos, 'descripcion_falla');
    $token = generarUUID();

    if (!$nombre || !$email || !$categoria || !$titulo || !$descripcion) {
        return ['tipo' => 'error', 'mensaje' => 'Campos obligatorios incompletos.'];
    }

    try {
        $client = getMongoClient();
        $client->selectCollection(MONGO_DB, COL_SOPORTE)->insertOne([
            'token_uuid' => $token, 'nombre_contacto' => $nombre, 'email_contacto' => $email,
            'categoria_fallo' => $categoria, 'titulo_falla' => $titulo,
            'descripcion_falla' => $descripcion, 'created_at' => new \MongoDB\BSON\UTCDateTime(),
            'estado' => 'abierto'
        ]);

        $db = \SolidariApp\Database::getConnection();
        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO tickets_soporte (nombre_contacto, email_contacto, categoria_fallo, titulo_falla, descripcion_falla, token_uuid) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $categoria, $titulo, $descripcion, $token]);
        $db->commit();
    } catch (\Exception $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        return ['tipo' => 'error', 'mensaje' => 'Error: ' . $e->getMessage()];
    }
    return ['tipo' => 'success', 'mensaje' => 'Ticket enviado.', 'token' => $token];
}

// 5. PROCESAR DONACIÓN (Actualizado para incluir usuario_id)
function procesarDonacion(array $datos, int $usuarioId): array {
    $nombre = campo($datos, 'nombre_contacto');
    $email = filter_var(trim($datos['email_contacto'] ?? ''), FILTER_VALIDATE_EMAIL);
    $monto = (float)($datos['monto'] ?? 0);
    $token = generarUUID();

    if (!$nombre || !$email || $monto <= 0) {
        return ['tipo' => 'error', 'mensaje' => 'Datos de donación inválidos.'];
    }

    try {
        // A. PERSISTENCIA EN MONGODB
        $client = getMongoClient();
        $client->selectCollection(MONGO_DB, COL_DONACIONES)->insertOne([
            'token_uuid' => $token, 
            'usuario_id' => $usuarioId,
            'nombre_contacto' => $nombre, 
            'monto' => $monto, 
            'created_at' => new \MongoDB\BSON\UTCDateTime()
        ]);

        // B. PERSISTENCIA EN POSTGRESQL (Con relación de usuario)
        $db = \SolidariApp\Database::getConnection();
        $db->beginTransaction();
        
        $sql = "INSERT INTO donaciones (nombre_contacto, email_contacto, monto, token_uuid, usuario_id) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$nombre, $email, $monto, $token, $usuarioId]);
        
        $db->commit();
    } catch (\Exception $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        return ['tipo' => 'error', 'mensaje' => 'Error en la transacción: ' . $e->getMessage()];
    }
    
    return ['tipo' => 'success', 'mensaje' => 'Donación registrada exitosamente.', 'token' => $token];
}
