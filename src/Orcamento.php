<?php

namespace Alura\DesignPattern;

use Alura\DesignPattern\EstadosOrcamento\EmAprovacao;
use Alura\DesignPattern\EstadosOrcamento\EstadoOrcamento;

class Orcamento
{
    private array $itens;
    public EstadoOrcamento $estadoAtual;

    public function __construct()
    {
        $this->estadoAtual = new EmAprovacao();
        $this->itens = [];
    }

    public function aplicaDescontoExtra()
    {
        $this->valor -= $this->estadoAtual->calculaDescontoExtra($this);
    }

    public function aprova()
    {
        $this->estadoAtual->aprova($this);
    }

    public function reprova()
    {
        $this->estadoAtual->reprova($this);
    }

    public function finaliza()
    {
        $this->estadoAtual->finaliza($this);
    }

    public function addItem(ItemOrcamento $item)
    {
        $this->itens[] = $item;
    }

    public function valor() : float
    {
        return array_reduce(
            $this->itens, 
            fn ($valorAcumulado, $item) => $item->valor + $valorAcumulado, 0
            /*
            // A linha acima é igual a esta linha de baixo:
            function(float $valorAcumulado, ItemOrcamento $item) {
                return $item->valor + $valorAcumulado;
            },
            0 // Valor inicial da variável $valorAcumulado.
            */
        );
    }
}
