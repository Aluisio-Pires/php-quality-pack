<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Mcp;

use Aluisio\PhpQualityPack\Application;
use Aluisio\PhpQualityPack\Console\Output;
use Aluisio\PhpQualityPack\Support\Json;
use InvalidArgumentException;
use Throwable;

final class Server
{
    public const PROTOCOL_VERSIONS = ['2025-11-25', '2025-06-18', '2025-03-26', '2024-11-05'];

    private const PARSE_ERROR = -32700;

    private const INVALID_REQUEST = -32600;

    private const METHOD_NOT_FOUND = -32601;

    private const INVALID_PARAMS = -32602;

    private const INSTRUCTIONS = 'PHP code quality tools for this project. Call `detect` first. Use `fix` for safe automatic fixes, `analyse` for type errors, `test` for Pest and `check` before finishing a task. `refactor` and `upgrade` are dry runs unless apply=true. Never lower the PHPStan level or edit the baseline without asking the user.';

    private Application $application;

    private ToolCatalog $tools;

    private PromptCatalog $prompts;

    private ResourceCatalog $resources;

    public function __construct(Application $application, ?ToolCatalog $tools = null, ?PromptCatalog $prompts = null, ?ResourceCatalog $resources = null)
    {
        $this->application = $application;
        $this->tools = $tools ?? new ToolCatalog;
        $this->prompts = $prompts ?? new PromptCatalog;
        $this->resources = $resources ?? new ResourceCatalog;
    }

    /**
     * @param  resource  $input
     * @param  resource  $output
     */
    public function listen($input, $output): void
    {
        while (($line = fgets($input)) !== false) {
            if (trim($line) === '') {
                continue;
            }

            $response = $this->respond($line);

            if ($response !== null) {
                fwrite($output, $response."\n");
                fflush($output);
            }
        }
    }

    public function respond(string $line): ?string
    {
        $message = json_decode($line, true);

        if (! is_array($message)) {
            return Json::encode($this->error(null, self::PARSE_ERROR, 'Parse error.'));
        }

        if (array_keys($message) === range(0, count($message) - 1)) {
            $responses = array_values(array_filter(array_map(
                fn ($item): ?array => is_array($item) ? $this->handle($item) : $this->error(null, self::INVALID_REQUEST, 'Invalid request.'),
                $message,
            )));

            return $responses === [] ? null : Json::encode($responses);
        }

        $response = $this->handle($message);

        return $response === null ? null : Json::encode($response);
    }

    /**
     * @param  array<mixed>  $message
     * @return array<string, mixed>|null
     */
    public function handle(array $message): ?array
    {
        $id = $message['id'] ?? null;
        $method = $message['method'] ?? null;
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];

        if (! is_string($method)) {
            return $id === null ? null : $this->error($id, self::INVALID_REQUEST, 'Invalid request.');
        }

        if ($id === null) {
            return null;
        }

        try {
            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $this->dispatch($method, $params)];
        } catch (UnknownMethod $exception) {
            return $this->error($id, self::METHOD_NOT_FOUND, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return $this->error($id, self::INVALID_PARAMS, $exception->getMessage());
        }
    }

    /**
     * @param  array<mixed>  $params
     * @return array<string, mixed>|object
     */
    private function dispatch(string $method, array $params)
    {
        switch ($method) {
            case 'initialize':
                return $this->initialize($params);
            case 'ping':
                return (object) [];
            case 'tools/list':
                return ['tools' => $this->tools->definitions()];
            case 'tools/call':
                return $this->callTool($params);
            case 'prompts/list':
                return ['prompts' => $this->prompts->definitions()];
            case 'prompts/get':
                return $this->prompts->get($this->stringParam($params, 'name'), $this->arrayParam($params, 'arguments'));
            case 'resources/list':
                return ['resources' => $this->resources->definitions()];
            case 'resources/read':
                return $this->resources->read($this->stringParam($params, 'uri'));
            default:
                throw new UnknownMethod(sprintf('Method [%s] not found.', $method));
        }
    }

    /**
     * @param  array<mixed>  $params
     * @return array<string, mixed>
     */
    private function initialize(array $params): array
    {
        $requested = $params['protocolVersion'] ?? null;

        return [
            'protocolVersion' => in_array($requested, self::PROTOCOL_VERSIONS, true) ? $requested : self::PROTOCOL_VERSIONS[0],
            'capabilities' => [
                'tools' => ['listChanged' => false],
                'prompts' => ['listChanged' => false],
                'resources' => ['listChanged' => false],
            ],
            'serverInfo' => [
                'name' => 'pqp',
                'title' => 'PHP Quality Pack',
                'version' => Application::VERSION,
            ],
            'instructions' => self::INSTRUCTIONS,
        ];
    }

    /**
     * @param  array<mixed>  $params
     * @return array<string, mixed>
     */
    private function callTool(array $params): array
    {
        $input = $this->tools->input($this->stringParam($params, 'name'), $this->arrayParam($params, 'arguments'));
        $stream = fopen('php://memory', 'w+b');

        if ($stream === false) {
            return $this->toolResult('Unable to capture the tool output.', true);
        }

        try {
            $exitCode = $this->application->call($input, new Output($stream, false));
        } catch (Throwable $throwable) {
            return $this->toolResult($throwable->getMessage(), true);
        }

        rewind($stream);
        $text = trim((string) stream_get_contents($stream));
        fclose($stream);

        return $this->toolResult($text, $exitCode !== 0 && Json::tryDecode($text) === null);
    }

    /**
     * @return array<string, mixed>
     */
    private function toolResult(string $text, bool $isError): array
    {
        return [
            'content' => [['type' => 'text', 'text' => $text]],
            'isError' => $isError,
        ];
    }

    /**
     * @param  array<mixed>  $params
     */
    private function stringParam(array $params, string $key): string
    {
        $value = $params[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException(sprintf('The [%s] parameter is required.', $key));
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $params
     * @return array<mixed>
     */
    private function arrayParam(array $params, string $key): array
    {
        $value = $params[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * @param  mixed  $id
     * @return array<string, mixed>
     */
    private function error($id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
