<?php

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
