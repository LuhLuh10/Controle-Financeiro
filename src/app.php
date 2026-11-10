<?php
require __DIR__ . '/../vendor/autoload.php';
 
use Slim\Factory\AppFactory;
 
$app = AppFactory::create();
 
// status
$app->get('/status', function ($request, $response) {
    $response->getBody()->write(json_encode(['status' => 'ok']));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});
 
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$categoria = [
['id' => 1, 'nome' => 'Compras', 'tipo' => 'despesa'],
['id' => 2, 'nome' => 'Contas', 'tipo' => 'receita']
];
 
// Get
$app->get('/categoria', function ($request, $response) use (&$categoria) {
    $response->getBody()->write(json_encode($categoria));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});
 
// Get com filtro
$app->get('/categoria/{id}', function ($request, $response, $args) use (&$categoria) {
$categoria = current(array_filter($categoria, fn ($p) => $p['id'] === (int) $args['id'])) ?: null;
if (!$categoria) {
$response->getBody()->write(json_encode(['erro' => 'Categoria não encontrada']));
return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
}

$response->getBody()->write(json_encode($categoria));
return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});
 
// POST
$app->post('/categoria', function ($request, $response) use (&$categoria) {
    $dados = $request->getParsedBody();
    $novoCategoria = ['id' => count($categoria) + 1, 'nome' => $dados['nome'], 'tipo' => $dados['tipo']];
    $categoria[] = $novoCategoria;
    $response->getBody()->write(json_encode($novoCategoria));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});
 
//PUT
$app->put('/categoria/{id}', function ($request, $response, $args) use (&$categoria) {
$dados = $request->getParsedBody();
foreach ($categoria as &$item) {
if ($item['id'] === (int) $args['id']) {
$item['nome'] = $dados['nome'];
$item['tipo'] = $dados['tipo'];
$response->getBody()->write(json_encode($item));
return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
}
}
return $response->withStatus(404);
});

//DELETE
$app->delete('/categoria/{id}', function ($request, $response, $args) use (&$categoria) {
    $categoria = array_values(array_filter($categoria, fn($p) => $p['id'] !== (int) $args['id']));
    return $response->withStatus(204);
});

$lancamentos = [];

// Cadastra um lançamento
$app->post('/lancamento', function ($request, $response) use (&$lancamentos) {
    $dados = $request->getParsedBody();
    $novoLancamento = [
        'id' => count($lancamentos) + 1,
        'descricao' => $dados['descricao'],
        'valor' => $dados['valor'],
        'data' => $dados['data'],
        'tipo' => $dados['tipo'],
        'categoria' => $dados['categoria'],
    ];
    $lancamentos[] = $novoLancamento;

    $response->getBody()->write(json_encode($novoLancamento));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

// Lista todos os lançamentos
$app->get('/lancamento', function ($request, $response) use (&$lancamentos) {
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
        return true;
    }));

    $response->getBody()->write(json_encode($resultado));
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
$app->put('/lancamento/{id}', function ($request, $response, $args) use (&$lancamentos) {
    $dados = $request->getParsedBody();
    foreach ($lancamentos as &$item) {
        if ($item['id'] === (int) $args['id']) {
            $item['descricao'] = $dados['descricao'];
            $item['valor'] = $dados['valor'];
            $item['data'] = $dados['data'];
            $item['tipo'] = $dados['tipo'];
            $item['categoria'] = $dados['categoria'];

            $response->getBody()->write(json_encode($item));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    }
    return $response->withStatus(404);
});

// Exclui um lançamento
$app->delete('/lancamento/{id}', function ($request, $response, $args) use (&$lancamentos) {
    $lancamentos = array_values(array_filter(
        $lancamentos,
        fn ($item) => $item['id'] !== (int) $args['id']
    ));
    return $response->withStatus(204);
});

$app->run();