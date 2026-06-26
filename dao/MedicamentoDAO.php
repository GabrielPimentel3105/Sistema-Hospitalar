<?php

class MedicamentoDAO {
    private $conn;
    private $table_name = "medicamentos";

    public function __construct($db) {
        $this->conn = $db;
    }

    private function valorDecimal($valor) {
        if ($valor === null || $valor === '') {
            return 0.00;
        }

        $valor = str_replace(',', '.', $valor);
        return (float) $valor;
    }

    private function inteiro($valor) {
        if ($valor === null || $valor === '') {
            return 0;
        }

        return (int) $valor;
    }

    public function create(Medicamento $medicamento) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (
                        nome_medicamento,
                        contraindicacoes,
                        interacoes_medicamentosas,
                        valor_unitario,
                        quantidade_estoque
                    )
                    VALUES
                    (
                        :nome_medicamento,
                        :contraindicacoes,
                        :interacoes_medicamentosas,
                        :valor_unitario,
                        :quantidade_estoque
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":nome_medicamento", $medicamento->__get("nome_medicamento"));
            $stmt->bindValue(":contraindicacoes", $medicamento->__get("contraindicacoes"));
            $stmt->bindValue(":interacoes_medicamentosas", $medicamento->__get("interacoes_medicamentosas"));
            $stmt->bindValue(":valor_unitario", $this->valorDecimal($medicamento->__get("valor_unitario")));
            $stmt->bindValue(":quantidade_estoque", $this->inteiro($medicamento->__get("quantidade_estoque")));

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function read() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT *
                    FROM " . $this->table_name . "
                    ORDER BY nome_medicamento ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch (PDOException $e) {
            return null;
        }
    }

    public function readOne($id) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT *
                    FROM " . $this->table_name . "
                    WHERE id_medicamento = :id_medicamento
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_medicamento", $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return null;
        }
    }

    public function update(Medicamento $medicamento) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE " . $this->table_name . "
                    SET
                        nome_medicamento = :nome_medicamento,
                        contraindicacoes = :contraindicacoes,
                        interacoes_medicamentosas = :interacoes_medicamentosas,
                        valor_unitario = :valor_unitario,
                        quantidade_estoque = :quantidade_estoque
                    WHERE id_medicamento = :id_medicamento";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":nome_medicamento", $medicamento->__get("nome_medicamento"));
            $stmt->bindValue(":contraindicacoes", $medicamento->__get("contraindicacoes"));
            $stmt->bindValue(":interacoes_medicamentosas", $medicamento->__get("interacoes_medicamentosas"));
            $stmt->bindValue(":valor_unitario", $this->valorDecimal($medicamento->__get("valor_unitario")));
            $stmt->bindValue(":quantidade_estoque", $this->inteiro($medicamento->__get("quantidade_estoque")));
            $stmt->bindValue(":id_medicamento", $medicamento->__get("id_medicamento"));

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete($id) {
        if ($this->conn === null) {
            return false;
        }

        try {
            /*
             * Medicamento pode estar vinculado a prescrições.
             * Por isso, não removemos fisicamente se houver uso.
             * Neste caso, apenas zeramos o estoque.
             */
            $query = "UPDATE " . $this->table_name . "
                    SET quantidade_estoque = 0
                    WHERE id_medicamento = :id_medicamento";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_medicamento", $id);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }
}
?>