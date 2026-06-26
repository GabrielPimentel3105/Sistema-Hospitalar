<?php

class Leito {
    private $conn;
    private $table_name = "leitos";

    private $id_leito;
    private $numero_leito;
    private $ala;
    private $status_leito;

    public function __construct($db = null) {
        $this->conn = $db;
    }

    public function __set($atributo, $valor) {
        $this->$atributo = $valor;
    }

    public function __get($atributo) {
        return $this->$atributo;
    }

    public function getIdLeito() {
        return $this->id_leito;
    }

    public function setIdLeito($id_leito) {
        $this->id_leito = $id_leito;
    }

    public function getNumeroLeito() {
        return $this->numero_leito;
    }

    public function setNumeroLeito($numero_leito) {
        $this->numero_leito = $numero_leito;
    }

    public function getAla() {
        return $this->ala;
    }

    public function setAla($ala) {
        $this->ala = $ala;
    }

    public function getStatusLeito() {
        return $this->status_leito;
    }

    public function setStatusLeito($status_leito) {
        $this->status_leito = $status_leito;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  (numero_leito, ala, status_leito)
                  VALUES
                  (:numero_leito, :ala, :status_leito)";

        $stmt = $this->conn->prepare($query);

        $status = !empty($this->status_leito) ? $this->status_leito : "Disponível";

        $stmt->bindParam(":numero_leito", $this->numero_leito);
        $stmt->bindParam(":ala", $this->ala);
        $stmt->bindParam(":status_leito", $status);

        return $stmt->execute();
    }

    public function read() {
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

    public function readDisponiveis() {
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

    public function readOne() {
        $query = "SELECT 
                    id_leito,
                    numero_leito,
                    ala,
                    status_leito
                  FROM " . $this->table_name . "
                  WHERE id_leito = :id_leito
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_leito", $this->id_leito);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET 
                    numero_leito = :numero_leito,
                    ala = :ala,
                    status_leito = :status_leito
                  WHERE id_leito = :id_leito";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":numero_leito", $this->numero_leito);
        $stmt->bindParam(":ala", $this->ala);
        $stmt->bindParam(":status_leito", $this->status_leito);
        $stmt->bindParam(":id_leito", $this->id_leito);

        return $stmt->execute();
    }

    public function inativar() {
        $query = "UPDATE " . $this->table_name . "
                  SET status_leito = 'Inativo'
                  WHERE id_leito = :id_leito";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_leito", $this->id_leito);

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