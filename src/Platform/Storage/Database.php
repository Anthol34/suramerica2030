<?php
// Usamos namespaces para que el Autoloader del index.php lo encuentre mágicamente
namespace Platform\Storage;

class Database {
    private $host = 'localhost';
    private $db_name = 'suramerica2030_db';
    private $username = 'root'; 
    private $password = 'supermario@001'; // ¡Ojo! Pon aquí la contraseña real de tu AppServ
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Se fuerza el charset a utf8mb4 (Mejor estándar actual)
            $this->conn = new \PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            // Fetch_Assoc por defecto para traer arreglos limpios y no objetos duplicados
            $this->conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch(\PDOException $exception) {
            die("Error crítico de almacenamiento: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>