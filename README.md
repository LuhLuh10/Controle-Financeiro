# Controle Financeiro

## 1. O que precisa

- PHP 7.4 ou PHP 8.0 a 8.5
- Composer

## 2. Instalar o projeto

Abra um terminal na pasta do projeto e instale as dependências:

```sh
composer install
```

## 3. Iniciar a API

Na pasta do projeto, execute:

```sh
php -S localhost:8080 -t public
```

A API estará disponível em `http://localhost:8080`. Deixe esse terminal aberto enquanto usa a API. Para parar o servidor, pressione `Ctrl+C`.

Os dados ficam em memória. Ao reiniciar o servidor, eles voltam aos valores iniciais.

As categorias iniciais são `Compras` (ID 1, despesa) e `Contas` (ID 2, receita).

## 4. Executar os testes

Na pasta do projeto, execute:

```sh
php testes/controleFinan.php
```

Não é necessário iniciar a API para testar. O teste usa uma instância própria e não altera os dados do servidor. Ao terminar, mostra quantos testes passaram.

## Endpoints disponíveis

Requisições com dados devem estar em JSON e usar o cabeçalho `Content-Type: application/json`. As respostas, inclusive as de erro, também são JSON.

### Verificação da API

| Método | Endereço | O que faz |
| --- | --- | --- |
| GET | `/status` | Verifica se a API está funcionando. |

### Categorias

| Método | Endereço | O que faz |
| --- | --- | --- |
| GET | `/categoria` | Lista as categorias. |
| GET | `/categoria/{id}` | Consulta uma categoria pelo ID. |
| POST | `/categoria` | Cadastra uma categoria com `nome` e `tipo`. |
| PUT | `/categoria/{id}` | Atualiza `nome` e `tipo` da categoria. |
| DELETE | `/categoria/{id}` | Exclui uma categoria. |

### Lançamentos

| Método | Endereço | O que faz |
| --- | --- | --- |
| POST | `/lancamento` | Cadastra um lançamento com `descricao`, `valor`, `data`, `tipo` e `categoria` (ID). |
| GET | `/lancamentos` | Lista lançamentos. Pode filtrar por `dataInicial`, `dataFinal`, `categoria` e `tipo`. |
| GET | `/lancamento/{id}` | Consulta um lançamento pelo ID. |
| PUT | `/lancamento/{id}` | Atualiza os dados de um lançamento. |
| DELETE | `/lancamento/{id}` | Exclui um lançamento. |

O tipo deve ser `receita` ou `despesa`. A data deve usar o formato `AAAA-MM-DD`.

Exemplos de filtros:

- `GET /lancamentos?dataInicial=2023-01-01&dataFinal=2023-01-31`
- `GET /lancamentos?categoria=1&tipo=despesa`

### Consultas financeiras

| Método | Endereço | O que faz |
| --- | --- | --- |
| GET | `/saldo` | Mostra receitas menos despesas. |
| GET | `/totais?dataInicial=AAAA-MM-DD&dataFinal=AAAA-MM-DD` | Mostra receitas e despesas dentro do período. |

Ao criar um item, a API retorna `201`. Nas consultas, atualizações e exclusões feitas com sucesso, retorna `200`. Dados inválidos retornam `422`; nome de categoria repetido retorna `400`; ID inexistente retorna `404`.

## Histórico de alterações

O projeto usa Git para guardar as alterações. Para ver o histórico, execute `git log --oneline`. Para salvar uma nova alteração:

```sh
git add README.md src/app.php testes/controleFinan.php
git commit -m "descreva a alteração"
git push origin master
```
