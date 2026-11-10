<?php
require __DIR__ . '/../src/app.php';

use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

$requestFactory = new ServerRequestFactory();
$streamFactory = new StreamFactory();
$testes = 0;

$enviar = function ($metodo, $rota, $dados = null) use ($app, $requestFactory, $streamFactory) {
    $request = $requestFactory->createServerRequest($metodo, $rota);
    if ($dados !== null) {
        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($streamFactory->createStream(json_encode($dados)));
    }
    return $app->handle($request);
};

$verificar = function ($nome, $response, $status, $conteudoValido) use (&$testes) {
    $testes++;
    $body = (string) $response->getBody();
    $json = json_decode($body, true);

    if (
        $response->getStatusCode() !== $status
        || $response->getHeaderLine('Content-Type') !== 'application/json'
        || json_last_error() !== JSON_ERROR_NONE
        || !$conteudoValido($json)
    ) {
        fwrite(STDERR, "FALHOU: {$nome}\nStatus: {$response->getStatusCode()}\nResposta: {$body}\n");
        exit(1);
    }

    echo "OK: {$nome}\n";
};

$verificar('GET /status', $enviar('GET', '/status'), 200, fn ($json) => ($json['status'] ?? null) === 'ok');
$verificar('rota desconhecida retorna erro JSON 404', $enviar('GET', '/rota-inexistente'), 404, fn ($json) => isset($json['erro']));
$verificar('método HTTP incorreto retorna erro JSON 405', $enviar('POST', '/status'), 405, fn ($json) => isset($json['erro']));

$verificar('GET /categoria lista as categorias', $enviar('GET', '/categoria'), 200, fn ($json) => count($json) === 2);
$verificar('GET /categoria/{id} encontra categoria', $enviar('GET', '/categoria/1'), 200, fn ($json) => ($json['nome'] ?? null) === 'Compras');
$verificar('GET /categoria/{id} retorna 404', $enviar('GET', '/categoria/999'), 404, fn ($json) => isset($json['erro']));

$verificar('POST /categoria cria categoria', $enviar('POST', '/categoria', ['nome' => 'Transporte', 'tipo' => 'despesa']), 201, fn ($json) => ($json['id'] ?? null) === 3);
$verificar('POST /categoria rejeita nome duplicado', $enviar('POST', '/categoria', ['nome' => ' compras ', 'tipo' => 'despesa']), 400, fn ($json) => isset($json['erro']));
$verificar('POST /categoria exige campos', $enviar('POST', '/categoria', ['nome' => '']), 422, fn ($json) => isset($json['erro']));
$verificar('POST /categoria rejeita tipo inválido', $enviar('POST', '/categoria', ['nome' => 'Tipo errado', 'tipo' => 'outro']), 422, fn ($json) => isset($json['erro']));

$verificar('PUT /categoria/{id} atualiza categoria', $enviar('PUT', '/categoria/3', ['nome' => 'Mobilidade', 'tipo' => 'despesa']), 200, fn ($json) => ($json['nome'] ?? null) === 'Mobilidade');
$verificar('PUT /categoria/{id} rejeita nome duplicado', $enviar('PUT', '/categoria/3', ['nome' => 'Contas', 'tipo' => 'despesa']), 400, fn ($json) => isset($json['erro']));
$verificar('PUT /categoria/{id} retorna 404', $enviar('PUT', '/categoria/999', ['nome' => 'Outra', 'tipo' => 'despesa']), 404, fn ($json) => isset($json['erro']));
$verificar('DELETE /categoria/{id} exclui categoria', $enviar('DELETE', '/categoria/3'), 200, fn ($json) => isset($json['mensagem']));
$verificar('DELETE /categoria/{id} retorna 404', $enviar('DELETE', '/categoria/999'), 404, fn ($json) => isset($json['erro']));

$lancamento = [
    'descricao' => 'Salário de teste',
    'valor' => 1000,
    'data' => '2026-11-10',
    'tipo' => 'receita',
    'categoria' => 2,
];

$verificar('POST /lancamento cria lançamento', $enviar('POST', '/lancamento', $lancamento), 201, fn ($json) => ($json['id'] ?? null) === 3);
$verificar('POST /lancamento rejeita valor inválido', $enviar('POST', '/lancamento', array_merge($lancamento, ['valor' => 0])), 422, fn ($json) => isset($json['erro']));
$verificar('POST /lancamento rejeita data ausente', $enviar('POST', '/lancamento', array_diff_key($lancamento, ['data' => true])), 422, fn ($json) => isset($json['erro']));
$verificar('POST /lancamento rejeita data inválida', $enviar('POST', '/lancamento', array_merge($lancamento, ['data' => '2026-02-30'])), 422, fn ($json) => isset($json['erro']));
$verificar('POST /lancamento rejeita categoria inexistente', $enviar('POST', '/lancamento', array_merge($lancamento, ['categoria' => 999])), 422, fn ($json) => isset($json['erro']));
$verificar('POST /lancamento rejeita tipo incompatível', $enviar('POST', '/lancamento', array_merge($lancamento, ['tipo' => 'despesa'])), 422, fn ($json) => isset($json['erro']));
$jsonInvalido = $requestFactory->createServerRequest('POST', '/lancamento')
    ->withHeader('Content-Type', 'application/json')
    ->withBody($streamFactory->createStream('{json inválido'));
$verificar('POST /lancamento retorna erro JSON para corpo inválido', $app->handle($jsonInvalido), 422, fn ($json) => isset($json['erro']));

$verificar('GET /lancamentos lista lançamentos', $enviar('GET', '/lancamentos'), 200, fn ($json) => count($json) === 3);
$verificar('GET /lancamentos filtra por período', $enviar('GET', '/lancamentos?dataInicial=2023-01-03&dataFinal=2023-01-31'), 200, fn ($json) => count($json) === 1 && ($json[0]['descricao'] ?? null) === 'Aluguel');
$verificar('GET /lancamentos filtra por categoria', $enviar('GET', '/lancamentos?categoria=1'), 200, fn ($json) => count($json) === 1 && ($json[0]['categoria'] ?? null) === 1);
$verificar('GET /lancamentos filtra por tipo', $enviar('GET', '/lancamentos?tipo=receita'), 200, fn ($json) => count($json) === 2 && $json[0]['tipo'] === 'receita' && $json[1]['tipo'] === 'receita');

$verificar('GET /lancamento/{id} encontra lançamento', $enviar('GET', '/lancamento/3'), 200, fn ($json) => ($json['descricao'] ?? null) === 'Salário de teste');
$verificar('GET /lancamento/{id} retorna 404', $enviar('GET', '/lancamento/999'), 404, fn ($json) => isset($json['erro']));
$verificar('GET /saldo retorna saldo', $enviar('GET', '/saldo'), 200, fn ($json) => (float) ($json['saldo'] ?? 0) === 4500.0);
$verificar('GET /totais retorna totais do período', $enviar('GET', '/totais?dataInicial=2023-01-01&dataFinal=2023-12-31'), 200, fn ($json) => ($json['totalReceitas'] ?? null) === 5000 && ($json['totalDespesas'] ?? null) === 1500);

$atualizacao = array_merge($lancamento, ['descricao' => 'Salário atualizado', 'valor' => 1200]);
$verificar('PUT /lancamento/{id} atualiza lançamento', $enviar('PUT', '/lancamento/3', $atualizacao), 200, fn ($json) => ($json['descricao'] ?? null) === 'Salário atualizado' && (float) ($json['valor'] ?? 0) === 1200.0);
$verificar('PUT /lancamento/{id} rejeita valor inválido', $enviar('PUT', '/lancamento/3', array_merge($atualizacao, ['valor' => 0])), 422, fn ($json) => isset($json['erro']));
$verificar('PUT /lancamento/{id} retorna 404', $enviar('PUT', '/lancamento/999', $atualizacao), 404, fn ($json) => isset($json['erro']));
$verificar('DELETE /lancamento/{id} exclui lançamento', $enviar('DELETE', '/lancamento/3'), 200, fn ($json) => isset($json['mensagem']));
$verificar('DELETE /lancamento/{id} retorna 404', $enviar('DELETE', '/lancamento/999'), 404, fn ($json) => isset($json['erro']));

fwrite(STDOUT, "\n{$testes} testes passaram.\n");
