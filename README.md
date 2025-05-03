# Redis Job Inspector

Inspect Laravel Redis queue jobs using an Eloquent-like query builder.

[![Packagist Version](https://img.shields.io/packagist/v/tvup/redis-job-inspector)](https://packagist.org/packages/tvup/redis-job-inspector)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## Installation

Install via Composer:

```bash
composer require tvup/redis-job-inspector
```

This package requires PHP 8.1+ and supports Laravel 10 and 11. Laravel's package auto-discovery will register the service provider and facade.

## Usage

Use the `RedisQueue` facade to build and execute queries against your Redis queues:

```php
use RedisQueue;

// All scheduled and running jobs on the default queue
$jobs = RedisQueue::jobs()->get();

// Scheduled jobs on the "emails" queue
$scheduled = RedisQueue::jobs()
    ->inQueue('emails')
    ->withJobState('Scheduled')
    ->get();

// Jobs of a specific class
$jobsOfClass = RedisQueue::jobs()
    ->withJobClass(\App\Jobs\SendEmail::class)
    ->get();

// Custom filters (e.g., displayName LIKE "%SendEmail%")
$filtered = RedisQueue::jobs()
    ->where('displayName', 'like', '%SendEmail%')
    ->where('job_state', '=', 'Running')
    ->get();
```

## Query Builder Methods

- `inQueue(string $queue)`: Specify the queue name (default: `default`).
- `withJobState(string $state)`: Filter by job state (`Scheduled` or `Running`).
- `withJobClass(string $class)`: Target a specific job class and extract its properties.
- `where(string $field, string $operator, mixed $value)`: Add a field filter (operators: `=` and `like`).
- `reset()`: Reset filters to defaults.
- `get()`: Run the query and return an `Illuminate\Support\Collection` of jobs.

Each job item in the result collection includes:

- `job_state`: `Scheduled` or `Running`.
- `displayName`: Job display name or class name.
- `command`: Array of command properties (e.g., `delay`).
- `delay_to`: Timestamp when the job is scheduled to run.
- `pushed_at`: `Carbon` instance of when the job was pushed.
- `attempts`: Number of attempts so far.
- `max_tries`: Maximum retries allowed.
- Any public/protected/private properties of the job class when using `withJobClass()`.

## Support & Contributing

If you encounter any issues or have questions, please open an issue on the GitHub repository.

Contributions are welcome! Please submit a pull request.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
