# Padrões estruturais (Gang of Four)
- [ ] Adapter
- [ ] Bridge
- [ ] Composite
- [ ] Decorator
- [ ] Façade (ou Facade)
- [ ] Business Delegate (?)
- [ ] Flyweight
- [ ] Proxy

Fonte: https://pt.wikipedia.org/wiki/Padr%C3%A3o_de_projeto_de_software

# Adapters para reutilizar dependências

## API de registro de orçamento
Um registro de orçamento pode ser feito com requisições POST com uma API, que pode acontecer de várias formas:
- Usando a biblioteca Guzzle;
- Usando a função `curl_init`;
- Usando a função `file_get_contents`;

Seja qual for a implementação específica, os detalhes da comunicação com a API não precisam ser conhecidas pela classe que solicita o registro do orçamento.

## Criando um adapter
A interface `HttpAdapter` abstrai as diferentes implementações possíveis para registrar os orçamentos na API:
```php
<?php

namespace Alura\DesignPattern\Http;

interface HttpAdapter
{
    public function post(string $url, array $data = []);
}
```
Implementação concreta do adaptador com curl:
```php
<?php

namespace Alura\DesignPattern\Http;

class CurlHttpAdapter implements HttpAdapter
{
    public function post(string $url, array $data = [])
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, $data);

        curl_exec($curl);
    }
}
```

A classe usuária do adaptador (`RegistroOrcamento.php`):
```php
<?php

namespace Alura\DesignPattern;

use Alura\DesignPattern\EstadosOrcamento\Finalizado;
use Alura\DesignPattern\Http\HttpAdapter;

class RegistroOrcamento
{
    private HttpAdapter $http;

    public function __construct(HttpAdapter $http)
    {
        $this->http = $http;
    }

    public function registrarOrcamento(Orcamento $orcamento) : void
    {
        if (!$orcamento->estadoAtual instanceof Finalizado) {
            throw new \DomainException(
                'Apenas orçamentos finalizados podem ser registrados na API.'
            );
        }
        
        $this->http->post('http://api.registrar.orcamento', [
            'valor' => $orcamento->valor,
            'quantidadeItens' => $orcamento->quantidadeItens,
        ]);
    }
}
```

> Note que a URL da API não existe, então o código vai quebrar.

Uso da classe `RegistroOrcamento` no arquivo `registro-orcamento.php`:
```php
<?php

use Alura\DesignPattern\Http\CurlHttpAdapter;
use Alura\DesignPattern\Orcamento;
use Alura\DesignPattern\RegistroOrcamento;

require 'vendor/autoload.php';

$registroOrcamento = new RegistroOrcamento(new CurlHttpAdapter());
$registroOrcamento->registrarOrcamento(new Orcamento());
```
