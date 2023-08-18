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
    public function exportar(Orcamento $orcamento) : string
    {
        $elementoOrcamento = new \SimpleXMLElement('<orcamento/>');
        $elementoOrcamento->addChild('valor', $orcamento->valor);
        $elementoOrcamento->addChild('quantidade_itens', $orcamento->quantidadeItens);

        return $elementoOrcamento->asXML();
    }
}
```

## Exportando orçamentos como ZIP
Criação da classe que exporta o orçamento para o formato .zip:
```php
<?php

namespace Alura\DesignPattern\Relatorios;

use Alura\DesignPattern\Orcamento;

class OrcamentoZip
{
    public function exportar(Orcamento $orcamento)
    {
        $caminhoArquivo = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'orcamento.zip';
        $zip = new \ZipArchive();
        $zip->open($caminhoArquivo, \ZipArchive::CREATE);

        $zip->addFromString('orcamento.serial', serialize($orcamento));
        $zip->close();
    }
}
```

Invocação da classe `OrcamentoZip`:
```php
<?php

use Alura\DesignPattern\Orcamento;
use Alura\DesignPattern\Relatorios\OrcamentoZip;

require 'vendor/autoload.php';

$orcamentoZip = new OrcamentoZip();
$orcamento = new Orcamento();
$orcamento->valor = 500;

$orcamentoZip->exportar($orcamento);
```

> Problema: Duas classes foram necessárias para exportar um **orçamento** para XML e para ZIP. Se precisarmos exportar um outro objeto (por exemplo, um pedido ou uma nota fiscal), teríamos que criar mais duas classes para exportar cada novo tipo de objeto. E se houver mais um formato para exportar, seria necessário acrescentar mais um método para cada classe (o que dificulta muito a manutenção).

## Exportando conteúdo
As classes do pacote `Alura\DesignPattern\Relatorios` foram removidas. Primeiramente vamos abstrair o conteúdo exportado e criar implementações para dois tipos de conteúdo (pedido e orçamento).

Interface `ConteudoExportado`:
```php
<?php

namespace Alura\DesignPattern\Relatorios;

interface ConteudoExportado
{
    public function conteudo() : array;
}
```
Implementação do pedido exportado:
```php
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
```
Implementação do orçamento exportado:
```php
<?php

namespace Alura\DesignPattern\Relatorios;

use Alura\DesignPattern\Orcamento;

class OrcamentoExportado implements ConteudoExportado
{
    private Orcamento $orcamento;

    public function __construct(Orcamento $orcamento)
    {
        $this->orcamento = $orcamento;
    }

    public function conteudo() : array
    {
        return [
            'valor' => $this->orcamento->valor,
            'quantidadeItens' => $this->orcamento->quantidadeItens,
        ];
    }
}
```
> Na próxima aula veremos como implementar os formatos de exportação para cada tipo de conteúdo exportado.
