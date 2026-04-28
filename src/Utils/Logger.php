<?php
namespace App\Utils;

/**
 * Logger de Producción con Sanitización de Datos Sensibles
 * 
 * Características:
 * - Campos SENSIBLES nunca logueados: password, token, csrf_token, curp, rfc
 * - MASKING automático: emails (j***@dominio.com), matrículas (****5678)
 * - Whitelist de campos seguros
 * - Niveles: info, warning, error, critical
 */
class Logger
{
    /**
     * Lista de campos que NUNCA se deben loguear (se reemplazan por [REDACTED])
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_hash',
        'passwd',
        'pwd',
        'token',
        'csrf_token',
        'api_key',
        'apikey',
        'secret',
        'curp',
        'rfc',
        'ssn',
        'credit_card',
        'tarjeta'
    ];

    /**
     * Patrón para masking de emails
     */
    private const EMAIL_MASK_PATTERN = '/^([a-zA-Z0-9._%+-]{1})[a-zA-Z0-9._%+-]*(@.*)$/';
    
    /**
     * Patrón para masking de matrículas/IDs
     */
    private const MATRICULA_MASK_PATTERN = '/^[a-zA-Z0-9]{4,}$/';

    /**
     * Ruta del archivo de logs
     */
    private static function path(): string
    {
        $base = __DIR__ . '/../../logs/app.log';
        // Crear directorio si no existe
        $dir = dirname($base);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $base;
    }

    /**
     * Sanitiza datos sensibles antes de loguear
     * 
     * @param mixed $data Datos a sanitizar
     * @return mixed Datos sanitizados
     */
    private static function sanitizeData($data)
    {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $keyLower = strtolower($key);
                
                // Verificar si el campo es sensible
                if (in_array($keyLower, self::SENSITIVE_FIELDS, true)) {
                    $sanitized[$key] = '[REDACTED]';
                } elseif (is_array($value)) {
                    // Recursividad para arrays anidados
                    $sanitized[$key] = self::sanitizeData($value);
                } elseif (is_string($value)) {
                    // Aplicar masking según tipo de dato
                    $sanitized[$key] = self::maskStringValue($keyLower, $value);
                } else {
                    $sanitized[$key] = $value;
                }
            }
            return $sanitized;
        }
        
        return $data;
    }

    /**
     * Aplica masking a strings según su tipo
     * 
     * @param string $key Nombre del campo
     * @param string $value Valor a enmascarar
     * @return string Valor enmascarado
     */
    private static function maskStringValue(string $key, string $value): string
    {
        // Email masking
        if ($key === 'email' || strpos($key, 'correo') !== false) {
            return preg_replace(self::EMAIL_MASK_PATTERN, '$1***$2', $value) ?: $value;
        }
        
        // Matrícula masking
        if ($key === 'matricula' || $key === 'student_id' || strpos($key, 'matricula') !== false) {
            if (strlen($value) >= 4 && preg_match(self::MATRICULA_MASK_PATTERN, $value)) {
                return '****' . substr($value, -4);
            }
        }
        
        // Teléfono masking
        if ($key === 'telefono' || $key === 'phone' || strpos($key, 'tel') !== false) {
            if (strlen($value) >= 4) {
                return str_repeat('*', strlen($value) - 4) . substr($value, -4);
            }
        }
        
        // Nombre masking (parcial)
        if (strpos($key, 'nombre') !== false || strpos($key, 'apellido') !== false) {
            if (strlen($value) >= 3) {
                return substr($value, 0, 1) . str_repeat('*', strlen($value) - 1);
            }
        }
        
        return $value;
    }

    /**
     * Log de nivel INFO
     * 
     * @param string $message Mensaje del log
     * @param array $context Contexto adicional
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Log de nivel WARNING
     * 
     * @param string $message Mensaje del log
     * @param array $context Contexto adicional
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Log de nivel ERROR
     * 
     * @param string $message Mensaje del log
     * @param array $context Contexto adicional
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Log de nivel CRITICAL
     * 
     * @param string $message Mensaje del log
     * @param array $context Contexto adicional
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }

    /**
     * Método principal de logging
     * 
     * @param string $level Nivel de log
     * @param string $message Mensaje del log
     * @param array $context Contexto adicional
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        // Sanitizar contexto antes de loguear
        $sanitizedContext = self::sanitizeData($context);
        
        $entry = [
            'timestamp' => date('c'),
            'level' => $level,
            'user_id' => $_SESSION['user_id'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'path' => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'message' => $message,
            'context' => $sanitizedContext,
        ];
        
        // Convertir a JSON de forma segura
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
        
        // Escribir en archivo con lock exclusivo
        @file_put_contents(self::path(), $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Limpia logs antiguos (más de N días)
     * 
     * @param int $days Días de retención
     * @return int Número de líneas eliminadas
     */
    public static function rotateLogs(int $days = 30): int
    {
        $logPath = self::path();
        
        if (!file_exists($logPath)) {
            return 0;
        }
        
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $keptLines = [];
        $removedCount = 0;
        
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            
            if ($entry && isset($entry['timestamp'])) {
                $logTime = strtotime($entry['timestamp']);
                
                if ($logTime >= $cutoffTime) {
                    $keptLines[] = $line;
                } else {
                    $removedCount++;
                }
            } else {
                // Mantener líneas que no se puedan parsear
                $keptLines[] = $line;
            }
        }
        
        // Reescribir archivo solo si se eliminaron líneas
        if ($removedCount > 0) {
            file_put_contents($logPath, implode(PHP_EOL, $keptLines) . PHP_EOL, LOCK_EX);
        }
        
        return $removedCount;
    }

    /**
     * Obtiene estadísticas de logs
     * 
     * @return array Estadísticas
     */
    public static function getStats(): array
    {
        $logPath = self::path();
        
        if (!file_exists($logPath)) {
            return [
                'total_lines' => 0,
                'by_level' => [],
                'file_size' => 0
            ];
        }
        
        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats = [
            'total_lines' => count($lines),
            'by_level' => [
                'INFO' => 0,
                'WARNING' => 0,
                'ERROR' => 0,
                'CRITICAL' => 0
            ],
            'file_size' => filesize($logPath)
        ];
        
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            
            if ($entry && isset($entry['level'])) {
                $level = $entry['level'];
                if (isset($stats['by_level'][$level])) {
                    $stats['by_level'][$level]++;
                }
            }
        }
        
        return $stats;
    }
}