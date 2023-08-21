<?php

use Alura\DesignPattern\Orcamento;
use Alura\DesignPattern\Pedido;

require 'vendor/autoload.php';

$pedidos = [];
$hoje = new DateTimeImmutable();

for ($i = 0; $i < 10000; $i++) {
    $pedido = new Pedido();
    $pedido->nomeCliente = md5((string) rand(1, 10000));
    $pedido->orcamento = new Orcamento();
    $pedido->dataFinalizacao = $hoje;

    $pedidos[] = $pedido;
}

// Exibir o uso de memória no arquivo.
echo memory_get_peak_usage(); // 4053640 bytes.
