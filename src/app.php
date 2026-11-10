<?php
require __DIR__ . '/../vendor/autoload.php';
 
use Slim\Exception\HttpException;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ResponseFactory;
 
$app = AppFactory::create();
 
$app->get('/status', function ($request, $response) {
    $response->getBody()->write(json_encode(['status' => 'ok']));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});
 
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(false, false, false);
$errorMiddleware->setDefaultErrorHandler(function ($request, $exception) {
    $status = $exception instanceof HttpException ? $exception->getCode() : 500;
    $mensagem = 'Ocorreu um erro interno.';
    if ($status === 404) {
        $mensagem = 'Rota não encontrada.';
    } elseif ($status === 405) {
        $mensagem = 'Método HTTP não permitido.';
    } elseif ($status === 400) {
        $mensagem = 'Requisição inválida.';
    }

    $response = (new ResponseFactory())->createResponse($status);
    $response->getBody()->write(json_encode(['erro' => $mensagem]));
    return $response->withHeader('Content-Type', 'application/json');
});
$categoria = [
['id' => 1, 'nome' => 'Compras', 'tipo' => 'despesa'],
['id' => 2, 'nome' => 'Contas', 'tipo' => 'receita']
];

$nomeCategoriaExiste = function ($nome, $idIgnorado = null) use (&$categoria) {
    $nome = strtolower(trim((string) $nome));
    foreach ($categoria as $item) {
        if ($item['id'] !== $idIgnorado && strtolower(trim($item['nome'])) === $nome) {
            return true;
        }
    }
    return false;
};
 
$app->get('/categoria', function ($request, $response) use (&$categoria) {
    $response->getBody()->write(json_encode($categoria));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});
 
$app->get('/categoria/{id}', function ($request, $response, $args) use (&$categoria) {
    $categoriaEncontrada = current(array_filter(
        $categoria,
        fn ($item) => $item['id'] === (int) $args['id']
    )) ?: null;
    if (!$categoriaEncontrada) {
        $response->getBody()->write(json_encode(['erro' => 'Categoria não encontrada']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $response->getBody()->write(json_encode($categoriaEncontrada));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});
 
// Cadastra uma categoria.
$app->post('/categoria', function ($request, $response) use (&$categoria, $nomeCategoriaExiste) {
    $dados = $request->getParsedBody();
    if (!is_array($dados) || empty(trim((string) ($dados['nome'] ?? '')))) {
        $response->getBody()->write(json_encode(['erro' => 'O nome da categoria é obrigatório']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }
    if (!in_array($dados['tipo'] ?? null, ['receita', 'despesa'], true)) {
        $response->getBody()->write(json_encode(['erro' => 'O tipo da categoria deve ser receita ou despesa']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }
    if ($nomeCategoriaExiste($dados['nome'])) {
        $response->getBody()->write(json_encode(['erro' => 'Já existe uma categoria com esse nome']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $novoCategoria = ['id' => count($categoria) + 1, 'nome' => trim($dados['nome']), 'tipo' => $dados['tipo']];
    $categoria[] = $novoCategoria;
    $response->getBody()->write(json_encode($novoCategoria));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});
 
// Atualiza uma categoria.
$app->put('/categoria/{id}', function ($request, $response, $args) use (&$categoria, $nomeCategoriaExiste) {
    $dados = $request->getParsedBody();
    if (!is_array($dados) || empty(trim((string) ($dados['nome'] ?? '')))) {
        $response->getBody()->write(json_encode(['erro' => 'O nome da categoria é obrigatório']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }
    if (!in_array($dados['tipo'] ?? null, ['receita', 'despesa'], true)) {
        $response->getBody()->write(json_encode(['erro' => 'O tipo da categoria deve ser receita ou despesa']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }

    foreach ($categoria as &$item) {
        if ($item['id'] === (int) $args['id']) {
            if ($nomeCategoriaExiste($dados['nome'], $item['id'])) {
                $response->getBody()->write(json_encode(['erro' => 'Já existe uma categoria com esse nome']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $item['nome'] = trim($dados['nome']);
            $item['tipo'] = $dados['tipo'];
            $response->getBody()->write(json_encode($item));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    }

    $response->getBody()->write(json_encode(['erro' => 'Categoria não encontrada']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
});

// Exclui uma categoria.
$app->delete('/categoria/{id}', function ($request, $response, $args) use (&$categoria) {
    $existe = false;
    foreach ($categoria as $item) {
        if ($item['id'] === (int) $args['id']) {
            $existe = true;
            break;
        }
    }
    if (!$existe) {
        $response->getBody()->write(json_encode(['erro' => 'Categoria não encontrada']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $categoria = array_values(array_filter($categoria, fn($p) => $p['id'] !== (int) $args['id']));
    $response->getBody()->write(json_encode(['mensagem' => 'Categoria excluída']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$lancamentos = [
    ['id' => 1, 'descricao' => 'Salário', 'valor' => 5000, 'data' => '2023-01-01', 'tipo' => 'receita', 'categoria' => 2],
    ['id' => 2, 'descricao' => 'Aluguel', 'valor' => 1500, 'data' => '2023-01-05', 'tipo' => 'despesa', 'categoria' => 1],
];

// Valida os dados do lançamento

$validarLancamento = function ($dados) use (&$categoria) {
    if (!is_array($dados)) {
        return 'Envie os dados do lançamento em formato JSON.';
    }

    foreach (['descricao', 'valor', 'data', 'tipo', 'categoria'] as $campo) {
        if (!isset($dados[$campo]) || $dados[$campo] === '') {
            return 'Todos os campos do lançamento são obrigatórios.';
        }
    }

    if (!is_numeric($dados['valor']) || $dados['valor'] <= 0) {
        return 'O valor deve ser maior que zero.';
    }

    if (!is_string($dados['data']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data'])) {
        return 'A data é obrigatória e deve estar no formato YYYY-MM-DD.';
    }
    [$ano, $mes, $dia] = array_map('intval', explode('-', $dados['data']));
    if (!checkdate($mes, $dia, $ano)) {
        return 'A data informada não é válida.';
    }

    if (!in_array($dados['tipo'], ['receita', 'despesa'], true)) {
        return 'O tipo deve ser receita ou despesa.';
    }

    $categoriaId = filter_var($dados['categoria'], FILTER_VALIDATE_INT);
    if ($categoriaId === false) {
        return 'A categoria informada não existe.';
    }

    $categoriaEncontrada = current(array_filter(
        $categoria,
        fn ($item) => $item['id'] === $categoriaId
    )) ?: null;
    if (!$categoriaEncontrada) {
        return 'A categoria informada não existe.';
    }
    if ($categoriaEncontrada['tipo'] !== $dados['tipo']) {
        return 'O tipo do lançamento deve ser compatível com a categoria.';
    }

    return null;
};

// Cadastra um lançamento
$app->post('/lancamento', function ($request, $response) use (&$lancamentos, $validarLancamento) {
    $dados = $request->getParsedBody();
    $erro = $validarLancamento($dados);
    if ($erro !== null) {
        $response->getBody()->write(json_encode(['erro' => $erro]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }

    $novoLancamento = [
        'id' => count($lancamentos) + 1,
        'descricao' => $dados['descricao'],
        'valor' => (float) $dados['valor'],
        'data' => $dados['data'],
        'tipo' => $dados['tipo'],
        'categoria' => (int) $dados['categoria'],
    ];
    $lancamentos[] = $novoLancamento;

    $response->getBody()->write(json_encode($novoLancamento));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

// Lista todos os lançamentos
$app->get('/lancamentos', function ($request, $response) use (&$lancamentos) {
    $filtros = $request->getQueryParams();
    $resultado = array_values(array_filter($lancamentos, function ($item) use ($filtros) {
        if (isset($filtros['dataInicial']) && $item['data'] < $filtros['dataInicial']) {
            return false;
        }
        if (isset($filtros['dataFinal']) && $item['data'] > $filtros['dataFinal']) {
            return false;
        }
        if (isset($filtros['categoria']) && (string) $item['categoria'] !== (string) $filtros['categoria']) {
            return false;
        }
        if (isset($filtros['tipo']) && $item['tipo'] !== $filtros['tipo']) {
            return false;
        }
        return true;
    }));

    $response->getBody()->write(json_encode($resultado));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});

// Consulta o saldo atual
$app->get('/saldo', function ($request, $response) use (&$lancamentos) {
    $receitas = 0;
    $despesas = 0;
    foreach ($lancamentos as $item) {
        if ($item['tipo'] === 'receita') {
            $receitas += (float) $item['valor'];
        } else {
            $despesas += (float) $item['valor'];
        }
    }

    $response->getBody()->write(json_encode(['saldo' => $receitas - $despesas]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// Consulta receitas e despesas de um período
$app->get('/totais', function ($request, $response) use (&$lancamentos) {
    $filtros = $request->getQueryParams();
    $receitas = 0;
    $despesas = 0;
    foreach ($lancamentos as $item) {
        if (isset($filtros['dataInicial']) && $item['data'] < $filtros['dataInicial']) {
            continue;
        }
        if (isset($filtros['dataFinal']) && $item['data'] > $filtros['dataFinal']) {
            continue;
        }

        if ($item['tipo'] === 'receita') {
            $receitas += (float) $item['valor'];
        } else {
            $despesas += (float) $item['valor'];
        }
    }

    $response->getBody()->write(json_encode([
        'totalReceitas' => $receitas,
        'totalDespesas' => $despesas,
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// Consulta um lançamento 
$app->get('/lancamento/{id}', function ($request, $response, $args) use (&$lancamentos) {
    $lancamento = current(array_filter($lancamentos, fn ($item) => $item['id'] === (int) $args['id'])) ?: null;
    if (!$lancamento) {
        $response->getBody()->write(json_encode(['erro' => 'Lançamento não encontrado']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $response->getBody()->write(json_encode($lancamento));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// Atualiza um lançamento
$app->put('/lancamento/{id}', function ($request, $response, $args) use (&$lancamentos, $validarLancamento) {
    $dados = $request->getParsedBody();
    foreach ($lancamentos as &$item) {
        if ($item['id'] === (int) $args['id']) {
            $erro = $validarLancamento($dados);
            if ($erro !== null) {
                $response->getBody()->write(json_encode(['erro' => $erro]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
            }

            $item['descricao'] = $dados['descricao'];
            $item['valor'] = (float) $dados['valor'];
            $item['data'] = $dados['data'];
            $item['tipo'] = $dados['tipo'];
            $item['categoria'] = (int) $dados['categoria'];

            $response->getBody()->write(json_encode($item));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    }
    $response->getBody()->write(json_encode(['erro' => 'Lançamento não encontrado']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
});

// Exclui um lançamento
$app->delete('/lancamento/{id}', function ($request, $response, $args) use (&$lancamentos) {
    $existe = false;
    foreach ($lancamentos as $item) {
        if ($item['id'] === (int) $args['id']) {
            $existe = true;
            break;
        }
    }
    if (!$existe) {
        $response->getBody()->write(json_encode(['erro' => 'Lançamento não encontrado']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $lancamentos = array_values(array_filter(
        $lancamentos,
        fn ($item) => $item['id'] !== (int) $args['id']
    ));
    $response->getBody()->write(json_encode(['mensagem' => 'Lançamento excluído']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});
