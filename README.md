# Controle Financeiro

API REST de categorias e lançamentos, feita com PHP e Slim.

## Requisitos e configuração

- PHP 7.4 ou PHP 8.0 a 8.5
- Composer

Na pasta do projeto, instale as dependências:

```sh
composer install
```

## Executar a API

```sh
php -S localhost:8080 -t public
```

A API fica disponível em `http://localhost:8080`. Os dados são armazenados em memória e voltam aos valores iniciais quando o servidor reinicia.

## Executar os testes

Em outro terminal, na pasta do projeto:

```sh
php testes/controleFinan.php
```

Os testes criam e usam uma instância própria da aplicação no processo de teste; não iniciam o servidor nem alteram os dados do servidor em execução. Podem ser executados novamente com o mesmo resultado.

## Endpoints

Requisições com corpo devem usar JSON e o cabeçalho `Content-Type: application/json`. As respostas, inclusive erros, são JSON.

| Método | Endpoint | Descrição |
| --- | --- | --- |
| GET | `/status` | Verifica o estado da API. |
| GET | `/categoria` | Lista categorias. |
| GET | `/categoria/{id}` | Consulta uma categoria. |
| POST | `/categoria` | Cadastra uma categoria (`nome`, `tipo`). |
| PUT | `/categoria/{id}` | Atualiza uma categoria (`nome`, `tipo`). |
| DELETE | `/categoria/{id}` | Exclui uma categoria. |
| POST | `/lancamento` | Cadastra um lançamento (`descricao`, `valor`, `data`, `tipo`, `categoria`). |
| GET | `/lancamentos` | Lista lançamentos; filtros opcionais: `dataInicial`, `dataFinal`, `categoria`, `tipo`. |
| GET | `/lancamento/{id}` | Consulta um lançamento. |
| PUT | `/lancamento/{id}` | Atualiza um lançamento, enviando os campos do cadastro. |
| DELETE | `/lancamento/{id}` | Exclui um lançamento. |
| GET | `/saldo` | Consulta o saldo atual. |
| GET | `/totais?dataInicial=AAAA-MM-DD&dataFinal=AAAA-MM-DD` | Consulta receitas e despesas no período. |

O tipo de categoria e lançamento é `receita` ou `despesa`. A data deve usar o formato `AAAA-MM-DD`. Erros de validação retornam `422`; recursos inexistentes retornam `404`.

## Histórico Git

O projeto já está em um repositório Git com histórico. Para registrar novas alterações:

```sh
git add README.md src/app.php testes/controleFinan.php
git commit -m "docs: registra instruções e endpoints da API"
git push origin master
```

Consulte os commits existentes com `git log --oneline`.
