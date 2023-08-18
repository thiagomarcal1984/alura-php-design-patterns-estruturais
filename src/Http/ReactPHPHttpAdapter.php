<?php

namespace Alura\DesignPattern\Http;

class ReactPHPHttpAdapter implements HttpAdapter
{
    public function post(string $url, array $data = [])
    {
        // Instancio o React PHP.
        // Preparo os dados.
        // Faço a requisição.
        echo "ReactPHP";
    }
}
