<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [
	/*
	|--------------------------------------------------------------------------
	| Default Log Channel
	|--------------------------------------------------------------------------
	|
	| This setting specifies which log channel will be used by default for
	| writing log messages. Ensure the value corresponds to one of the
	| channels defined in the "channels" array below.
	|
	*/
	'default' => env('LOG_CHANNEL', 'stack'),

	/*
	|--------------------------------------------------------------------------
	| Deprecation Logging Configuration
	|--------------------------------------------------------------------------
	|
	| This section manages how deprecation warnings are logged. The 'channel'
	| option specifies which log channel will handle these warnings, ensuring
	| they are tracked separately if needed. Deprecation logging helps
	| identify outdated PHP features or library functions in use, enabling you
	| to proactively address them before future updates remove these
	| functionalities.
	|
	| Options:
	|
	| * `channel`: (string) Defines the log channel for deprecation warnings.
	|              Defaults to 'null' unless set in the environment
	|              configuration.
	|
	| * `trace`: (boolean) Enables stack trace logging for deprecations.
	|            Useful for pinpointing where deprecated code originates in
	|            the app.
	|
	*/
	'deprecations' => [
		'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
		'trace' => env('LOG_DEPRECATIONS_TRACE', false),
	],

	/*
	|--------------------------------------------------------------------------
	| Log Channels Configuration
	|--------------------------------------------------------------------------
	|
	| In this section, you can define the various log channels for your
	| application. Each channel represents a specific logging destination,
	| allowing you to direct different types of log messages to different
	| handlers or services. Lumis leverages the Monolog PHP logging library,
	| which offers a wide array of logging drivers, handlers, and formatters
	| that you can easily configure and use.
	|
	| You can choose from several built-in drivers or create custom channels:
	|
	| Available drivers: "single", "daily", "slack", "syslog",
	|                    "errorlog", "monolog", "custom", "stack"
	|
	| Key options include:
	| - `single`: Writes all logs to a single file.
	| - `daily`: Rotates log files on a daily basis.
	| - `stack`: Aggregates multiple channels into one log stream.
	| - `slack`: Sends critical log notifications to a Slack channel.
	| - `syslog`/`errorlog`: Logs to the system's logging service or PHP error
	|                        log.
	|
	| Each channel can be configured with its own log level, path, and
	| additional settings to match your application's logging requirements.
	|
	*/
	'channels' => [
		'daily' => [
			'driver' => 'daily',
			'path' => storage_path('logs/lumis.log'),
			'level' => env('LOG_LEVEL', 'debug'),
			'days' => env('LOG_DAILY_DAYS', 14),
			'replace_placeholders' => true,
		],
		'emergency' => [
			'path' => storage_path('logs/lumis.log'),
		],
		'errorlog' => [
			'driver' => 'errorlog',
			'level' => env('LOG_LEVEL', 'debug'),
			'replace_placeholders' => true,
		],
		'null' => [
			'driver' => 'monolog',
			'handler' => NullHandler::class,
		],
		'papertrail' => [
			'driver' => 'monolog',
			'level' => env('LOG_LEVEL', 'debug'),
			'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
			'handler_with' => [
				'host' => env('PAPERTRAIL_URL'),
				'port' => env('PAPERTRAIL_PORT'),
				'connectionString' => 'tls://' . env('PAPERTRAIL_URL') . ':' . env('PAPERTRAIL_PORT'),
			],
			'processors' => [PsrLogMessageProcessor::class],
		],
		'single' => [
			'driver' => 'single',
			'path' => storage_path('logs/lumis.log'),
			'level' => env('LOG_LEVEL', 'debug'),
			'replace_placeholders' => true,
		],
		'slack' => [
			'driver' => 'slack',
			'url' => env('LOG_SLACK_WEBHOOK_URL'),
			'username' => env('LOG_SLACK_USERNAME', 'Lumis Log'),
			'emoji' => env('LOG_SLACK_EMOJI', ':fire:'),
			'level' => env('LOG_LEVEL', 'critical'),
			'replace_placeholders' => true,
		],
		'stack' => [
			'driver' => 'stack',
			'channels' => explode(',', env('LOG_STACK', 'single')),
			'ignore_exceptions' => false,
		],
		'stderr' => [
			'driver' => 'monolog',
			'level' => env('LOG_LEVEL', 'debug'),
			'handler' => StreamHandler::class,
			'formatter' => env('LOG_STDERR_FORMATTER'),
			'with' => [
				'stream' => 'php://stderr',
			],
			'processors' => [PsrLogMessageProcessor::class],
		],
		'syslog' => [
			'driver' => 'syslog',
			'level' => env('LOG_LEVEL', 'debug'),
			'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
			'replace_placeholders' => true,
		],
	],
];
