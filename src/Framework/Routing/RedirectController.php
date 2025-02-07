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

namespace MVPS\Lumis\Framework\Routing;

use MVPS\Lumis\Framework\Http\RedirectResponse;
use MVPS\Lumis\Framework\Http\Request;
use MVPS\Lumis\Framework\Support\Str;

class RedirectController extends Controller
{
	/**
	 * Invoke the redirect controller method.
	 */
	public function __invoke(Request $request, UrlGenerator $url): RedirectResponse
	{
		$parameters = collection($request->route()->parameters());

		$status = $parameters->get('status');
		$destination = $parameters->get('destination');

		$parameters->forget('status')
			->forget('destination');

		$route = (new Route('GET', $destination, ['as' => 'lumis_route_redirect_destination']))
			->bind($request);

		$parameters = $parameters->only($route->getCompiled()->getPathVariables())
			->all();

		$url = $url->toRoute($route, $parameters, false);

		if (! str_starts_with($destination, '/') && str_starts_with($url, '/')) {
			$url = Str::after($url, '/');
		}

		return new RedirectResponse($url, $status);
	}
}
