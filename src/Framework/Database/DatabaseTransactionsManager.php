<?php

namespace MVPS\Lumis\Framework\Database;

use Illuminate\Database\DatabaseTransactionsManager as IlluminateDatabaseTransactionsManager;
use MVPS\Lumis\Framework\Collections\Collection;

class DatabaseTransactionsManager extends IlluminateDatabaseTransactionsManager
{
	/**
	 * All of the committed transactions.
	 *
	 * @var \MVPS\Lumis\Framework\Collections\Collection
	 */
	protected $committedTransactions;

	/**
	 * All of the pending transactions.
	 *
	 * @var \MVPS\Lumis\Framework\Collections\Collection
	 */
	protected $pendingTransactions;

	/**
	 * Create a new database transactions manager instance.
	 */
	public function __construct()
	{
		$this->committedTransactions = new Collection;
		$this->pendingTransactions = new Collection;
	}
}
