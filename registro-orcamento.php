<?php

use Alura\DesignPattern\Http\CurlHttpAdapter;
use Alura\DesignPattern\Http\ReactPHPHttpAdapter;
use Alura\DesignPattern\Orcamento;
use Alura\DesignPattern\RegistroOrcamento;

require 'vendor/autoload.php';

// Repare que o adaptador usado ao instanciar RegistroOrcamento mudou, e o código mantém seu funcionamento.
$registroOrcamento = new RegistroOrcamento(new ReactPHPHttpAdapter());
$registroOrcamento->registrarOrcamento(new Orcamento());
// $orcamento = new Orcamento();
// $orcamento->valor = 500;
// $orcamento->quantidadeItens = 2;
// $orcamento->aprova();
// $orcamento->finaliza();
// $registroOrcamento->registrarOrcamento($orcamento);
