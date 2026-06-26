<?php

class FaturamentoDAO {
    private $conn;
    private $table_name = "faturamento";

    public function __construct($db) {
        $this->conn = $db;
    }

    private function valorDecimal($valor) {
        if ($valor === null || $valor === '') {
            return 0.00;
        }

        $valor = str_replace(',', '.', (string)$valor);
        return (float) $valor;
    }

    private function nullSeVazio($valor) {
        return !empty($valor) ? $valor : null;
    }

    private function tabelaExiste($tabela) {
        try {
            $stmt = $this->conn->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tabela]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function colunaExiste($tabela, $coluna) {
        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM {$tabela} LIKE ?");
            $stmt->execute([$coluna]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function create(Faturamento $faturamento) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (
                        valor_total,
                        data_faturamento,
                        status_pagamento,
                        observacoes,
                        id_paciente,
                        id_consulta,
                        id_internacao
                    )
                    VALUES
                    (
                        :valor_total,
                        :data_faturamento,
                        :status_pagamento,
                        :observacoes,
                        :id_paciente,
                        :id_consulta,
                        :id_internacao
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":valor_total", $this->valorDecimal($faturamento->__get("valor_total")));
            $stmt->bindValue(":data_faturamento", $faturamento->__get("data_faturamento") ?: date('Y-m-d'));
            $stmt->bindValue(":status_pagamento", $faturamento->__get("status_pagamento") ?: "Pendente");
            $stmt->bindValue(":observacoes", $faturamento->__get("observacoes"));
            $stmt->bindValue(":id_paciente", $faturamento->__get("id_paciente"));
            $stmt->bindValue(":id_consulta", $this->nullSeVazio($faturamento->__get("id_consulta")));
            $stmt->bindValue(":id_internacao", $this->nullSeVazio($faturamento->__get("id_internacao")));

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }

            return false;

        } catch(PDOException $e) {
            return false;
        }
    }

    public function read() {
        return $this->readPending();
    }

    public function readPending() {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT
                        f.*,
                        p.nome AS paciente_nome,
                        p.cpf,
                        p.id_convenio,
                        conv.nome_convenio,
                        c.data_consulta,
                        c.horario,
                        i.data_entrada,
                        i.data_alta
                    FROM " . $this->table_name . " f
                    INNER JOIN pacientes p ON f.id_paciente = p.id_paciente
                    LEFT JOIN convenios conv ON p.id_convenio = conv.id_convenio
                    LEFT JOIN consultas c ON f.id_consulta = c.id_consulta
                    LEFT JOIN internacoes i ON f.id_internacao = i.id_internacao
                    ORDER BY f.data_faturamento DESC, f.id_faturamento DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function readOne($idFaturamento) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT
                        f.*,
                        p.nome AS paciente_nome,
                        p.cpf,
                        p.id_convenio,
                        conv.nome_convenio
                    FROM " . $this->table_name . " f
                    INNER JOIN pacientes p ON f.id_paciente = p.id_paciente
                    LEFT JOIN convenios conv ON p.id_convenio = conv.id_convenio
                    WHERE f.id_faturamento = :id_faturamento
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_faturamento", $idFaturamento);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            return null;
        }
    }

    public function adicionarItem($idFaturamento, $descricao, $tipoItem, $quantidade, $valorUnitario) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $quantidade = (float) $quantidade;
            $valorUnitario = $this->valorDecimal($valorUnitario);
            $valorTotal = $quantidade * $valorUnitario;

            $query = "INSERT INTO itens_faturamento
                    (
                        descricao,
                        tipo_item,
                        quantidade,
                        valor_unitario,
                        valor_total,
                        id_faturamento
                    )
                    VALUES
                    (
                        :descricao,
                        :tipo_item,
                        :quantidade,
                        :valor_unitario,
                        :valor_total,
                        :id_faturamento
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":descricao", $descricao);
            $stmt->bindValue(":tipo_item", $tipoItem);
            $stmt->bindValue(":quantidade", $quantidade);
            $stmt->bindValue(":valor_unitario", $valorUnitario);
            $stmt->bindValue(":valor_total", $valorTotal);
            $stmt->bindValue(":id_faturamento", $idFaturamento);

            $resultado = $stmt->execute();

            if ($resultado) {
                $this->recalcularValorTotal($idFaturamento);
            }

            return $resultado;

        } catch(PDOException $e) {
            return false;
        }
    }

    public function listarItens($idFaturamento) {
        if ($this->conn === null) {
            return null;
        }

        try {
            $query = "SELECT *
                    FROM itens_faturamento
                    WHERE id_faturamento = :id_faturamento
                    ORDER BY id_item ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_faturamento", $idFaturamento);
            $stmt->execute();

            return $stmt;

        } catch(PDOException $e) {
            return null;
        }
    }

    public function buscarGuiaAberta($idPaciente, $idConsulta = null, $idInternacao = null) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $sql = "SELECT id_faturamento
                    FROM faturamento
                    WHERE id_paciente = :id_paciente
                      AND status_pagamento = 'Pendente'";

            if (!empty($idConsulta)) {
                $sql .= " AND id_consulta = :id_consulta";
            }

            if (!empty($idInternacao)) {
                $sql .= " AND id_internacao = :id_internacao";
            }

            if (empty($idConsulta) && empty($idInternacao)) {
                $sql .= " AND id_consulta IS NULL AND id_internacao IS NULL";
            }

            $sql .= " ORDER BY id_faturamento DESC LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":id_paciente", $idPaciente);

            if (!empty($idConsulta)) {
                $stmt->bindValue(":id_consulta", $idConsulta);
            }

            if (!empty($idInternacao)) {
                $stmt->bindValue(":id_internacao", $idInternacao);
            }

            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row['id_faturamento'] ?? false;

        } catch(PDOException $e) {
            return false;
        }
    }

    public function criarGuiaAberta($idPaciente, $idConsulta = null, $idInternacao = null, $observacoes = '') {
        if ($this->conn === null) {
            return false;
        }

        try {
            if (trim($observacoes) === '') {
                $observacoes = 'Guia aberta automaticamente pelo sistema.';
            }

            $query = "INSERT INTO faturamento
                    (
                        valor_total,
                        data_faturamento,
                        status_pagamento,
                        observacoes,
                        id_paciente,
                        id_consulta,
                        id_internacao
                    )
                    VALUES
                    (
                        0,
                        CURDATE(),
                        'Pendente',
                        :observacoes,
                        :id_paciente,
                        :id_consulta,
                        :id_internacao
                    )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":observacoes", $observacoes);
            $stmt->bindValue(":id_paciente", $idPaciente);
            $stmt->bindValue(":id_consulta", $this->nullSeVazio($idConsulta));
            $stmt->bindValue(":id_internacao", $this->nullSeVazio($idInternacao));

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }

            return false;

        } catch(PDOException $e) {
            return false;
        }
    }

    public function buscarOuCriarGuiaAberta($idPaciente, $idConsulta = null, $idInternacao = null, $observacoes = '') {
        $idGuia = $this->buscarGuiaAberta($idPaciente, $idConsulta, $idInternacao);

        if ($idGuia) {
            return $idGuia;
        }

        return $this->criarGuiaAberta($idPaciente, $idConsulta, $idInternacao, $observacoes);
    }

    public function itemJaLancado($idFaturamento, $descricao, $tipoItem) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "SELECT id_item
                    FROM itens_faturamento
                    WHERE id_faturamento = :id_faturamento
                      AND descricao = :descricao
                      AND tipo_item = :tipo_item
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_faturamento", $idFaturamento);
            $stmt->bindValue(":descricao", $descricao);
            $stmt->bindValue(":tipo_item", $tipoItem);
            $stmt->execute();

            return $stmt->rowCount() > 0;

        } catch(PDOException $e) {
            return false;
        }
    }

    public function lancarItemAutomatico(
        $idPaciente,
        $descricao,
        $tipoItem,
        $quantidade,
        $valorUnitario,
        $idConsulta = null,
        $idInternacao = null,
        $evitarDuplicidade = true
    ) {
        if ($this->conn === null || empty($idPaciente)) {
            return false;
        }

        try {
            $idGuia = $this->buscarOuCriarGuiaAberta(
                $idPaciente,
                $idConsulta,
                $idInternacao,
                'Guia aberta automaticamente para controle de procedimentos hospitalares.'
            );

            if (!$idGuia) {
                return false;
            }

            if ($evitarDuplicidade && $this->itemJaLancado($idGuia, $descricao, $tipoItem)) {
                return $idGuia;
            }

            $this->adicionarItem(
                $idGuia,
                $descricao,
                $tipoItem,
                $quantidade,
                $valorUnitario
            );

            $this->recalcularValorTotal($idGuia);

            return $idGuia;

        } catch(PDOException $e) {
            return false;
        }
    }

    public function recalcularValorTotal($idFaturamento) {
        if ($this->conn === null || empty($idFaturamento)) {
            return false;
        }

        try {
            $query = "UPDATE faturamento
                    SET valor_total = (
                        SELECT COALESCE(SUM(valor_total), 0)
                        FROM itens_faturamento
                        WHERE id_faturamento = :id_faturamento_soma
                    )
                    WHERE id_faturamento = :id_faturamento";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id_faturamento_soma", $idFaturamento);
            $stmt->bindValue(":id_faturamento", $idFaturamento);

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function consolidarGastos($idPaciente, $idConsulta = null, $idInternacao = null) {
        if ($this->conn === null) {
            return [
                'total_exames' => 0,
                'total_medicamentos' => 0,
                'total_insumos' => 0,
                'total_honorarios' => 150,
                'total_geral' => 150
            ];
        }

        try {
            $totalExames = 0;
            $totalMedicamentos = 0;
            $totalInsumos = 0;
            $totalHonorarios = 150.00;

            if (!empty($idConsulta)) {
                $queryExames = "SELECT COALESCE(SUM(e.valor_exame), 0) AS total
                                FROM exames e
                                INNER JOIN prontuario pr ON e.id_prontuario = pr.id_prontuario
                                WHERE pr.id_consulta = :id_consulta";

                $stmtExames = $this->conn->prepare($queryExames);
                $stmtExames->bindValue(":id_consulta", $idConsulta);
                $stmtExames->execute();
                $totalExames = (float)($stmtExames->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

                $queryMedicamentos = "SELECT COALESCE(SUM(m.valor_unitario), 0) AS total
                                    FROM prescricoes p
                                    INNER JOIN medicamentos m ON p.id_medicamento = m.id_medicamento
                                    INNER JOIN prontuario pr ON p.id_prontuario = pr.id_prontuario
                                    WHERE pr.id_consulta = :id_consulta";

                $stmtMedicamentos = $this->conn->prepare($queryMedicamentos);
                $stmtMedicamentos->bindValue(":id_consulta", $idConsulta);
                $stmtMedicamentos->execute();
                $totalMedicamentos = (float)($stmtMedicamentos->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            }

            if (!empty($idInternacao)) {
                $queryInsumos = "SELECT COALESCE(SUM(ui.quantidade_utilizada * i.valor_unitario), 0) AS total
                                FROM uso_insumos ui
                                INNER JOIN insumos i ON ui.id_insumo = i.id_insumo
                                WHERE ui.id_internacao = :id_internacao";

                $stmtInsumos = $this->conn->prepare($queryInsumos);
                $stmtInsumos->bindValue(":id_internacao", $idInternacao);
                $stmtInsumos->execute();
                $totalInsumos = (float)($stmtInsumos->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            } else {
                $queryInsumosPaciente = "SELECT COALESCE(SUM(ui.quantidade_utilizada * i.valor_unitario), 0) AS total
                                        FROM uso_insumos ui
                                        INNER JOIN insumos i ON ui.id_insumo = i.id_insumo
                                        INNER JOIN internacoes inter ON ui.id_internacao = inter.id_internacao
                                        WHERE inter.id_paciente = :id_paciente";

                $stmtInsumosPaciente = $this->conn->prepare($queryInsumosPaciente);
                $stmtInsumosPaciente->bindValue(":id_paciente", $idPaciente);
                $stmtInsumosPaciente->execute();
                $totalInsumos = (float)($stmtInsumosPaciente->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            }

            $totalGeral = $totalExames + $totalMedicamentos + $totalInsumos + $totalHonorarios;

            return [
                'total_exames' => $totalExames,
                'total_medicamentos' => $totalMedicamentos,
                'total_insumos' => $totalInsumos,
                'total_honorarios' => $totalHonorarios,
                'total_geral' => $totalGeral
            ];

        } catch(PDOException $e) {
            return [
                'total_exames' => 0,
                'total_medicamentos' => 0,
                'total_insumos' => 0,
                'total_honorarios' => 150,
                'total_geral' => 150
            ];
        }
    }

    public function gerarGuiaConsolidada($idPaciente, $idConsulta = null, $idInternacao = null) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $gastos = $this->consolidarGastos($idPaciente, $idConsulta, $idInternacao);

            $observacoes = "Guia consolidada automaticamente. " .
                "Exames: R$ " . number_format($gastos['total_exames'], 2, ',', '.') . "; " .
                "Medicamentos: R$ " . number_format($gastos['total_medicamentos'], 2, ',', '.') . "; " .
                "Insumos: R$ " . number_format($gastos['total_insumos'], 2, ',', '.') . "; " .
                "Honorários: R$ " . number_format($gastos['total_honorarios'], 2, ',', '.') . ".";

            $queryFaturamento = "INSERT INTO faturamento
                                (
                                    valor_total,
                                    data_faturamento,
                                    status_pagamento,
                                    observacoes,
                                    id_paciente,
                                    id_consulta,
                                    id_internacao
                                )
                                VALUES
                                (
                                    :valor_total,
                                    CURDATE(),
                                    'Pendente',
                                    :observacoes,
                                    :id_paciente,
                                    :id_consulta,
                                    :id_internacao
                                )";

            $stmtFat = $this->conn->prepare($queryFaturamento);

            $stmtFat->bindValue(":valor_total", $gastos['total_geral']);
            $stmtFat->bindValue(":observacoes", $observacoes);
            $stmtFat->bindValue(":id_paciente", $idPaciente);
            $stmtFat->bindValue(":id_consulta", $this->nullSeVazio($idConsulta));
            $stmtFat->bindValue(":id_internacao", $this->nullSeVazio($idInternacao));

            if (!$stmtFat->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $idFaturamento = $this->conn->lastInsertId();

            $itens = [
                [
                    'descricao' => 'Honorários médicos / atendimento',
                    'tipo_item' => 'Honorário',
                    'quantidade' => 1,
                    'valor_unitario' => $gastos['total_honorarios'],
                    'valor_total' => $gastos['total_honorarios']
                ],
                [
                    'descricao' => 'Exames vinculados ao prontuário',
                    'tipo_item' => 'Exame',
                    'quantidade' => 1,
                    'valor_unitario' => $gastos['total_exames'],
                    'valor_total' => $gastos['total_exames']
                ],
                [
                    'descricao' => 'Medicamentos prescritos',
                    'tipo_item' => 'Medicamento',
                    'quantidade' => 1,
                    'valor_unitario' => $gastos['total_medicamentos'],
                    'valor_total' => $gastos['total_medicamentos']
                ],
                [
                    'descricao' => 'Insumos utilizados em leito/internação',
                    'tipo_item' => 'Insumo',
                    'quantidade' => 1,
                    'valor_unitario' => $gastos['total_insumos'],
                    'valor_total' => $gastos['total_insumos']
                ]
            ];

            $queryItem = "INSERT INTO itens_faturamento
                        (
                            descricao,
                            tipo_item,
                            quantidade,
                            valor_unitario,
                            valor_total,
                            id_faturamento
                        )
                        VALUES
                        (
                            :descricao,
                            :tipo_item,
                            :quantidade,
                            :valor_unitario,
                            :valor_total,
                            :id_faturamento
                        )";

            $stmtItem = $this->conn->prepare($queryItem);

            foreach ($itens as $item) {
                if ((float)$item['valor_total'] <= 0) {
                    continue;
                }

                $stmtItem->bindValue(":descricao", $item['descricao']);
                $stmtItem->bindValue(":tipo_item", $item['tipo_item']);
                $stmtItem->bindValue(":quantidade", $item['quantidade']);
                $stmtItem->bindValue(":valor_unitario", $item['valor_unitario']);
                $stmtItem->bindValue(":valor_total", $item['valor_total']);
                $stmtItem->bindValue(":id_faturamento", $idFaturamento);

                if (!$stmtItem->execute()) {
                    $this->conn->rollBack();
                    return false;
                }
            }

            $this->conn->commit();
            return $idFaturamento;

        } catch(PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function updateStatus($id, $status) {
        if ($this->conn === null) {
            return false;
        }

        try {
            $query = "UPDATE " . $this->table_name . "
                    SET status_pagamento = :status_pagamento
                    WHERE id_faturamento = :id_faturamento";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":status_pagamento", $status);
            $stmt->bindValue(":id_faturamento", $id);

            return $stmt->execute();

        } catch(PDOException $e) {
            return false;
        }
    }

    public function getTotalPorStatus($status) {
        if ($this->conn === null) {
            return 0;
        }

        try {
            $query = "SELECT COALESCE(SUM(valor_total), 0) AS total
                    FROM " . $this->table_name . "
                    WHERE status_pagamento = :status";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row['total'] ?? 0;

        } catch(PDOException $e) {
            return 0;
        }
    }

    public function getContagemPorStatus($status) {
        if ($this->conn === null) {
            return 0;
        }

        try {
            $query = "SELECT COUNT(*) AS total
                    FROM " . $this->table_name . "
                    WHERE status_pagamento = :status";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":status", $status);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row['total'] ?? 0;

        } catch(PDOException $e) {
            return 0;
        }
    }
}
?>