<?php
namespace SolidariApp;

class AuthManager {
    public static function registrarUsuario(string $nombre, string $email, string $password): bool {
        $db = Database::getConnection();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)");
        return $stmt->execute([$nombre, $email, $hash]);
    }

    public static function verificarLogin(string $email, string $password): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user; // Retorna el usuario completo (incluye el ID)
        }
        return null;
    }
}
