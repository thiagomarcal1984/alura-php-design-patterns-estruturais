# Padrões estruturais (Gang of Four)
- [x] Adapter
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
## Modificando a implementação
Em resumo: abstraindo vários códigos para uma interface adaptadora facilitamos a modificação de implementações através da mudança do adaptador concreto.

Mudança no adaptador chamado no arquivo `registro-orcamento.php`:
```php
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
```
Implementação do adaptador `ReactPHPHttpAdapter`:
```php
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
```

Leitura complementar sobre o padrão Adapter: https://refactoring.guru/design-patterns/adapter

# Exportando dados com Bridge

## Exportando orçamentos como XML
Criação da classe `OrcamentoXml.php`:
```php
<?php

namespace Alura\DesignPattern\Relatorios;

use Alura\DesignPattern\Orcamento;

class OrcamentoXml
{
    public function exportarOrcamento(Orcamento $orcamento) : string
    {
        $elementoOrcamento = new \SimpleXMLElement('<orcamento/>');
        $elementoOrcamento->addChild('valor', $orcamento->valor);
        $elementoOrcamento->addChild('quantidade_itens', $orcamento->quantidadeItens);

        return $elementoOrcamento->asXML();
    }
}
```
