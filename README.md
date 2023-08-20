# Padrões estruturais (Gang of Four)
- [x] Adapter
- [x] Bridge
- [ ] Composite
- [x] Decorator
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

## Implementações de formatos
As interfaces `ConteudoExportado` e `ArquivoExportado` fazem uma ponte (**bridge**) entre os conteúdos concretos e e as exportações concretas dos formatos de arquivos.

Interace `ArquivoExportado`:
```php
<?php

namespace Alura\DesignPattern\Relatorios;

interface ArquivoExportado
{
    public function salvar(ConteudoExportado $conteudoExportado) : string;
}
```
Implementação pra exportar como Zip:
```php
<?php

namespace Alura\DesignPattern\Relatorios;

class ArquivoZipExportado implements ArquivoExportado
{
    private string $nomeArquivoInterno;

    public function __construct(string $nomeArquivoInterno)
    {
        $this->nomeArquivoInterno = $nomeArquivoInterno;
    }

    public function salvar(ConteudoExportado $conteudoExportado) : string
    {
        $caminhoArquivo = tempnam(sys_get_temp_dir(), 'zip');
        $arquivoZip = new \ZipArchive();
        $arquivoZip->open($caminhoArquivo);
        $arquivoZip->addFromString($this->nomeArquivoInterno, serialize($conteudoExportado->conteudo()));
        $arquivoZip->close();
        return $caminhoArquivo;
    }
}
```
Implementação para exportar como XML:
```php
<?php

namespace Alura\DesignPattern\Relatorios;

class ArquivoXmlExportado implements ArquivoExportado
{
    private string $nomeElementoPai;

    public function __construct(string $nomeElementoPai)
    {
        $this->nomeElementoPai = $nomeElementoPai;
    }
    
    public function salvar(ConteudoExportado $conteudoExportado) : string
    {
        $elementoXml = new \SimpleXMLElement("<{$this->nomeElementoPai}/>");
        foreach ($conteudoExportado->conteudo() as $item => $valor) {
            $elementoXml->addChild($item, $valor);
        }

        $caminhoArquivo = tempnam(sys_get_temp_dir(), 'xml');
        $elementoXml->asXML($caminhoArquivo);

        return $caminhoArquivo;
    }
}
```

Invocação dos diferentes tipos de conteúdo exportados por diferentes tipos de algoritmos de exportação:
```php
// relatorio.php
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
```
## Explicando o padrão
As interfaces `ConteudoExportado` e `ArquivoExportado` são dois lados de uma ponte entre os diversos tipos de conteúdo concretos (orçamentos, pedidos, notas fiscais etc.) e os diversos algoritmos de exportação concretos (XML, CSV, JSON, ZIP etc.).

Leitura complementar sobre o padrão Bridge: https://refactoring.guru/design-patterns/bridge

# Mais de um imposto com Decorators
## Implementando impostos compostos
Suponhamos que seja necessário combinar impostos de classes diferentes. Implementar essa combinação em uma classe deixa tudo mais complexo e o número de classes resultantes dessa combinação pode crescer ao infinito.

Código de exemplo da combinação do ICMS com ISS: 
```php
<?php

namespace Alura\DesignPattern\Impostos;

use Alura\DesignPattern\Orcamento;

class IcmsComIss implements Imposto
{
    public function calculaImposto(Orcamento $orcamento): float
    {
        return 
            (new Icms())->calculaImposto($orcamento) + 
            (new Iss())->calculaImposto($orcamento);
    }
}
```
## Decorando impostos
A solução é usar uma recursividade do cálculo de impostos: uma classe abstrata contém um método que chama recursivamente os impostos, que são encadeados nos construtores de cada classe de imposto.

Mudança da interface `Imposto` para classe abstrata:
```php
<?php

namespace Alura\DesignPattern\Impostos;

use Alura\DesignPattern\Orcamento;

abstract class Imposto
{
    private ?Imposto $outroImposto;

    public function __construct(Imposto $outroImposto = null)
    {
        $this->outroImposto = $outroImposto;
    }

    abstract protected function realizaCalculoEspecifico(Orcamento $orcamento): float;

    public function calculaImposto(Orcamento $orcamento): float
    {
        return
            $this->realizaCalculoEspecifico($orcamento) +
            $this->realizaCalculoDeOutroImposto($orcamento);
    }

    private function realizaCalculoDeOutroImposto(Orcamento $orcamento)
    {
        return $this->outroImposto === null ?
            0 :
            $this->outroImposto->calculaImposto($orcamento);
    }
}
```
> Perceba que o método `realizaCalculoEspecifico` obtém o valor da classe de imposto específica; o método `calculaImposto` é o metodo que calcula o valor do imposto atual e soma recursivamente com o valor do outro imposto, calculado pelo método `realizaCalculoDeOutroImposto`.

Adaptação do imposto ICMS:
```php
<?php

namespace Alura\DesignPattern\Impostos;

use Alura\DesignPattern\Orcamento;

class Icms extends Imposto
{
    public function realizaCalculoEspecifico(Orcamento $orcamento): float
    {
        return $orcamento->valor * 0.1;
    }
}
```
Adaptação da superclasse `ImpostoCom2Aliquotas` (base para as classes `Ikcv` e `Icpp`, as quais não precisaram de mudanças):
```php
<?php

namespace Alura\DesignPattern\Impostos;

use Alura\DesignPattern\Orcamento;

abstract class ImpostoCom2Aliquotas extends Imposto
{
    public function realizaCalculoEspecifico(Orcamento $orcamento): float
    {
        if ($this->deveAplicarTaxaMaxima($orcamento)) {
            return $this->calculaTaxaMaxima($orcamento);
        }

        return $this->calculaTaxaMinima($orcamento);
    }

    abstract protected function deveAplicarTaxaMaxima(Orcamento $orcamento): bool;
    abstract protected function calculaTaxaMaxima(Orcamento $orcamento): float;
    abstract protected function calculaTaxaMinima(Orcamento $orcamento): float;
}
```
Adaptação do imposto ISS:
```php
<?php

namespace Alura\DesignPattern\Impostos;

use Alura\DesignPattern\Orcamento;

class Iss extends Imposto
{
    public function realizaCalculoEspecifico(Orcamento $orcamento): float
    {
        return $orcamento->valor * 0.06;
    }
}
```
Execução combinada de diferentes impostos no arquivo `teste.php`:
```php
<?php

use Alura\DesignPattern\CalculadoraDeDescontos;
use Alura\DesignPattern\CalculadoraDeImpostos;
use Alura\DesignPattern\Impostos\{Icms, Iss};
use Alura\DesignPattern\Orcamento;

require 'vendor/autoload.php';

$calculadora = new CalculadoraDeImpostos();

$orcamento = new Orcamento();
$orcamento->valor = 100;

echo $calculadora->calcula($orcamento, new Iss(new Icms())) . PHP_EOL; // Resultado: 16
echo $calculadora->calcula($orcamento, new Iss()) . PHP_EOL; // Resultado: 6
echo $calculadora->calcula($orcamento, new Icms()) . PHP_EOL; // Resultado: 10
```

## Explicando o padrão
O padrão decorator é uma forma de **adicionar, em tempo de execução, funcionalidades a outra funcionalidade já existente**. No caso dos impostos, a funcionalidade é acumular impostos obtidos de diferentes classes.

Leitura complementar sobre o padrão Decorator: https://refactoring.guru/design-patterns/decorator
