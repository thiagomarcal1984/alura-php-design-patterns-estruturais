<?php

use Alura\DesignPattern\{Orcamento, Pedido};
use Alura\DesignPattern\Relatorios\{OrcamentoExportado, PedidoExportado};
use Alura\DesignPattern\Relatorios\{ArquivoXmlExportado, ArquivoZipExportado};

require 'vendor/autoload.php';

// Criação do orçamento
$orcamento = new Orcamento();
$orcamento->valor = 500;
$orcamento->quantidadeItens = 7;
$orcamentoExportado = new OrcamentoExportado($orcamento);

// Criação e uso dos dois exportadores de orçamento.
$orcamentoExportadoXml = new ArquivoXmlExportado("orcamento");
echo $orcamentoExportadoXml->salvar($orcamentoExportado) . PHP_EOL;

$orcamentoExportadoZip = new ArquivoZipExportado("orcamento.array");
echo $orcamentoExportadoZip->salvar($orcamentoExportado) . PHP_EOL;

// Criação de um pedido.
$pedido = new Pedido();
$pedido->nomeCliente = 'Teste';
$pedido->dataFinalizacao = new \DateTimeImmutable();
$pedidoExportado = new PedidoExportado($pedido);

// Criação e uso dos dois exportadores de pedido.
$pedidoExportadoXml = new ArquivoXmlExportado("pedido");
echo $pedidoExportadoXml->salvar($pedidoExportado) . PHP_EOL;

$pedidoExportadoZip = new ArquivoZipExportado("pedido.array");
echo $pedidoExportadoZip->salvar($pedidoExportado) . PHP_EOL;
