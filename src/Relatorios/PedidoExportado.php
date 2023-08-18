<?php

namespace Alura\DesignPattern\Relatorios;

use Alura\DesignPattern\Pedido;

class PedidoExportado implements ConteudoExportado
{
    private Pedido $pedido;

    public function __construct(Pedido $pedido)
    {
        $this->pedido = $pedido;
    }

    public function conteudo() : array
    {
        return [
            'dataFinalizacao' => $this->pedido->dataFinalizacao->format('d/m/Y'),
            'nomeCliente' => $this->pedido->nomeCliente,
        ];
    }
}
