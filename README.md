# Padrões estruturais (Gang of Four)
- [x] Adapter
- [x] Bridge
- [x] Composite
- [x] Decorator
- [x] Façade (ou Facade)
- [ ] Business Delegate (?)
- [ ] Flyweight
- [x] Proxy

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

# Compondo orçamentos com Composite
## Apresentando o problema
A classe `Orcamento` vai ser modificada para uma estrutura master/detail: vamos acrescentar a classe `ItemOrcamento`.

Classe `Orcamento`:
```php
<?php

namespace Alura\DesignPattern;

use Alura\DesignPattern\EstadosOrcamento\EmAprovacao;
use Alura\DesignPattern\EstadosOrcamento\EstadoOrcamento;

class Orcamento
{
    private array $itens;
    public EstadoOrcamento $estadoAtual;

    public function __construct()
    {
        $this->estadoAtual = new EmAprovacao();
        $this->itens = [];
    }
    
    // ... omissão do resto do código.
    
    public function addItem(ItemOrcamento $item)
    {
        $this->itens[] = $item;
    }

    public function valor() : float
    {
        return array_reduce(
            $this->itens, 
            fn ($valorAcumulado, $item) => $item->valor + $valorAcumulado, 0
            /*
            // A linha acima é igual a esta linha de baixo:
            function(float $valorAcumulado, ItemOrcamento $item) {
                return $item->valor + $valorAcumulado;
            },
            0 // Valor inicial da variável $valorAcumulado.
            */
        );
    }
}
```

Classe `ItemOrcamento`:
```php
<?php

namespace Alura\DesignPattern;

class ItemOrcamento
{
    public float $valor;
}
```
Teste da classe no arqiuvo `itens.php`:
```php
<?php

use Alura\DesignPattern\ItemOrcamento;
use Alura\DesignPattern\Orcamento;

require 'vendor/autoload.php';

$orcamento = new Orcamento();

$item1 = new ItemOrcamento();
$item1->valor = 300;
$item2 = new ItemOrcamento();
$item2->valor = 500;

$orcamento->addItem($item1);
$orcamento->addItem($item2);

echo $orcamento->valor(); // Resultado: 800
```
> O problema em questão é: como fazer um orçamento composto por outros orçamentos?

## Para saber mais: Arrays
Os arrays no PHP são muito poderosos e há inúmeras funções para tratá-los e manipulá-los. No último vídeo, nós utilizamos a função array_reduce, que reduz um array a um único valor. No nosso caso, reduzimos um array de itens à soma de seus valores.

Para entender melhor o funcionamento desta e algumas outras funções, aqui está uma publicação no blog da Alura sobre arrays: Trabalhando com arrays em PHP (https://www.alura.com.br/artigos/trabalhando-com-arrays-em-php?_gl=1*zuszoz*_ga*MTYzNjkzMDE3Ny4xNjM1MzYwNzUx*_ga_59FP0KYKSM*MTY5MjUzODEyOC4yMTYuMS4xNjkyNTQyOTQ4LjAuMC4w*_fplc*S05WZ2kxSWFQYyUyRlhpWlolMkZpZ3V3U0dtV2l1MVVhM2R3UVZNTFFTbzU3OXNVRGpGcDNJZlg2b3lnWTczN084RVJEWm44Mjhob1dHa3VBaVJZN1dCekZRUWs0ckU5UVI3emgyZFR0RVp2Rm13dmg4OVFzakg5TngwN0MlMkIyelhRJTNEJTNE*_ga_1EPWSW3PCS*MTY5MjU0MDUzNS45LjEuMTY5MjU0Mjk0OC4wLjAuMA..).

## Compondo orçamentos
Os orçamentos funcionarão como uma árvore: um orçamento pode conter outros orçamentos.

Para isso, o elemento raiz e as "folhas" dessa raiz devem implementar uma mesma interface. Neste caso, usaremos uma nova interface chamada `Orcavel`:

```php
<?php

namespace Alura\DesignPattern;

interface Orcavel
{
    public function valor() : float;
}
```

Mudanças na classe `ItemOrcamento`:
```php
<?php

namespace Alura\DesignPattern;

class ItemOrcamento implements Orcavel
{
    public float $valor;

    public function valor() : float{
        // Cuidado pra não chamar recursivamente o método valor(),
        // senão estoura a pilha da memória.
        return $this->valor; 
    }
}
```

Mudanças na classe `Orcamento`:
```php
<?php

namespace Alura\DesignPattern;

use Alura\DesignPattern\EstadosOrcamento\EmAprovacao;
use Alura\DesignPattern\EstadosOrcamento\EstadoOrcamento;

class Orcamento implements Orcavel // Implementação da interface.
{
    private array $itens;
    public EstadoOrcamento $estadoAtual;

    public function __construct()
    {
        $this->estadoAtual = new EmAprovacao();
        $this->itens = [];
    }

    // ... omissão do resto do código.

    // A função generalizou: antes era o parâmetro era concreto: ItemOrcamento.
    public function addItem(Orcavel $item) 
    {
        $this->itens[] = $item;
    }

    public function valor() : float
    {
        return array_reduce(
            $this->itens, 
            fn (float $valorAcumulado, Orcavel $item) => $item->valor() + $valorAcumulado, 0
            /*
            // A linha acima é igual a esta linha de baixo:
            function(float $valorAcumulado, Orcavel $item) {
                return $item->valor() + $valorAcumulado;
            },
            0 // Valor inicial da variável $valorAcumulado.
            */
        );
    }
}
```
Execução do padrão Composite no arquivo `itens.php`:
```php
<?php

use Alura\DesignPattern\ItemOrcamento;
use Alura\DesignPattern\Orcamento;

require 'vendor/autoload.php';

$orcamento = new Orcamento();

$item1 = new ItemOrcamento();
$item1->valor = 300;
$item2 = new ItemOrcamento();
$item2->valor = 500;

$orcamento->addItem($item1);
$orcamento->addItem($item2);

$orcamentoAntigo = new Orcamento();
$item3 = new ItemOrcamento();
$item3->valor = 150;
$orcamentoAntigo->addItem($item3);

$orcamentoMaisAntigoAinda = new Orcamento();
$item4 = new ItemOrcamento();
$item4->valor = 50;
$item5 = new ItemOrcamento();
$item5->valor = 100;
$orcamentoMaisAntigoAinda->addItem($item4);
$orcamentoMaisAntigoAinda->addItem($item5);

$orcamento->addItem($orcamentoAntigo);
$orcamento->addItem($orcamentoMaisAntigoAinda);

echo $orcamento->valor(); // Exibe 1100.
```

## Explicando o padrão
O padrão Composite cria uma árvore em que cada nó contribui para uma ação conjunta maior. Pense na analogia de um exército, em que a hierarquia mais alta representa o nó do composite. No exemplo dos orçamentos, totalizar cada orçamento que compõe o orçamento geral.

Leitura complementar sobre o padrão Composite: https://refactoring.guru/design-patterns/composite

# Facade para descontos
## Logando um desconto
Criação de uma classe fictícia para logar os descontos calculados:

```php
<?php

namespace Alura\DesignPattern;

class LogDesconto
{
    public function informar(float $descontoCalculado): void
    {
        // biblioteca de log
        echo "Salvando log de desconto: $descontoCalculado" . PHP_EOL;
    }
}
```

Uso da classe fictícia na classe `CalculadoraDeDescontos`:
```php
<?php

namespace Alura\DesignPattern;

use Alura\DesignPattern\Descontos\Desconto;
use Alura\DesignPattern\Descontos\DescontoMaisDe500Reais;
use Alura\DesignPattern\Descontos\DescontoMaisDe5Itens;
use Alura\DesignPattern\Descontos\SemDesconto;

class CalculadoraDeDescontos
{
    public function calculaDescontos(Orcamento $orcamento): float
    {
        $cadeiaDeDescontos = new DescontoMaisDe5Itens(
            new DescontoMaisDe500Reais(
                new SemDesconto()
            )
        );

        $descontoCalculado = $cadeiaDeDescontos->calculaDesconto($orcamento);
        $logDesconto = new LogDesconto();
        $logDesconto->informar($descontoCalculado);
        
        return $descontoCalculado;
    }
}
```
## Falando sobre Facades
O padrão Facade visa disponibilizar uma interface simplificada para usar várias outras classes. Você não precisa conhecer todos os subsistemas para usar seus recursos: basta usar uma facade/fachada.

No exemplo da classe `CalculadoraDeDescontos`, a fachada calcula o desconto em cadeia e faz o log dele.

O framework PHP Laravel usa muitas fachadas.

Leitura complementar sobre o padrão Facade: https://refactoring.guru/design-patterns/facade

# Proxy de cache
## Demora no cálculo do valor
Suponhamos que a classe `ItemOrcamento` busque dados de uma API e que a busca desses dados demore 1 segundo:
```php
<?php

namespace Alura\DesignPattern;

class ItemOrcamento implements Orcavel
{
    public float $valor;

    public function valor() : float{
        sleep(1); // Simulando a latência da API.
        return $this->valor;
    }
}
```
O código a seguir demoraria 10 segundos para terminar sua execução (`itens.php`):
```php
<?php

use Alura\DesignPattern\ItemOrcamento;
use Alura\DesignPattern\Orcamento;

require 'vendor/autoload.php';

$orcamento = new Orcamento();

// Operações sobre o orçamento.

echo $orcamento->valor(); // Demora de 5 segundos.
echo $orcamento->valor(); // Demora de mais 5 segundos.
```
> Como evitar a repetição da consulta no mesmo orçamento?

## Implementando um Proxy
A classe `CacheOrcamentoProxy` a seguir extende da classe concreta `Orcamento` (e não da interface `Orcavel`):
```php
<?php

namespace Alura\DesignPattern;

class CacheOrcamentoProxy extends Orcamento
{
    private float $valorCache = 0;
    private Orcamento $orcamento;

    public function __construct(Orcamento $orcamento)
    {
        $this->orcamento = $orcamento;
    }

    public function addItem(Orcavel $item)
    {
        throw new \DomainException('Não é possível adicionar item a um orçamento cacheado.');
    }

    public function valor(): float
    {
        if ($this->valorCache  == 0)
            $this->valorCache = $this->orcamento->valor();
        return $this->valorCache;
    }
}
```

Agora, para impedir a latência, instanciamos um proxy do orçamento cujo valor queremos buscar (arquivo `itens.php`):
```php
<?php

use Alura\DesignPattern\CacheOrcamentoProxy;
use Alura\DesignPattern\ItemOrcamento;
use Alura\DesignPattern\Orcamento;

require 'vendor/autoload.php';

$orcamento = new Orcamento();

// Operações sobre o orçamento.

$proxyCache = new CacheOrcamentoProxy($orcamento);

echo $proxyCache->valor(); // Demora de 5 segundos para atribuir valor ao cache.
echo $proxyCache->valor(); // Agora o resultado é imediato, porque busca do cache...
echo $proxyCache->valor(); // ... do cache...
echo $proxyCache->valor(); // ... do cache...
echo $proxyCache->valor(); // ... do cache...
echo $proxyCache->valor(); // ... do cache.
```
## Explicando o padrão
Note que o uso do método `valor()` no orçamento original é limitado pelo objeto proxy.

Um cartão de crédito é um proxy para a nossa conta bancária.

Os padrões Proxy e Decorator são semelhantes, mas a diferença é que o Decorator `acrescenta` funcionalidades, enquanto o proxy `limita/intercepta` funcionalidades sob certas condições.

Leitura complementar sobre o padrão Proxy: https://refactoring.guru/design-patterns/proxy

# Flyweight: Pedidos mais leves
## Milhares de pedidos
Arquivo `pedidos.php` para criação de milhares de pedidos:

```php
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
```
> Como reusar algum objeto (por exemplo, um cliente) em várias instâncias de outros objetos?

## Aplicando Flyweight
Há dados que são intrínsecos de um objeto (ou seja, variam de objeto para objeto) e dados extrínsecos (que podem ser reusados).

A data e o nome do cliente, por exemplo, são extrínsecos do pedido. Já o orçamento é intrínseco.

Implementação da classe de dados extrínsecos do pedido:
```php
<?php

namespace Alura\DesignPattern;

class DadosExtrinsecosPedido
{
    public string $nomeCliente;
    public \DateTimeInterface $dataFinalizacao;
}
```
Adaptação da classe `Pedido`:
```php
<?php

namespace Alura\DesignPattern;

class Pedido
{
    public DadosExtrinsecosPedido $dados;
    public Orcamento $orcamento;
}
```

Invocação dos pedidos e de seus dados extrínsecos no script `pedidos.php`:
```php
<?php

use Alura\DesignPattern\DadosExtrinsecosPedido;
use Alura\DesignPattern\Orcamento;
use Alura\DesignPattern\Pedido;

require 'vendor/autoload.php';

$pedidos = [];
$dados = new DadosExtrinsecosPedido();
$dados->dataFinalizacao = new DateTimeImmutable();
$dados->nomeCliente = md5((string) rand(1, 10000));

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
```
