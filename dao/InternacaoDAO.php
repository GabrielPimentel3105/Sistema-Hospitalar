<?php

class InternacaoDAO {
    private $conn;
    private $table_name = "internacoes";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create(Internacao $internacao) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $queryVerifica = "SELECT status_leito
                              FROM leitos
                              WHERE id_leito = :id_leito
                              LIMIT 1";

            $stmtVerifica = $this->conn->prepare($queryVerifica);
            $stmtVerifica->bindValue(":id_leito", $internacao->__get("id_leito"));
            $stmtVerifica->execute();

            $leito = $stmtVerifica->fetch(PDO::FETCH_ASSOC);

            if (!$leito || $leito["status_leito"] !== "Disponível") {
                $this->conn->rollBack();
                return false;
            }

            $query = "INSERT INTO " . $this->table_name . "
                    (
                        data_entrada,
                        data_alta,
                        status_internacao,
                        id_paciente,
                        id_leito
                    )
                    VALUES
                    (
                        NOW(),
                        NULL,
                        'Ativa',
                        :id_paciente,
                        :id_leito
                    )";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_paciente", $internacao->__get("id_paciente"));
            $stmt->bindValue(":id_leito", $internacao->__get("id_leito"));

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $queryLeito = "UPDATE leitos
                           SET status_leito = 'Ocupado'
                           WHERE id_leito = :id_leito";

            $stmtLeito = $this->conn->prepare($queryLeito);
            $stmtLeito->bindValue(":id_leito", $internacao->__get("id_leito"));

            if (!$stmtLeito->execute()) {
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
                        i.*,
                        p.nome AS paciente_nome,
                        p.cpf,
                        l.numero_leito,
                        l.ala,
                        l.status_leito
                    FROM internacoes i
                    INNER JOIN pacientes p ON i.id_paciente = p.id_paciente
                    INNER JOIN leitos l ON i.id_leito = l.id_leito
                    ORDER BY i.data_entrada DESC, i.id_internacao DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch (PDOException $e) {
            return null;
        }
    }

    public function readAtivas() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT
                        i.*,
                        p.nome AS paciente_nome,
                        p.cpf,
                        l.numero_leito,
                        l.ala,
                        l.status_leito
                    FROM internacoes i
                    INNER JOIN pacientes p ON i.id_paciente = p.id_paciente
                    INNER JOIN leitos l ON i.id_leito = l.id_leito
                    WHERE i.status_internacao = 'Ativa'
                    AND i.data_alta IS NULL
                    ORDER BY i.data_entrada ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch (PDOException $e) {
            return null;
        }
    }

    public function darAlta($idInternacao) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $queryBusca = "SELECT id_leito
                           FROM internacoes
                           WHERE id_internacao = :id_internacao
                           AND status_internacao = 'Ativa'
                           AND data_alta IS NULL
                           LIMIT 1";

            $stmtBusca = $this->conn->prepare($queryBusca);
            $stmtBusca->bindValue(":id_internacao", $idInternacao);
            $stmtBusca->execute();

            $internacao = $stmtBusca->fetch(PDO::FETCH_ASSOC);

            if (!$internacao) {
                $this->conn->rollBack();
                return false;
            }

            $queryAlta = "UPDATE internacoes
                          SET
                              data_alta = NOW(),
                              status_internacao = 'Alta'
                          WHERE id_internacao = :id_internacao
                          AND status_internacao = 'Ativa'
                          AND data_alta IS NULL";

            $stmtAlta = $this->conn->prepare($queryAlta);
            $stmtAlta->bindValue(":id_internacao", $idInternacao);

            if (!$stmtAlta->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $queryLeito = "UPDATE leitos
                           SET status_leito = 'Higienização'
                           WHERE id_leito = :id_leito";

            $stmtLeito = $this->conn->prepare($queryLeito);
            $stmtLeito->bindValue(":id_leito", $internacao["id_leito"]);

            if (!$stmtLeito->execute()) {
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

    public function transferir($idInternacao, $novoLeito) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $queryAtual = "SELECT id_leito
                           FROM internacoes
                           WHERE id_internacao = :id_internacao
                           AND status_internacao = 'Ativa'
                           AND data_alta IS NULL
                           LIMIT 1";

            $stmtAtual = $this->conn->prepare($queryAtual);
            $stmtAtual->bindValue(":id_internacao", $idInternacao);
            $stmtAtual->execute();

            $internacao = $stmtAtual->fetch(PDO::FETCH_ASSOC);

            if (!$internacao) {
                $this->conn->rollBack();
                return false;
            }

            if ((string)$internacao["id_leito"] === (string)$novoLeito) {
                $this->conn->rollBack();
                return false;
            }

            $queryNovo = "SELECT status_leito
                          FROM leitos
                          WHERE id_leito = :id_leito
                          LIMIT 1";

            $stmtNovo = $this->conn->prepare($queryNovo);
            $stmtNovo->bindValue(":id_leito", $novoLeito);
            $stmtNovo->execute();

            $leitoNovo = $stmtNovo->fetch(PDO::FETCH_ASSOC);

            if (!$leitoNovo || $leitoNovo["status_leito"] !== "Disponível") {
                $this->conn->rollBack();
                return false;
            }

            $queryTransferencia = "UPDATE internacoes
                                   SET id_leito = :novo_leito
                                   WHERE id_internacao = :id_internacao
                                   AND status_internacao = 'Ativa'
                                   AND data_alta IS NULL";

            $stmtTransferencia = $this->conn->prepare($queryTransferencia);
            $stmtTransferencia->bindValue(":novo_leito", $novoLeito);
            $stmtTransferencia->bindValue(":id_internacao", $idInternacao);

            if (!$stmtTransferencia->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $queryLeitoAntigo = "UPDATE leitos
                                 SET status_leito = 'Higienização'
                                 WHERE id_leito = :id_leito";

            $stmtLeitoAntigo = $this->conn->prepare($queryLeitoAntigo);
            $stmtLeitoAntigo->bindValue(":id_leito", $internacao["id_leito"]);

            if (!$stmtLeitoAntigo->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $queryLeitoNovo = "UPDATE leitos
                               SET status_leito = 'Ocupado'
                               WHERE id_leito = :id_leito";

            $stmtLeitoNovo = $this->conn->prepare($queryLeitoNovo);
            $stmtLeitoNovo->bindValue(":id_leito", $novoLeito);

            if (!$stmtLeitoNovo->execute()) {
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

    public function cancelar($idInternacao) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $queryBusca = "SELECT id_leito
                           FROM internacoes
                           WHERE id_internacao = :id_internacao
                           AND status_internacao = 'Ativa'
                           AND data_alta IS NULL
                           LIMIT 1";

            $stmtBusca = $this->conn->prepare($queryBusca);
            $stmtBusca->bindValue(":id_internacao", $idInternacao);
            $stmtBusca->execute();

            $internacao = $stmtBusca->fetch(PDO::FETCH_ASSOC);

            if (!$internacao) {
                $this->conn->rollBack();
                return false;
            }

            $query = "UPDATE internacoes
                      SET
                          status_internacao = 'Cancelada',
                          data_alta = NOW()
                      WHERE id_internacao = :id_internacao
                      AND status_internacao = 'Ativa'
                      AND data_alta IS NULL";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_internacao", $idInternacao);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $queryLeito = "UPDATE leitos
                           SET status_leito = 'Disponível'
                           WHERE id_leito = :id_leito";

            $stmtLeito = $this->conn->prepare($queryLeito);
            $stmtLeito->bindValue(":id_leito", $internacao["id_leito"]);

            if (!$stmtLeito->execute()) {
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

    public function countAtivas() {
        if ($this->conn === null) {
            return 0;
        }

        try {
            $query = "SELECT COUNT(*) AS total
                      FROM internacoes
                      WHERE status_internacao = 'Ativa'
                      AND data_alta IS NULL";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row["total"] ?? 0;

        } catch (PDOException $e) {
            return 0;
        }
    }
}
?>