<?php

namespace MVPS\Lumis\Framework\Database;

use Illuminate\Database\DatabaseManager as IlluminateDatabaseManager;
use MVPS\Lumis\Framework\Application;
use MVPS\Lumis\Framework\Database\Connectors\ConnectionFactory;

class DatabaseManager extends IlluminateDatabaseManager
{
	/**
	 * The application instance.
	 *
	 * @var \MVPS\Lumis\Framework\Application
	 */
	protected $app;

	/**
	 * The database connection factory instance.
	 *
	 * @var \MVPS\Lumis\Framework\Database\Connectors\ConnectionFactory
	 */
	protected $factory;

	/**
	 * Create a new database manager instance.
	 */
	public function __construct(Application $app, ConnectionFactory $factory)
	{
		parent::__construct($app, $factory);
	}
}
