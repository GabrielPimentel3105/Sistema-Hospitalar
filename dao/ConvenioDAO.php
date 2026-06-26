<?php

class ConvenioDAO {
    private $conn;
    private $table_name = "convenios";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create(Convenio $convenio) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (
                        nome_convenio,
                        telefone,
                        procedimentos_autorizados,
                        status_convenio
                    ) 
                    VALUES 
                    (
                        :nome_convenio,
                        :telefone,
                        :procedimentos_autorizados,
                        :status_convenio
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":nome_convenio", $convenio->__get("nome_convenio"));
            $stmt->bindValue(":telefone", $convenio->__get("telefone"));
            $stmt->bindValue(":procedimentos_autorizados", $convenio->__get("procedimentos_autorizados"));
            $stmt->bindValue(":status_convenio", $convenio->__get("status_convenio") ?: "Ativo");

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function read() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                    ORDER BY 
                        CASE 
                            WHEN status_convenio = 'Ativo' THEN 1 
                            ELSE 2 
                        END,
                        nome_convenio ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function readAtivos() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                    WHERE status_convenio = 'Ativo'
                    ORDER BY nome_convenio ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function readOne($id) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                    WHERE id_convenio = :id_convenio
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_convenio", $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            return null;
        }
    }

    public function update(Convenio $convenio) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE " . $this->table_name . " 
                    SET 
                        nome_convenio = :nome_convenio,
                        telefone = :telefone,
                        procedimentos_autorizados = :procedimentos_autorizados,
                        status_convenio = :status_convenio
                    WHERE id_convenio = :id_convenio";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":nome_convenio", $convenio->__get("nome_convenio"));
            $stmt->bindValue(":telefone", $convenio->__get("telefone"));
            $stmt->bindValue(":procedimentos_autorizados", $convenio->__get("procedimentos_autorizados"));
            $stmt->bindValue(":status_convenio", $convenio->__get("status_convenio") ?: "Ativo");
            $stmt->bindValue(":id_convenio", $convenio->__get("id_convenio"));

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function delete($id) {
        if ($this->conn === null) {
            return false;
        }

        try {
            /*
             * Não apagamos fisicamente o convênio, porque ele pode estar vinculado
             * a pacientes, consultas e faturamentos. A exclusão vira inativação lógica.
             */
            $query = "UPDATE " . $this->table_name . " 
                    SET status_convenio = 'Inativo'
                    WHERE id_convenio = :id_convenio";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_convenio", $id);

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }
}
?>