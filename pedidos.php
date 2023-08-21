<?php

use Alura\DesignPattern\DadosExtrinsecosPedido;
use Alura\DesignPattern\Orcamento;
use Alura\DesignPattern\Pedido;

require 'vendor/autoload.php';

$pedidos = [];
$dados = new DadosExtrinsecosPedido(
    md5((string) rand(1, 10000)),
    new DateTimeImmutable()
);

for ($i = 0; $i < 10000; $i++) {
    $pedido = new Pedido();
    $pedido->dados = $dados;
    $pedido->orcamento = new Orcamento();

    $pedidos[] = $pedido;
}

// Exibir o uso de memória no arquivo.
echo memory_get_peak_usage(); 
// Antes usávamos 4053640 bytes.
// Agora usamos 3254928 bytes.
