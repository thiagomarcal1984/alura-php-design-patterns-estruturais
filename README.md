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
