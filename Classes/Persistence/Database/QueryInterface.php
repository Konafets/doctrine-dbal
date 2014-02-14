<?php

namespace TYPO3\DoctrineDbal\Persistence\Database;

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

/**
 * Interface QueryInterface
 *
 * This code is heavily inspired by the database integration of ezPublish
 * from Benjamin Eberlei.
 *
 * @package TYPO3\DoctrineDbal\Persistence\Database
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
interface QueryInterface {
	/**
	 * Returns the type of the query
	 *
	 * @return int
	 */
	public function getType();

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
	public function bindValue($value, $placeholder = NULL, $type = \PDO::PARAM_STR);

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
	public function bindParam(&$param, $placeholder = NULL, $type = \PDO::PARAM_STR);

	/**
	 * Prepares a Prepared statement for the database
	 *
	 * @return \PDOStatement
	 * @api
	 */
	public function prepare();

	/**
	 * Executes this query against a database
	 *
	 * @return mixed
	 * @api
	 */
	public function execute();

	/**
	 * Returns the SQL statement of this query as a string
	 *
	 * @return string
	 * @throws \Doctrine\DBAL\Query\QueryException
	 * @api
	 */
	public function getSql();

	/**
	 * Returns the sql statement of this query
	 *
	 * @return string
	 */
	public function __toString();
}