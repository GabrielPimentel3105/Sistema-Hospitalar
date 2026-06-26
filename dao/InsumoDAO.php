<?php

class InsumoDAO {
    private $conn;
    private $table_name = "insumos";

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

    public function create(Insumo $insumo) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (
                        nome_insumo,
                        quantidade_estoque,
                        valor_unitario,
                        estoque_minimo
                    )
                    VALUES
                    (
                        :nome_insumo,
                        :quantidade_estoque,
                        :valor_unitario,
                        :estoque_minimo
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":nome_insumo", $insumo->__get("nome_insumo"));
            $stmt->bindValue(":quantidade_estoque", $this->inteiro($insumo->__get("quantidade_estoque")));
            $stmt->bindValue(":valor_unitario", $this->valorDecimal($insumo->__get("valor_unitario")));
            $stmt->bindValue(":estoque_minimo", $this->inteiro($insumo->__get("estoque_minimo")));

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function update(Insumo $insumo) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE " . $this->table_name . "
                    SET
                        nome_insumo = :nome_insumo,
                        quantidade_estoque = :quantidade_estoque,
                        valor_unitario = :valor_unitario,
                        estoque_minimo = :estoque_minimo
                    WHERE id_insumo = :id_insumo";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":nome_insumo", $insumo->__get("nome_insumo"));
            $stmt->bindValue(":quantidade_estoque", $this->inteiro($insumo->__get("quantidade_estoque")));
            $stmt->bindValue(":valor_unitario", $this->valorDecimal($insumo->__get("valor_unitario")));
            $stmt->bindValue(":estoque_minimo", $this->inteiro($insumo->__get("estoque_minimo")));
            $stmt->bindValue(":id_insumo", $insumo->__get("id_insumo"));

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
                    ORDER BY nome_insumo ASC";

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
                    WHERE id_insumo = :id_insumo
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_insumo", $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return null;
        }
    }

    public function delete($id) {
        if ($this->conn === null) {
            return false;
        }

        try {
            /*
             * Como o insumo pode ter histórico de uso, não apagamos fisicamente.
             * Zeramos o estoque para preservar o histórico.
             */
            $query = "UPDATE " . $this->table_name . "
                    SET quantidade_estoque = 0
                    WHERE id_insumo = :id_insumo";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_insumo", $id);

            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function countBaixoEstoque() {
        if ($this->conn === null) {
            return 0;
        }

        try {
            $query = "SELECT COUNT(*) AS total
                    FROM " . $this->table_name . "
                    WHERE quantidade_estoque <= estoque_minimo";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row['total'] ?? 0;

        } catch (PDOException $e) {
            return 0;
        }
    }
}
?>