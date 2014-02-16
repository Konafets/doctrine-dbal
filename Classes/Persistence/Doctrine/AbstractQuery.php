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
use Doctrine\DBAL\Statement;
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
	 * @var int boundCounter
	 */
	private $boundCounter = 0;

	/**
	 * @var array $boundValues
	 */
	private $boundValues = array();

	/**
	 * @var array $boundValuesType
	 */
	private $boundValuesType = array();

	/**
	 * @var array $boundParameters
	 */
	private $boundParameters = array();

	/**
	 * @var array $boundParametersType
	 */
	private $boundParametersType = array();

	/**
	 * The last executed statement
	 *
	 * @var string
	 */
	private $lastStatement = '';

	/**
	 * The affected rows of the last statement
	 *
	 * @var int
	 */
	private $affectedRows = -1;

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
	 * Prepares a Prepared statement for the database
	 *
	 * @return \PDOStatement
	 * @api
	 */
	public function prepare() {
		$statement = $this->connection->prepare($this->getSql());

		$this->doBind($statement);

		return $statement;
	}

	/**
	 * Performs binding of variables bound with bindValue and bindParam on the statement $statement.
	 *
	 * This method must be called if you have used the bind methods in your query.
	 *
	 * @param \Doctrine\DBAL\Statement $statement
	 *
	 * @return void
	 */
	private function doBind(Statement $statement) {
		foreach ($this->boundValues as $key => $value) {
			$statement->bindValue($key, $value, $this->boundValuesType[$key]);
		}

		foreach ($this->boundParameters as $key => $value) {
			$statement->bindParam($key, $value, $this->boundParametersType[$key]);
		}
	}

	/**
	 * Binds the value $value to the specified variable name $placeholder.
	 *
	 * The method provides a shortcut for PDOStatement::bindValue when using
	 * Prepared Statements.
	 *
	 * The parameter $value specifies the value that you want to bind. If $placeholder is
	 * not provided bindValue() will automatically create a placeholder according the pattern:
	 * 'placeholder1', 'placeholder2', ...
	 *
	 * For more informations see (@link http://dk1.php.net/manual/en/pdostatement.bindvalue.php}
	 *
	 * Example:
	 * <code><br>
	 * $query = $GLOBALS['TYPO3_DB']->createSelectQuery();<br>
	 * $expr = $query->expr;<br><br>
	 *
	 * $value = 2;<br>
	 * $expr->eq('id', $query->bindValue($value));<br>
	 * $stmt = $query->prepare(); // the value 2 is bound to the query.<br>
	 * $value = 4;<br>
	 * $stmt->execute(); // executed with 'id = 2'<br>
	 * </code>
	 *
	 * @param string|int $value
	 * @param string     $placeholder the name to bind with. The string must start with a colon ':'.
	 * @param int        $type
	 *
	 * @return string The used placeholder name
	 * @api
	 */
	public function bindValue($value, $placeholder = NULL, $type = \PDO::PARAM_STR) {
		if ($placeholder === NULL) {
			++$this->boundCounter;
			$placeholder = ':placeholder' . $this->boundCounter;
		}

		$this->boundValues[$placeholder] = $value;
		$this->boundValuesType[$placeholder] = $type;

		return $placeholder;
	}


	/**
	 * Binds the parameter $param to the specified variable name $placeholder.
	 *
	 * This method provides a statement for PDOStatement::bindParam when using
	 * Prepared Statements.
	 *
	 * The parameter $param specifies the variable that will be bind. If $placeholder
	 * is not provided bindParam() will automacially create a placeholder according the pattern:
	 * 'placeholder1', 'placeholder2', ...
	 *
	 * For more informations see (@link http://dk1.php.net/manual/en/pdostatement.bindparam.php}
	 *
	 * Example:
	 * <code><br>
	 * $query = $GLOBALS['TYPO3_DB']->createSelectQuery();<br>
	 * $expr = $query->expr;<br><br>
	 *
	 * $value = 2;<br>
	 * $expr->equals('id', $query->bindParam($value));<br>
	 * $stmt = $query->prepare(); // the parameter $value is bound to the query<br>
	 * $value = 42;<br>
	 * $stmt->execute(); // execute with 'id = 4'<br>
	 * </code>
	 *
	 * @param string|int $param
	 * @param string     $placeholder
	 * @param int        $type
	 *
	 * @return string The used placeholder name
	 * @api
	 */
	public function bindParam(&$param, $placeholder = NULL, $type = \PDO::PARAM_STR) {
		if ($placeholder === NULL) {
			++$this->boundCounter;
			$placeholder = ':placeholder' . $this->boundCounter;
		}

		$this->boundParameters[$placeholder] =& $param;
		$this->boundParametersType[$placeholder] = $type;

		return $placeholder;
	}

	/**
	 * Executes this query against a database
	 *
	 * @return mixed
	 * @api
	 */
	public function execute() {
		if ((!empty($this->boundParameters)) || (!empty($this->boundValues))) {
			$stmt = $this->prepare();

			if ($stmt->execute()) {
				$this->lastStatement = $this->getSql();
				$this->affectedRows = $stmt->rowCount();

				return $this->affectedRows;
			} else {
				return FALSE;
			}
		} else {
			if ($this->getType() == self::SELECT) {
				$this->lastStatement = $this->getSql();

				return $this->connection->executeQuery($this->getSQL());
			} else {
				$this->lastStatement = $this->getSql();

				return $this->connection->executeUpdate($this->getSQL());
			}
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
	 * Returns the affected rows of the query
	 *
	 * @return int
	 * @api
	 */
	public function getAffectedRows() {
		return $this->affectedRows;
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