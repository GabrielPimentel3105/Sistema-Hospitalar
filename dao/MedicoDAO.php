<?php

class MedicoDAO {
    private $conn;
    private $table_name = "medicos";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create(Medico $medico) {
        if ($this->conn === null) return false;

        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (nome, crm, especialidade, telefone, email) 
                    VALUES (:nome, :crm, :especialidade, :telefone, :email)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":nome", $medico->__get("nome"));
            $stmt->bindValue(":crm", $medico->__get("crm"));
            $stmt->bindValue(":especialidade", $medico->__get("especialidade"));
            $stmt->bindValue(":telefone", $medico->__get("telefone"));
            $stmt->bindValue(":email", $medico->__get("email"));

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function read() {
        if ($this->conn === null) return null;

        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                    ORDER BY nome ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function readOne($id) {
        if ($this->conn === null) return null;

        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                    WHERE id_medico = :id 
                    LIMIT 0,1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            return null;
        }
    }

    public function update(Medico $medico) {
        if ($this->conn === null) return false;

        try {
            $query = "UPDATE " . $this->table_name . " 
                    SET nome = :nome, 
                        crm = :crm, 
                        especialidade = :especialidade, 
                        telefone = :telefone, 
                        email = :email 
                    WHERE id_medico = :id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":nome", $medico->__get("nome"));
            $stmt->bindValue(":crm", $medico->__get("crm"));
            $stmt->bindValue(":especialidade", $medico->__get("especialidade"));
            $stmt->bindValue(":telefone", $medico->__get("telefone"));
            $stmt->bindValue(":email", $medico->__get("email"));
            $stmt->bindValue(":id", $medico->__get("id_medico"));

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function delete($id) {
        if ($this->conn === null) return false;

        try {
            $query = "DELETE FROM " . $this->table_name . " 
                    WHERE id_medico = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id", $id);

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }
}
?>