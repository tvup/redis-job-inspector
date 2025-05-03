<?php
namespace RedisJobInspector;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class   RedisJobQueryBuilder
{
    protected string $queue = 'default';
    protected ?string $state = null;

    protected ?string $jobClass = null;
    protected array $extractProps = [];

    protected array $filters = [];

    public function inQueue(string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function withJobState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function withJobClass(string $class): static
    {
        $this->jobClass = $class;

        if (empty($props)) {
            $reflection = new \ReflectionClass($class);
            $props = [];

            foreach ($reflection->getProperties(
                \ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC
            ) as $prop) {
                $props[] = $prop->getName();
            }
        }

        $this->extractProps = $props;

        return $this;
    }

    public function where(string $field, string $operator, mixed $value): static
    {
        if ($field === 'job_state' && $operator === '=') {
            $this->withJobState($value);
        }
        $this->filters[] = compact('field', 'operator', 'value');

        return $this;
    }

    public function reset(): static
    {
        $this->filters = [];
        $this->state = null;
        $this->queue = 'default';

        return $this;
    }

    public function get(): Collection
    {
        $jobs = new Collection();

        $fetchScheduled = $this->state === 'Scheduled' || $this->state === null;
        $fetchRunning = $this->state === 'Running' || $this->state === null;

        if ($fetchScheduled) {
            $rawJobs = Redis::zrange("queues:{$this->queue}:delayed", 0, -1, ['withscores' => true]);

            $rawJobsCollection = new Collection($rawJobs);

            $jobs = $rawJobsCollection->map(function ($score, $raw) {
                if (!$this->jobClass || empty($this->extractProps)) {
                    $this->inferJobClassAndProps($raw);
                }

                return RedisJobInspector::parseRawJob(
                    $raw,
                    $score,
                    'Scheduled',
                    $this->jobClass,
                    $this->extractProps
                );
            });
        }

        if ($fetchRunning) {
            $rawJobs = Redis::lrange("queues:{$this->queue}", 0, -1);

            $rawJobsCollection = new Collection($rawJobs);
            $jobs = $jobs->merge($rawJobsCollection->map(function ($raw) {
                if (!$this->jobClass || empty($this->extractProps)) {
                    $this->inferJobClassAndProps($raw);
                }

                return RedisJobInspector::parseRawJob(
                    $raw,
                    null,
                    'Running',
                    $this->jobClass,
                    $this->extractProps
                );

            }));
        }

        return $jobs
            ->filter(function ($job) {
                foreach ($this->filters as $filter) {
                    ['field' => $field, 'operator' => $operator, 'value' => $value] = $filter;
                    $actual = $job[$field] ?? null;

                    if (is_null($actual)) {
                        return false;
                    }

                    switch ($operator) {
                        case '=':
                            if (strcasecmp((string) $actual, (string) $value) !== 0) {
                                return false;
                            }
                            break;

                        case 'like':
                            $pattern = '/' . str_replace('%', '.*', preg_quote($value, '/')) . '/i';
                            if (!preg_match($pattern, $actual)) {
                                return false;
                            }
                            break;

                        default:
                            return false;
                    }
                }

                return true;
            })

            ->values();
    }

    protected function inferJobClassAndProps(string $raw): void
    {
        $data = json_decode($raw, true);
        $commandString = $data['data']['command'] ?? null;

        if ($commandString) {
            try {
                $rawCommand = unserialize($commandString);
                $class = get_class($rawCommand);
                $this->jobClass = $class;

                $reflection = new \ReflectionClass($class);

                $propertyCollection = new Collection($reflection->getProperties(
                    \ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC
                ));

                $this->extractProps = $propertyCollection->map(fn ($p) => $p->getName())->all();
            } catch (\Throwable $e) {
                Log::warning('Could not infer job class', ['error' => $e->getMessage()]);
            }
        }
    }
}
