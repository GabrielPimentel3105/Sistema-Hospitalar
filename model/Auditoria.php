<?php

class Auditoria {
    private $conn;
    private $id_auditoria;
    private $descricao;
    private $data_auditoria;
    private $status_auditoria;
    private $id_faturamento;

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
        $daoPath = __DIR__ . '/../dao/AuditoriaDAO.php';

        if (file_exists($daoPath)) {
            require_once $daoPath;
        }

        if (!class_exists('AuditoriaDAO')) {
            return false;
        }

        $dao = new AuditoriaDAO($this->conn);

        if (!method_exists($dao, $metodo)) {
            return false;
        }

        if (empty($argumentos)) {
            if ($metodo === 'create') {
                return $dao->create($this);
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

    public function log(...$argumentos) {
        return $this->executarDAO('log', $argumentos);
    }

    public function auditarFaturamento(...$argumentos) {
        return $this->executarDAO('auditarFaturamento', $argumentos);
    }
}
?>