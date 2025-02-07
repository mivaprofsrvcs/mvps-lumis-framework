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

namespace MVPS\Lumis\Framework\Http\Client\Traits;

trait DeterminesStatusCode
{
	/**
	 * Determine if the response code was 202 "Accepted" response.
	 */
	public function accepted(): bool
	{
		return $this->status() === 202;
	}

	/**
	 * Determine if the response was a 400 "Bad Request" response.
	 */
	public function badRequest(): bool
	{
		return $this->status() === 400;
	}

	/**
	 * Determine if the response was a 409 "Conflict" response.
	 */
	public function conflict(): bool
	{
		return $this->status() === 409;
	}

	/**
	 * Determine if the response code was 201 "Created" response.
	 */
	public function created(): bool
	{
		return $this->status() === 201;
	}

	/**
	 * Determine if the response was a 403 "Forbidden" response.
	 */
	public function forbidden(): bool
	{
		return $this->status() === 403;
	}

	/**
	 * Determine if the response code was a 302 "Found" response.
	 */
	public function found(): bool
	{
		return $this->status() === 302;
	}

	/**
	 * Determine if the response code was a 301 "Moved Permanently".
	 */
	public function movedPermanently(): bool
	{
		return $this->status() === 301;
	}

	/**
	 * Determine if the response code was the given status code and the body
	 * has no content.
	 */
	public function noContent(int $status = 204): bool
	{
		return $this->status() === $status && $this->body() === '';
	}

	/**
	 * Determine if the response was a 404 "Not Found" response.
	 */
	public function notFound(): bool
	{
		return $this->status() === 404;
	}

	/**
	 * Determine if the response code was a 304 "Not Modified" response.
	 */
	public function notModified(): bool
	{
		return $this->status() === 304;
	}

	/**
	 * Determine if the response code was 200 "OK" response.
	 */
	public function ok(): bool
	{
		return $this->status() === 200;
	}

	/**
	 * Determine if the response was a 402 "Payment Required" response.
	 */
	public function paymentRequired(): bool
	{
		return $this->status() === 402;
	}

	/**
	 * Determine if the response was a 408 "Request Timeout" response.
	 */
	public function requestTimeout(): bool
	{
		return $this->status() === 408;
	}

	/**
	 * Determine if the response was a 429 "Too Many Requests" response.
	 */
	public function tooManyRequests(): bool
	{
		return $this->status() === 429;
	}

	/**
	 * Determine if the response was a 401 "Unauthorized" response.
	 */
	public function unauthorized(): bool
	{
		return $this->status() === 401;
	}

	/**
	 * Determine if the response was a 422 "Unprocessable Content" response.
	 */
	public function unprocessableContent(): bool
	{
		return $this->status() === 422;
	}

	/**
	 * Determine if the response was a 422 "Unprocessable Content" response.
	 */
	public function unprocessableEntity(): bool
	{
		return $this->unprocessableContent();
	}
}
