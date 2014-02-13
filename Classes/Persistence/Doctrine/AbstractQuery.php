<?php

namespace TYPO3\DoctrineDbal\Persistence\Doctrine;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Stefano Kowalke <blueduck@gmx.net>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractQuery
 *
 * This code is heavily inspired by the database integration of ezPublish
 * from Benjamin Eberlei.
 *
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
abstract class AbstractQuery {
	/**
	 * The query types.
	 */
	const SELECT = 0;
	const DELETE = 1;
	const UPDATE = 2;
	const TRUNCATE = 3;

	/**
	 * The connection to Database
	 *
	 * @var \Doctrine\DBAL\Connection
	 */
	protected $connection;

	/**
	 * @var Expression
	 */
	public $expr;

	/**
	 * The constructor
	 *
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
		$this->expr = GeneralUtility::makeInstance('\\TYPO3\\DoctrineDbal\\Persistence\\Doctrine\\Expression', $connection);
	}

	/**
	 * Executes this query against a database
	 *
	 * @return mixed
	 * @api
	 */
	public function execute() {
		if ($this->getType() == self::SELECT) {
			return $this->connection->executeQuery($this->getSQL());
		} else {
			return $this->connection->executeUpdate($this->getSQL());
		}
	}

	/**
	 * Validates the given constraints.
	 *
	 * If the constraints are given as an array of constraints, this method returns the inner array
	 *
	 * @param array $constraints
	 *
	 * @return array
	 * @throws \Doctrine\DBAL\Query\QueryException
	 */
	protected function validateConstraints(array $constraints) {
		if (count($constraints) === 1 && is_array($constraints[0])) {
			$constraints = $constraints[0];
		}

		if (count($constraints) === 0) {
			throw new QueryException('No constraints given!');
		}

		return $constraints;
	}

	/**
	 * Returns the sql statement of this query
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getSql();
	}
}