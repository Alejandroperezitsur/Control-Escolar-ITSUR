    // Soporte para paginación en getAll
    public function getAll($page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->table} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Soporte para contar todos los alumnos
    public function count() {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        return (int)$this->db->query($sql)->fetchColumn();
    }
<?php
require_once __DIR__ . '/Model.php';

class Alumno extends Model {
    protected $table = 'alumnos';
    
    private $allowedFields = [
        'matricula', 'nombre', 'apellido', 
        'email', 'fecha_nac', 'foto', 'password', 'activo'
    ];

    public function __construct() {
        parent::__construct();
    }

    public function create($data) {
        $data = $this->sanitize($data);
        $data = $this->filterAllowedFields($data);
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($data['foto']) && $data['foto']['error'] === 0) {
            $data['foto'] = $this->processFoto($data['foto']);
        }

        if (!$this->validate($data)) {
            return false;
        }

        return parent::create($data);
    }

    public function update($id, $data) {
        $data = $this->sanitize($data);
        $data = $this->filterAllowedFields($data);
        if (isset($data['password'])) {
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                unset($data['password']);
            }
        }
        
        if (isset($data['foto']) && $data['foto']['error'] === 0) {
            $this->deleteFoto($id);
            $data['foto'] = $this->processFoto($data['foto']);
        }

        if (!$this->validate($data, $id)) {
            return false;
        }

        return parent::update($id, $data);
    }

    public function findByMatricula($matricula) {
        $sql = "SELECT * FROM {$this->table} WHERE matricula = :matricula LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':matricula', $matricula);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function delete($id) {
        $this->deleteFoto($id);
        return parent::delete($id);
    }

    private function validate($data, $id = null) {
        $errors = [];

        // Validar matrícula
        if (empty($data['matricula'])) {
            $errors[] = "La matrícula es requerida";
        } elseif (!preg_match('/^[SICMQEA][0-9]{8}$/', $data['matricula'])) {
            $errors[] = "Formato de matrícula inválido";
        }

        // Validar email único
        $sql = "SELECT id FROM alumnos WHERE email = :email";
        if ($id) {
            $sql .= " AND id != :id";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $data['email']);
        if ($id) {
            $stmt->bindValue(':id', $id);
        }
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $errors[] = "El email ya está registrado";
        }

        // Validar fecha de nacimiento
        if (!empty($data['fecha_nac'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha_nac']);
            if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha_nac']) {
                $errors[] = "Formato de fecha inválido";
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            return false;
        }

        return true;
    }

    private function filterAllowedFields($data) {
        return array_intersect_key($data, array_flip($this->allowedFields));
    }

    private function processFoto($foto) {
        $config = require __DIR__ . '/../../config/config.php';
        $maxSize = (int)$config['security']['upload_max_size'];
        $allowedExts = array_map('strtolower', (array)$config['security']['allowed_extensions']);

        if (!isset($foto['tmp_name']) || !is_uploaded_file($foto['tmp_name'])) {
            throw new Exception('Archivo de subida inválido');
        }

        if ((int)$foto['size'] > $maxSize) {
            throw new Exception('El archivo excede el tamaño permitido');
        }

        // Validar tipo MIME real
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? finfo_file($finfo, $foto['tmp_name']) : null;
        if ($finfo) { finfo_close($finfo); }
        $validMimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!$mime || !in_array($mime, $validMimes, true)) {
            throw new Exception('El archivo no es una imagen válida');
        }

        // Validar contenido como imagen
        if (!@getimagesize($foto['tmp_name'])) {
            throw new Exception('Contenido de imagen inválido');
        }

        $fileInfo = pathinfo($foto['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');
        if (!in_array($extension, $allowedExts, true)) {
            throw new Exception('Extensión de archivo no permitida');
        }

        // Asegurar directorio destino
        $uploadDir = __DIR__ . '/../../uploads/fotos/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                throw new Exception('No se pudo crear el directorio de subida');
            }
        }

        // Nombre aleatorio robusto
        $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;
        $uploadPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($foto['tmp_name'], $uploadPath)) {
            throw new Exception('Error al subir el archivo');
        }

        return $newFileName;
    }

    private function deleteFoto($id) {
        $alumno = $this->find($id);
        if ($alumno && $alumno['foto']) {
            $fotoPath = __DIR__ . '/../../uploads/fotos/' . $alumno['foto'];
            if (file_exists($fotoPath)) {
                unlink($fotoPath);
            }
        }
    }

    public function getWithCalificaciones($id, $profesorId = null) {
        $baseSql = "SELECT a.*, c.parcial1, c.parcial2, c.final, c.promedio,
                           g.id AS grupo_id, g.nombre AS grupo_nombre, g.ciclo AS grupo_ciclo,
                           m.nombre AS materia, m.clave AS materia_clave
                    FROM alumnos a
                    LEFT JOIN calificaciones c ON a.id = c.alumno_id
                    LEFT JOIN grupos g ON c.grupo_id = g.id";

        // Aplicar filtro por profesor solo si se proporciona
        if (!is_null($profesorId)) {
            $baseSql .= " AND g.profesor_id = :profesorId";
        }

        $baseSql .= " LEFT JOIN materias m ON g.materia_id = m.id
                      WHERE a.id = :id";

        $stmt = $this->db->prepare($baseSql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        if (!is_null($profesorId)) {
            $stmt->bindValue(':profesorId', (int)$profesorId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function search($term, $page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        $like = "%$term%";

        $sql = "SELECT * FROM alumnos 
                WHERE nombre LIKE :term 
                OR apellido LIKE :term 
                OR matricula LIKE :term 
                OR email LIKE :term 
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':term', $like);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countSearch($term) {
        $like = "%$term%";
        $sql = "SELECT COUNT(*) FROM alumnos 
                WHERE nombre LIKE :term 
                OR apellido LIKE :term 
                OR matricula LIKE :term 
                OR email LIKE :term";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':term', $like);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getAllByEstado($estado, $page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM alumnos WHERE activo = :estado LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':estado', (int)$estado);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByEstado($estado) {
        $sql = "SELECT COUNT(*) FROM alumnos WHERE activo = :estado";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':estado', (int)$estado);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function searchByEstado($term, $estado, $page = 1, $limit = 10) {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        $like = "%$term%";

        $sql = "SELECT * FROM alumnos 
                WHERE activo = :estado AND (
                    nombre LIKE :term OR apellido LIKE :term OR matricula LIKE :term OR email LIKE :term
                )
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':estado', (int)$estado);
        $stmt->bindValue(':term', $like);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countSearchByEstado($term, $estado) {
        $like = "%$term%";
        $sql = "SELECT COUNT(*) FROM alumnos 
                WHERE activo = :estado AND (
                    nombre LIKE :term OR apellido LIKE :term OR matricula LIKE :term OR email LIKE :term
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':estado', (int)$estado);
        $stmt->bindValue(':term', $like);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}