<?php
namespace RedisJobInspector;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RedisJobInspector\Support\Prop;

class RedisJobInspector
{
    public static function parseRawJob(
        string $rawPayload,
        ?int $score = null,
        string $state = 'Running',
        ?string $jobClass = null,
        array $extractProps = []
    ): ?array {
        $data = json_decode($rawPayload, true);

        $commandProps = [];
        $extras = [];

        try {
            $commandString = $data['data']['command'] ?? null;
            $displayName = $data['displayName'] ?? ($commandString ? get_class($commandString) : null);

            if ($commandString) {
                $rawCommand = unserialize($commandString);
                $props = (array) $rawCommand;

                foreach ($extractProps as $propName) {
                    $extras[$propName] = Prop::get($props, $propName, $jobClass);
                }

                $commandProps['delay'] ??= $props['delay'] ?? $score;
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to unserialize Redis job payload', ['error' => $e->getMessage()]);

            return null;
        }

        return array_merge($extras, [
            'job_state'   => $state,
            'displayName' => $displayName,
            'command'     => $commandProps,
            'delay_to'    => $commandProps['delay'] ?? null,
            'pushed_at'   => isset($data['pushedAt']) ? Carbon::createFromTimestampMs((int) ($data['pushedAt'] * 1000)) : null,
            'attempts'    => $data['attempts'] ?? null,
            'max_tries'   => $data['maxTries'] ?? null,
        ]);
    }
}
