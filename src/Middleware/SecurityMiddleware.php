<?php

namespace App\Middleware;

use PDO;

class SecurityMiddleware {
    
    public static function verifyOwnership(PDO $pdo, int $resourceId, string $resourceType, int $userId, string $userRole): void {
        if ($userRole === 'admin') {
            return;
        }

        switch ($resourceType) {
            case 'alumno':
                $stmt = $pdo->prepare("SELECT id FROM alumnos WHERE id = :id AND user_id = :uid AND deleted_at IS NULL");
                $stmt->execute(['id' => $resourceId, 'uid' => $userId]);
                if (!$stmt->fetchColumn()) {
                    throw new \Exception("Acceso denegado: No puedes ver datos de otro alumno.", 403);
                }
                break;
            
            case 'grupo':
                if ($userRole !== 'docente') {
                    throw new \Exception("Acceso denegado: Rol no autorizado.", 403);
                }
                $stmt = $pdo->prepare("SELECT id FROM grupos WHERE id = :gid AND docente_id = :tid AND deleted_at IS NULL");
                $stmt->execute(['gid' => $resourceId, 'tid' => $userId]);
                if (!$stmt->fetchColumn()) {
                    throw new \Exception("Acceso denegado: Este grupo no te pertenece.", 403);
                }
                break;
                
            case 'calificacion':
                if ($userRole === 'docente') {
                    $stmt = $pdo->prepare("SELECT c.id FROM calificaciones c JOIN grupos g ON c.grupo_id = g.id WHERE c.id = :cid AND g.docente_id = :tid AND c.deleted_at IS NULL");
                    $stmt->execute(['cid' => $resourceId, 'tid' => $userId]);
                    if (!$stmt->fetchColumn()) {
                        throw new \Exception("Acceso denegado: No puedes modificar esta calificacion.", 403);
                    }
                } elseif ($userRole === 'alumno') {
                    $stmt = $pdo->prepare("SELECT c.id FROM calificaciones c JOIN alumnos a ON c.alumno_id = a.id WHERE c.id = :cid AND a.user_id = :uid AND c.deleted_at IS NULL");
                    $stmt->execute(['cid' => $resourceId, 'uid' => $userId]);
                    if (!$stmt->fetchColumn()) {
                        throw new \Exception("Acceso denegado: Esta calificacion no te pertenece.", 403);
                    }
                }
                break;
        }
    }

    public static function regenerateSessionContext(string $reason = 'security_event'): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $oldData = $_SESSION ?? [];
            session_regenerate_id(true);
            $_SESSION = $oldData;
            $_SESSION['last_regeneration'] = time();
            $_SESSION['regeneration_reason'] = $reason;
        }
    }

    public static function safePasswordVerify(string $password, ?string $hash): bool {
        $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        if ($hash === null || $hash === '') {
            password_verify($password, $dummyHash);
            return false;
        }
        return password_verify($password, $hash);
    }

    public static function whitelistData(array $input, array $allowedFields): array {
        $output = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $output[$field] = $input[$field];
            }
        }
        return $output;
    }
}
