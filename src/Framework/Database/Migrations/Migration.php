<?php

namespace MVPS\Lumis\Framework\Database\Migrations;

use Illuminate\Database\Migrations\Migration as IlluminateMigration;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

abstract class Migration extends IlluminateMigration
{
	/**
	 * Get the schema builder instance for the connection.
	 */
	public function schema(): SchemaBuilder
	{
		return app('db')->connection($this->getConnection())
			->getSchemaBuilder();
	}
}
