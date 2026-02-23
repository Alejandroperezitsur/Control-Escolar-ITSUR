<?php
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        // Cargar la configuración. Usamos include en vez de require_once
        // porque require_once puede devolver true si ya se incluyó antes,
        // lo que rompe la asignación esperada (devuelve bool en lugar del array).
        $config = include __DIR__ . '/config.php';

        if ($config === false || !isset($config['db']) || !is_array($config['db'])) {
            die("Error: no se pudo cargar la configuración de base de datos (config/config.php). Revisa que el archivo exista y retorne un array.");
        }

        // Valores por defecto seguros para entorno local
        $hostPort = $config['db']['host'] ?? '127.0.0.1:3306';
        $dbname = $config['db']['name'] ?? 'control_escolar';
        $user = $config['db']['user'] ?? 'root';
        $pass = $config['db']['pass'] ?? '';

        // Separar host y puerto si se proporcionaron juntos (ej. 127.0.0.1:3306)
        $hostOnly = $hostPort;
        $port = null;
        if (strpos($hostPort, ':') !== false) {
            list($hostOnly, $port) = explode(':', $hostPort, 2);
        }

        try {
            // Crear conexión temporal (sin DB) para crear la base si no existe
            $tempDsn = 'mysql:host=' . $hostOnly;
            if ($port) {
                $tempDsn .= ';port=' . $port;
            }

            $tempConn = new PDO($tempDsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $tempConn->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace("`", "", $dbname) . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            // Ahora conectar a la base de datos específica
            $dsn = 'mysql:host=' . $hostOnly;
            if ($port) {
                $dsn .= ';port=' . $port;
            }
            $dsn .= ';dbname=' . $dbname . ';charset=utf8mb4';

            $this->conn = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            try {
                $chk = $this->conn->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'nombre'");
                $chk->execute([':db' => $dbname]);
                $exists = (int)$chk->fetchColumn();
                if ($exists === 0) {
                    $this->conn->exec("ALTER TABLE usuarios ADD COLUMN nombre VARCHAR(100) DEFAULT NULL AFTER email");
                }
            } catch (Throwable $e) {}

            // alumnos.activo
            try {
                $chk = $this->conn->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'alumnos' AND COLUMN_NAME = 'activo'");
                $chk->execute([':db' => $dbname]);
                $exists = (int)$chk->fetchColumn();
                if ($exists === 0) {
                    $this->conn->exec("ALTER TABLE alumnos ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER password");
                }
            } catch (Throwable $e) {}
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
