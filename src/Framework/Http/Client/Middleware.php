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

namespace MVPS\Lumis\Framework\Http\Client;

use Psr\Http\Message\RequestInterface;

class Middleware
{
	/**
	 * Middleware that adds cookies to requests.
	 *
	 * TODO: Implement this
	 */
	// public static function cookies(): callable
	// {
	// 	return static function (callable $handler): callable {
	// 		return static function ($request, array $options) use ($handler) {
	// 			if (empty($options['cookies'])) {
	// 				return $handler($request, $options);
	// 			} elseif (!($options['cookies'] instanceof CookieJarInterface)) {
	// 				throw new \InvalidArgumentException('cookies must be an instance of GuzzleHttp\Cookie\CookieJarInterface');
	// 			}
	// 			$cookieJar = $options['cookies'];
	// 			$request = $cookieJar->withCookieHeader($request);

	// 			return $handler($request, $options)
	// 				->then(
	// 					static function (ResponseInterface $response) use ($cookieJar, $request): ResponseInterface {
	// 						$cookieJar->extractCookies($request, $response);

	// 						return $response;
	// 					}
	// 				);
	// 		};
	// 	};
	// }

	/**
	 * Middleware that pushes history data to an ArrayAccess container.
	 *
	 * TODO: Implement this
	 *
	 * @throws \InvalidArgumentException
	 */
	// public static function history(array|ArrayAccess &$container): callable
	// {
	// 	if (!\is_array($container) && !$container instanceof \ArrayAccess) {
	// 		throw new \InvalidArgumentException('history container must be an array or object implementing ArrayAccess');
	// 	}

	// 	return static function (callable $handler) use (&$container): callable {
	// 		return static function (RequestInterface $request, array $options) use ($handler, &$container) {
	// 			return $handler($request, $options)->then(
	// 				static function ($value) use ($request, &$container, $options) {
	// 					$container[] = [
	// 						'request' => $request,
	// 						'response' => $value,
	// 						'error' => null,
	// 						'options' => $options,
	// 					];

	// 					return $value;
	// 				},
	// 				static function ($reason) use ($request, &$container, $options) {
	// 					$container[] = [
	// 						'request' => $request,
	// 						'response' => null,
	// 						'error' => $reason,
	// 						'options' => $options,
	// 					];

	// 					return p\Create::rejectionFor($reason);
	// 				}
	// 			);
	// 		};
	// 	};
	// }

	/**
	 * Middleware that throws exceptions for 4xx or 5xx responses when the
	 * "http_errors" request option is set to true.
	 *
	 * TODO: Implement this
	 */
	// public static function httpErrors(BodySummarizerInterface|null $bodySummarizer = null): callable
	// {
	// 	return static function (callable $handler) use ($bodySummarizer): callable {
	// 		return static function ($request, array $options) use ($handler, $bodySummarizer) {
	// 			if (empty($options['http_errors'])) {
	// 				return $handler($request, $options);
	// 			}

	// 			return $handler($request, $options)->then(
	// 				static function (ResponseInterface $response) use ($request, $bodySummarizer) {
	// 					$code = $response->getStatusCode();
	// 					if ($code < 400) {
	// 						return $response;
	// 					}
	// 					throw RequestException::create($request, $response, null, [], $bodySummarizer);
	// 				}
	// 			);
	// 		};
	// 	};
	// }

	/**
	 * Middleware that logs requests, responses, and errors using a message
	 * formatter.
	 *
	 * TODO: Implement this
	 *
	 * @param LoggerInterface                            $logger    Logs messages.
	 * @param MessageFormatterInterface|MessageFormatter $formatter Formatter used to create message strings.
	 * @param string                                     $logLevel  Level at which to log requests.
	 *
	 */
	// public static function log(LoggerInterface $logger, $formatter, string $logLevel = 'info'): callable
	// {
	// 	// To be compatible with Guzzle 7.1.x we need to allow users to pass a MessageFormatter
	// 	if (!$formatter instanceof MessageFormatter && !$formatter instanceof MessageFormatterInterface) {
	// 		throw new LogicException(sprintf('Argument 2 to %s::log() must be of type %s', self::class, MessageFormatterInterface::class));
	// 	}

	// 	return static function (callable $handler) use ($logger, $formatter, $logLevel): callable {
	// 		return static function (RequestInterface $request, array $options = []) use ($handler, $logger, $formatter, $logLevel) {
	// 			return $handler($request, $options)->then(
	// 				static function ($response) use ($logger, $request, $formatter, $logLevel): ResponseInterface {
	// 					$message = $formatter->format($request, $response);
	// 					$logger->log($logLevel, $message);

	// 					return $response;
	// 				},
	// 				static function ($reason) use ($logger, $request, $formatter): PromiseInterface {
	// 					$response = $reason instanceof RequestException ? $reason->getResponse() : null;
	// 					$message = $formatter->format($request, $response, P\Create::exceptionFor($reason));
	// 					$logger->error($message);

	// 					return P\Create::rejectionFor($reason);
	// 				}
	// 			);
	// 		};
	// 	};
	// }

	/**
	 * Middleware that applies a map function to the request before passing to
	 * the next handler.
	 */
	public static function mapRequest(callable $callable): callable
	{
		return static function (callable $handler) use ($callable): callable {
			return static function (RequestInterface $request, array $options) use ($handler, $callable) {
				return $handler($callable($request), $options);
			};
		};
	}

	/**
	 * Middleware that applies a map function to the resolved promise's
	 * response.
	 */
	public static function mapResponse(callable $callable): callable
	{
		return static function (callable $handler) use ($callable): callable {
			return static function (RequestInterface $request, array $options) use ($handler, $callable) {
				return $handler($request, $options)->then($callable);
			};
		};
	}

	/**
	 * This middleware adds a default content-type if possible, a default
	 * content-length or transfer-encoding header, and the expect header.
	 *
	 * TODO: Implement this
	 */
	// public static function prepareBody(): callable
	// {
	// 	return static function (callable $handler): PrepareBodyMiddleware {
	// 		return new PrepareBodyMiddleware($handler);
	// 	};
	// }

	/**
	 * Middleware that handles request redirects.
	 *
	 * TODO: Implement this
	 */
	// public static function redirect(): callable
	// {
	// 	return static function (callable $handler): RedirectMiddleware {
	// 		return new RedirectMiddleware($handler);
	// 	};
	// }

	/**
	 * Middleware that retries requests based on the boolean result of
	 * invoking the provided "decider" function.
	 *
	 * TODO: Implement this
	 */
	// public static function retry(callable $decider, callable|null $delay = null): callable
	// {
	// 	return static function (callable $handler) use ($decider, $delay): RetryMiddleware {
	// 		return new RetryMiddleware($decider, $handler, $delay);
	// 	};
	// }

	/**
	 * Middleware that invokes a callback before and after sending a request.
	 */
	public static function tap(callable|null $before = null, callable|null $after = null): callable
	{
		return static function (callable $handler) use ($before, $after): callable {
			return static function (RequestInterface $request, array $options) use ($handler, $before, $after) {
				if ($before) {
					$before($request, $options);
				}

				$response = $handler($request, $options);

				if ($after) {
					$after($request, $options, $response);
				}

				return $response;
			};
		};
	}
}
