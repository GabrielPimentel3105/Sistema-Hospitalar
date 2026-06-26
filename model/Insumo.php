<?php

class Insumo {
    private $conn;
    private $id_insumo;
    private $nome_insumo;
    private $quantidade_estoque;
    private $valor_unitario;
    private $estoque_minimo;

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
        $daoPath = __DIR__ . '/../dao/InsumoDAO.php';

        if (file_exists($daoPath)) {
            require_once $daoPath;
        }

        if (!class_exists('InsumoDAO')) {
            return false;
        }

        $dao = new InsumoDAO($this->conn);

        if (!method_exists($dao, $metodo)) {
            return false;
        }

        if (empty($argumentos)) {
            if (in_array($metodo, ['create', 'update'])) {
                return $dao->$metodo($this);
            }

            if ($metodo === 'delete') {
                return $dao->delete($this->__get('id_insumo'));
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

    public function update(...$argumentos) {
        return $this->executarDAO('update', $argumentos);
    }

    public function read(...$argumentos) {
        return $this->executarDAO('read', $argumentos);
    }

    public function readOne(...$argumentos) {
        return $this->executarDAO('readOne', $argumentos);
    }

    public function delete(...$argumentos) {
        return $this->executarDAO('delete', $argumentos);
    }

    public function countBaixoEstoque(...$argumentos) {
        return $this->executarDAO('countBaixoEstoque', $argumentos);
    }
}
?>