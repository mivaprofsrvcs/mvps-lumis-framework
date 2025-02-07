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

namespace MVPS\Lumis\Framework\Bootstrap;

use MVPS\Lumis\Framework\Contracts\Framework\Application;
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
