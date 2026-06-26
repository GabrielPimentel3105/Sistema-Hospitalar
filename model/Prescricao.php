<?php

class Prescricao {
    private $conn;
    private $id_prescricao;
    private $dosagem;
    private $frequencia;
    private $duracao_tratamento;
    private $id_prontuario;
    private $id_medicamento;
    private $status_prescricao;

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
        $daoPath = __DIR__ . '/../dao/PrescricaoDAO.php';

        if (file_exists($daoPath)) {
            require_once $daoPath;
        }

        if (!class_exists('PrescricaoDAO')) {
            return false;
        }

        $dao = new PrescricaoDAO($this->conn);

        if (!method_exists($dao, $metodo)) {
            return false;
        }

        if (empty($argumentos)) {
            if (in_array($metodo, ['create', 'update'])) {
                return $dao->$metodo($this);
            }

            if ($metodo === 'cancelar') {
                return $dao->cancelar($this->__get('id_prescricao'));
            }

            if ($metodo === 'delete') {
                return $dao->cancelar($this->__get('id_prescricao'));
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

    public function readByProntuario(...$argumentos) {
        return $this->executarDAO('readByProntuario', $argumentos);
    }

    public function update(...$argumentos) {
        return $this->executarDAO('update', $argumentos);
    }

    public function cancelar(...$argumentos) {
        return $this->executarDAO('cancelar', $argumentos);
    }

    public function delete(...$argumentos) {
        return $this->executarDAO('delete', $argumentos);
    }
}
?>