<?php

namespace Alura\DesignPattern\Relatorios;

interface ArquivoExportado
{
    public function salvar(ConteudoExportado $conteudoExportado) : string;
}
