<?php

namespace Rokde\LaravelSlowQueryLogger;

use Exception;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class LaravelSlowQueryLoggerProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 */
	public function boot()
	{
		if ($this->app->runningInConsole()) {
			$this->publishes([
				__DIR__ . '/../config/slow-query-logger.php' => config_path('slow-query-logger.php'),
			], 'config');
		}

		$this->setupListener();
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(
			__DIR__ . '/../config/slow-query-logger.php', 'slow-query-logger'
		);
	}

	/**
	 * setting up listener
	 */
	private function setupListener()
	{
		if (!config('slow-query-logger.enabled')) {
			return;
		}

		DB::listen(function (QueryExecuted $queryExecuted) {
			$sql = $queryExecuted->sql;
			$bindings = $queryExecuted->bindings;
			$time = $queryExecuted->time;

			$logSqlQueriesSlowerThan = (float)config('slow-query-logger.time-to-log', -1);
			if ($logSqlQueriesSlowerThan < 0 || $time < $logSqlQueriesSlowerThan) {
				return;
			}

			$level = config('slow-query-logger.log-level', 'debug');
			$channel = config('slow-query-logger.channel', 'single');
			try {
				foreach ($bindings as $val) {
					$sql = preg_replace('/\?/', "'{$val}'", $sql, 1);
				}

				Log::channel($channel)->log($level, $time . '  ' . $sql);
			} catch (Exception $e) {
				//  be quiet on error
			}
		});
	}
}
