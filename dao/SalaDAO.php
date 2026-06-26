<?php

class SalaDAO {
    private $conn;
    private $table_name = "salas";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        if ($this->conn === null) return null;

        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                    ORDER BY numero_sala ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function create(Sala $sala) {
        if ($this->conn === null) return false;

        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (numero_sala, tipo_sala, status_sala) 
                    VALUES (:num, :tipo, :status)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":num", $sala->__get("numero_sala"));
            $stmt->bindValue(":tipo", $sala->__get("tipo_sala"));
            $stmt->bindValue(":status", $sala->__get("status_sala"));

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }
}
?>