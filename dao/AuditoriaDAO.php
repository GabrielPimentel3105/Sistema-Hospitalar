<?php

class AuditoriaDAO {
    private $conn;
    private $table_name = "auditoria";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create(Auditoria $auditoria) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (
                        descricao,
                        data_auditoria,
                        status_auditoria,
                        id_faturamento
                    )
                    VALUES
                    (
                        :descricao,
                        NOW(),
                        :status_auditoria,
                        :id_faturamento
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":descricao", $auditoria->__get("descricao"));
            $stmt->bindValue(":status_auditoria", $auditoria->__get("status_auditoria") ?: "Log");
            $stmt->bindValue(":id_faturamento", !empty($auditoria->__get("id_faturamento")) ? $auditoria->__get("id_faturamento") : null);

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function log($descricao, $status = "Log", $idFaturamento = null) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $statusPermitidos = ['Conforme', 'Divergente', 'Pendente', 'Log'];

            if (!in_array($status, $statusPermitidos)) {
                $status = "Log";
            }

            $query = "INSERT INTO " . $this->table_name . "
                    (
                        descricao,
                        data_auditoria,
                        status_auditoria,
                        id_faturamento
                    )
                    VALUES
                    (
                        :descricao,
                        NOW(),
                        :status_auditoria,
                        :id_faturamento
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":descricao", $descricao);
            $stmt->bindValue(":status_auditoria", $status);
            $stmt->bindValue(":id_faturamento", !empty($idFaturamento) ? $idFaturamento : null);

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
            $query = "SELECT
                        a.*,
                        f.valor_total,
                        f.status_pagamento,
                        p.nome AS paciente_nome
                    FROM " . $this->table_name . " a
                    LEFT JOIN faturamento f ON a.id_faturamento = f.id_faturamento
                    LEFT JOIN pacientes p ON f.id_paciente = p.id_paciente
                    ORDER BY a.data_auditoria DESC, a.id_auditoria DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function auditarFaturamento($idFaturamento) {
        if ($this->conn === null || empty($idFaturamento)) {
            return false;
        }

        try {
            $queryFaturamento = "SELECT *
                                FROM faturamento
                                WHERE id_faturamento = :id_faturamento
                                LIMIT 1";

            $stmtFat = $this->conn->prepare($queryFaturamento);
            $stmtFat->bindValue(":id_faturamento", $idFaturamento);
            $stmtFat->execute();

            $faturamento = $stmtFat->fetch(PDO::FETCH_ASSOC);

            if (!$faturamento) {
                return false;
            }

            $queryItens = "SELECT COALESCE(SUM(valor_total), 0) AS total_itens
                        FROM itens_faturamento
                        WHERE id_faturamento = :id_faturamento";

            $stmtItens = $this->conn->prepare($queryItens);
            $stmtItens->bindValue(":id_faturamento", $idFaturamento);
            $stmtItens->execute();

            $totalItens = (float)($stmtItens->fetch(PDO::FETCH_ASSOC)['total_itens'] ?? 0);
            $valorFaturado = (float)($faturamento['valor_total'] ?? 0);

            $diferenca = abs($valorFaturado - $totalItens);

            if ($diferenca <= 0.01) {
                return $this->log(
                    "Auditoria realizada: guia #" . $idFaturamento . " conforme. Valor faturado confere com os itens registrados.",
                    "Conforme",
                    $idFaturamento
                );
            }

            return $this->log(
                "Auditoria realizada: guia #" . $idFaturamento . " divergente. Valor faturado: R$ " .
                number_format($valorFaturado, 2, ',', '.') .
                " / Soma dos itens: R$ " . number_format($totalItens, 2, ',', '.') . ".",
                "Divergente",
                $idFaturamento
            );

        } catch(PDOException $e) {
            return false;
        }
    }
}
?>