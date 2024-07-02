<?php

namespace MVPS\Lumis\Framework\Bootstrap;

use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Http\Request;

class SetRequestForConsole
{
	/**
	 * Bootstrap the given application.
	 */
	public function bootstrap(Application $app): void
	{
		$uri = $app->make('config')->get('app.url', 'http://localhost');

		$components = parse_url($uri);

		$server = $_SERVER;

		if (isset($components['path'])) {
			$server = array_merge($server, [
				'SCRIPT_FILENAME' => $components['path'],
				'SCRIPT_NAME' => $components['path'],
			]);
		}

		$app->instance('request', new Request(
			uri: $uri,
			method: 'GET',
			serverParams: $server
		));
	}
}
