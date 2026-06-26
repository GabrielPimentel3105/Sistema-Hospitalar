<?php

require_once __DIR__ . '/../model/Leito.php';

class LeitoDAO {
    private $conn;
    private $table_name = "leitos";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function cadastrar(Leito $leito) {
        $query = "INSERT INTO " . $this->table_name . "
                  (numero_leito, ala, status_leito)
                  VALUES
                  (:numero_leito, :ala, :status_leito)";

        $stmt = $this->conn->prepare($query);

        $numero_leito = $leito->__get("numero_leito");
        $ala = $leito->__get("ala");
        $status_leito = $leito->__get("status_leito");

        $stmt->bindParam(":numero_leito", $numero_leito);
        $stmt->bindParam(":ala", $ala);
        $stmt->bindParam(":status_leito", $status_leito);

        return $stmt->execute();
    }

    public function listar() {
        $query = "SELECT 
                    id_leito,
                    numero_leito,
                    ala,
                    status_leito
                  FROM " . $this->table_name . "
                  ORDER BY ala ASC, numero_leito ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function listarDisponiveis() {
        $query = "SELECT 
                    id_leito,
                    numero_leito,
                    ala,
                    status_leito
                  FROM " . $this->table_name . "
                  WHERE status_leito = 'Disponível'
                  ORDER BY ala ASC, numero_leito ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function buscarPorId($id_leito) {
        $query = "SELECT 
                    id_leito,
                    numero_leito,
                    ala,
                    status_leito
                  FROM " . $this->table_name . "
                  WHERE id_leito = :id_leito
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_leito", $id_leito);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function atualizar(Leito $leito) {
        $query = "UPDATE " . $this->table_name . "
                  SET 
                    numero_leito = :numero_leito,
                    ala = :ala,
                    status_leito = :status_leito
                  WHERE id_leito = :id_leito";

        $stmt = $this->conn->prepare($query);

        $id_leito = $leito->__get("id_leito");
        $numero_leito = $leito->__get("numero_leito");
        $ala = $leito->__get("ala");
        $status_leito = $leito->__get("status_leito");

        $stmt->bindParam(":id_leito", $id_leito);
        $stmt->bindParam(":numero_leito", $numero_leito);
        $stmt->bindParam(":ala", $ala);
        $stmt->bindParam(":status_leito", $status_leito);

        return $stmt->execute();
    }

    public function inativar($id_leito) {
        $query = "UPDATE " . $this->table_name . "
                  SET status_leito = 'Inativo'
                  WHERE id_leito = :id_leito";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_leito", $id_leito);

        return $stmt->execute();
    }

    public function alterarStatus($id_leito, $status_leito) {
        $query = "UPDATE " . $this->table_name . "
                  SET status_leito = :status_leito
                  WHERE id_leito = :id_leito";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status_leito", $status_leito);
        $stmt->bindParam(":id_leito", $id_leito);

        return $stmt->execute();
    }

    public function countPorStatus($status_leito) {
        $query = "SELECT COUNT(*) AS total
                  FROM " . $this->table_name . "
                  WHERE status_leito = :status_leito";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status_leito", $status_leito);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ? (int)$resultado["total"] : 0;
    }
}