<?php

class UsoInsumoDAO {
    private $conn;
    private $table_name = "uso_insumos";

    public function __construct($db) {
        $this->conn = $db;
    }

    private function inteiro($valor) {
        if ($valor === null || $valor === '') {
            return 0;
        }

        return (int) $valor;
    }

    public function create(UsoInsumo $usoInsumo) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $idInsumo = $usoInsumo->__get("id_insumo");
            $quantidade = $this->inteiro($usoInsumo->__get("quantidade_utilizada"));

            if (empty($idInsumo) || $quantidade <= 0) {
                $this->conn->rollBack();
                return false;
            }

            $queryEstoque = "SELECT quantidade_estoque
                            FROM insumos
                            WHERE id_insumo = :id_insumo
                            LIMIT 1";

            $stmtEstoque = $this->conn->prepare($queryEstoque);
            $stmtEstoque->bindValue(":id_insumo", $idInsumo);
            $stmtEstoque->execute();

            $insumo = $stmtEstoque->fetch(PDO::FETCH_ASSOC);

            if (!$insumo || (int)$insumo['quantidade_estoque'] < $quantidade) {
                $this->conn->rollBack();
                return false;
            }

            $query = "INSERT INTO " . $this->table_name . "
                    (
                        quantidade_utilizada,
                        data_uso,
                        id_leito,
                        id_insumo,
                        id_internacao
                    )
                    VALUES
                    (
                        :quantidade_utilizada,
                        NOW(),
                        :id_leito,
                        :id_insumo,
                        :id_internacao
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":quantidade_utilizada", $quantidade);
            $stmt->bindValue(":id_leito", $usoInsumo->__get("id_leito"));
            $stmt->bindValue(":id_insumo", $idInsumo);
            $stmt->bindValue(":id_internacao", !empty($usoInsumo->__get("id_internacao")) ? $usoInsumo->__get("id_internacao") : null);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $queryBaixa = "UPDATE insumos
                        SET quantidade_estoque = quantidade_estoque - :quantidade
                        WHERE id_insumo = :id_insumo";

            $stmtBaixa = $this->conn->prepare($queryBaixa);
            $stmtBaixa->bindValue(":quantidade", $quantidade);
            $stmtBaixa->bindValue(":id_insumo", $idInsumo);

            if (!$stmtBaixa->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function read() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT
                        ui.*,
                        i.nome_insumo,
                        i.valor_unitario,
                        (ui.quantidade_utilizada * i.valor_unitario) AS valor_total,
                        l.numero_leito,
                        l.ala,
                        p.nome AS paciente_nome,
                        inter.status_internacao
                    FROM uso_insumos ui
                    INNER JOIN insumos i ON ui.id_insumo = i.id_insumo
                    INNER JOIN leitos l ON ui.id_leito = l.id_leito
                    LEFT JOIN internacoes inter ON ui.id_internacao = inter.id_internacao
                    LEFT JOIN pacientes p ON inter.id_paciente = p.id_paciente
                    ORDER BY ui.data_uso DESC, ui.id_uso DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch (PDOException $e) {
            return null;
        }
    }

    public function readPorInternacao($idInternacao) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT
                        ui.*,
                        i.nome_insumo,
                        i.valor_unitario,
                        (ui.quantidade_utilizada * i.valor_unitario) AS valor_total
                    FROM uso_insumos ui
                    INNER JOIN insumos i ON ui.id_insumo = i.id_insumo
                    WHERE ui.id_internacao = :id_internacao
                    ORDER BY ui.data_uso DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_internacao", $idInternacao);
            $stmt->execute();

            return $stmt;

        } catch (PDOException $e) {
            return null;
        }
    }
}
?>