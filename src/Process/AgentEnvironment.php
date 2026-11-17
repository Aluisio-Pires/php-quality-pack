<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Process;

final class AgentEnvironment
{
    public const OVERRIDES = [
        'NO_COLOR' => '1',
        'TERM' => 'dumb',
        'COLUMNS' => '120',
        'PAO_DISABLE' => '1',
    ];

    private const VARIABLES = [
        'AI_AGENT',
        'AMP_CURRENT_THREAD_ID',
        'CLAUDECODE',
        'CLINE_ACTIVE',
        'CRUSH',
        'GEMINI_CLI',
        'GOOSE_TERMINAL',
        'OPENCODE',
        'PI_CODING_AGENT',
        'QWEN_CODE',
        'REPL_ID',
        'WINDSURF_CASCADE_TERMINAL',
        'ZCODE_ENV',
    ];

    private const PREFIXES = [
        'ANTIGRAVITY_',
        'AUGMENT_AGENT',
        'CLAUDE_CODE',
        'CODEX_',
        'COPILOT_',
        'CURSOR_',
        'GROK_PLUGIN_',
        'JUNIE_',
        'KIMI_PLUGIN_',
        'KIRO_AGENT_',
        'MATTERHORN_',
        'OPENCLAW_',
        'OPENCODE_',
    ];

    public static function isAgentVariable(string $name): bool
    {
        if (in_array($name, self::VARIABLES, true)) {
            return true;
        }

        foreach (self::PREFIXES as $prefix) {
            if (strpos($name, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }
}
