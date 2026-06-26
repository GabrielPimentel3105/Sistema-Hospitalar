<?php

class Faturamento {
    private $conn;
    private $id_faturamento;
    private $valor_total;
    private $data_faturamento;
    private $status_pagamento;
    private $observacoes;
    private $id_paciente;
    private $id_consulta;
    private $id_internacao;

    public function __construct($db = null) {
        $this->conn = $db;
    }

    public function __get($atributo) {
        return property_exists($this, $atributo) ? $this->$atributo : null;
    }

    public function __set($atributo, $valor) {
        if (property_exists($this, $atributo)) {
            $this->$atributo = $valor;
        }
    }

    private function executarDAO($metodo, $argumentos = []) {
        $daoPath = __DIR__ . '/../dao/FaturamentoDAO.php';

        if (file_exists($daoPath)) {
            require_once $daoPath;
        }

        if (!class_exists('FaturamentoDAO')) {
            return false;
        }

        $dao = new FaturamentoDAO($this->conn);

        if (!method_exists($dao, $metodo)) {
            return false;
        }

        if (empty($argumentos)) {
            if (in_array($metodo, ['create', 'update'])) {
                return $dao->$metodo($this);
            }
        }

        return call_user_func_array([$dao, $metodo], $argumentos);
    }

    public function __call($metodo, $argumentos) {
        return $this->executarDAO($metodo, $argumentos);
    }

    public function create(...$argumentos) {
        return $this->executarDAO('create', $argumentos);
    }

    public function read(...$argumentos) {
        return $this->executarDAO('read', $argumentos);
    }

    public function readOne(...$argumentos) {
        return $this->executarDAO('readOne', $argumentos);
    }

    public function readPending(...$argumentos) {
        return $this->executarDAO('readPending', $argumentos);
    }

    public function updateStatus(...$argumentos) {
        return $this->executarDAO('updateStatus', $argumentos);
    }

    public function consolidarGastos(...$argumentos) {
        return $this->executarDAO('consolidarGastos', $argumentos);
    }

    public function gerarGuiaConsolidada(...$argumentos) {
        return $this->executarDAO('gerarGuiaConsolidada', $argumentos);
    }

    public function listarItens(...$argumentos) {
        return $this->executarDAO('listarItens', $argumentos);
    }

    public function getTotalPorStatus(...$argumentos) {
        return $this->executarDAO('getTotalPorStatus', $argumentos);
    }

    public function getContagemPorStatus(...$argumentos) {
        return $this->executarDAO('getContagemPorStatus', $argumentos);
    }
}
?>