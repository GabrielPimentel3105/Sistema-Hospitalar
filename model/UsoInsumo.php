<?php

class UsoInsumo {
    private $conn;
    private $id_uso;
    private $quantidade_utilizada;
    private $data_uso;
    private $id_leito;
    private $id_insumo;
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
        $daoPath = __DIR__ . '/../dao/UsoInsumoDAO.php';

        if (file_exists($daoPath)) {
            require_once $daoPath;
        }

        if (!class_exists('UsoInsumoDAO')) {
            return false;
        }

        $dao = new UsoInsumoDAO($this->conn);

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

    public function readPorInternacao(...$argumentos) {
        return $this->executarDAO('readPorInternacao', $argumentos);
    }
}
?>