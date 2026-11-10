<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;

function respostaJson($response, $dados, $status = 200)
{
    $response->getBody()->write(json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
}

function erroJson($response, $mensagem, $status)
{
    return respostaJson($response, ['erro' => $mensagem], $status);
}

function lerJson($request)
{
    $dados = json_decode((string) $request->getBody(), true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
        return null;
    }

    return $dados;
}

function idValido($id)
{
    return ctype_digit((string) $id) && (int) $id > 0;
}

function criarAplicacao()
{
    $dados = [
        'categorias' => [],
        'lancamentos' => [],
        'proximoIdCategoria' => 1,
        'proximoIdLancamento' => 1
    ];
    $app = AppFactory::create();

    $app->get('/categorias', function ($request, $response) use (&$dados) {
        return respostaJson($response, $dados['categorias']);
    });

    $app->get('/categorias/{id}', function ($request, $response, $args) use (&$dados) {
        if (!idValido($args['id'])) {
            return erroJson($response, 'O ID informado é inválido.', 400);
        }

        foreach ($dados['categorias'] as $categoria) {
            if ($categoria['id'] === (int) $args['id']) {
                return respostaJson($response, $categoria);
            }
        }

        return erroJson($response, 'Categoria não encontrada.', 404);
    });

    $app->post('/categorias', function ($request, $response) use (&$dados) {
        $entrada = lerJson($request);
        if ($entrada === null) {
            return erroJson($response, 'O corpo da requisição deve ser um JSON válido.', 400);
        }

        $nome = isset($entrada['nome']) && is_string($entrada['nome']) ? trim($entrada['nome']) : '';
        $tipo = $entrada['tipo'] ?? '';
        if ($nome === '' || !in_array($tipo, ['receita', 'despesa'], true)) {
            return erroJson($response, 'Informe um nome e um tipo válido: receita ou despesa.', 422);
        }

        foreach ($dados['categorias'] as $categoria) {
            if (strtolower($categoria['nome']) === strtolower($nome)) {
                return erroJson($response, 'Já existe uma categoria com esse nome.', 422);
            }
        }

        $categoria = [
            'id' => $dados['proximoIdCategoria'],
            'nome' => $nome,
            'tipo' => $tipo
        ];
        $dados['categorias'][] = $categoria;
        $dados['proximoIdCategoria']++;
        return respostaJson($response, $categoria, 201);
    });

    $app->put('/categorias/{id}', function ($request, $response, $args) use (&$dados) {
        if (!idValido($args['id'])) {
            return erroJson($response, 'O ID informado é inválido.', 400);
        }

        $entrada = lerJson($request);
        if ($entrada === null) {
            return erroJson($response, 'O corpo da requisição deve ser um JSON válido.', 400);
        }

        $nome = isset($entrada['nome']) && is_string($entrada['nome']) ? trim($entrada['nome']) : '';
        $tipo = $entrada['tipo'] ?? '';
        if ($nome === '' || !in_array($tipo, ['receita', 'despesa'], true)) {
            return erroJson($response, 'Informe um nome e um tipo válido: receita ou despesa.', 422);
        }

        $id = (int) $args['id'];
        $posicao = null;
        foreach ($dados['categorias'] as $i => $categoria) {
            if ($categoria['id'] === $id) {
                $posicao = $i;
            } elseif (strtolower($categoria['nome']) === strtolower($nome)) {
                return erroJson($response, 'Já existe uma categoria com esse nome.', 422);
            }
        }

        if ($posicao === null) {
            return erroJson($response, 'Categoria não encontrada.', 404);
        }

        foreach ($dados['lancamentos'] as $lancamento) {
            if ($lancamento['categoriaId'] === $id && $lancamento['tipo'] !== $tipo) {
                return erroJson($response, 'O tipo não pode ser alterado porque existem lançamentos vinculados.', 422);
            }
        }

        $dados['categorias'][$posicao]['nome'] = $nome;
        $dados['categorias'][$posicao]['tipo'] = $tipo;
        return respostaJson($response, $dados['categorias'][$posicao]);
    });

    $app->delete('/categorias/{id}', function ($request, $response, $args) use (&$dados) {
        if (!idValido($args['id'])) {
            return erroJson($response, 'O ID informado é inválido.', 400);
        }

        $id = (int) $args['id'];
        $posicao = null;
        foreach ($dados['categorias'] as $i => $categoria) {
            if ($categoria['id'] === $id) {
                $posicao = $i;
                break;
            }
        }

        if ($posicao === null) {
            return erroJson($response, 'Categoria não encontrada.', 404);
        }

        foreach ($dados['lancamentos'] as $lancamento) {
            if ($lancamento['categoriaId'] === $id) {
                return erroJson($response, 'Não é possível excluir uma categoria que possui lançamentos.', 422);
            }
        }

        array_splice($dados['categorias'], $posicao, 1);
        return respostaJson($response, ['mensagem' => 'Categoria excluída com sucesso.']);
    });

    $errorMiddleware = $app->addErrorMiddleware(false, false, false);
    $errorMiddleware->setDefaultErrorHandler(function ($request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails) use ($app) {
        $status = 500;
        $mensagem = 'Ocorreu um erro interno.';

        if ($exception instanceof HttpNotFoundException) {
            $status = 404;
            $mensagem = 'Endpoint não encontrado.';
        } elseif ($exception instanceof HttpMethodNotAllowedException) {
            $status = 405;
            $mensagem = 'Método HTTP não permitido para este endpoint.';
        }

        return erroJson($app->getResponseFactory()->createResponse(), $mensagem, $status);
    });

    return $app;
}