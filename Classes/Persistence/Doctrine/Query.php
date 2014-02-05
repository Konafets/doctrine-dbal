<?php
namespace TYPO3\DoctrineDbal\Persistence\Doctrine;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  (c) 2014 Stefano Kowalke <blueduck@gmx.net>
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\DoctrineDbal\Persistence\QueryInterface;

class Query implements QueryInterface {

	/**
	 * @var int $type The type of the query
	 */
	private $type;

	/**
	 * @var \Doctrine\DBAL\Query\QueryBuilder
	 */
	protected $queryBuilder;

	/**
	 * @var mixed
	 */
	protected $constraint;

	/**
	 * @var array
	 */
	protected $orderings;

	/**
	 * @var int
	 */
	protected $limit;

	/**
	 * @var int
	 */
	protected $offset;

	/**
	 * @var int
	 */
	protected $parameterIndex = 1;

	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @var array
	 */
	protected $joins;

	/**
	 * @var int
	 */
	protected $joinAliasCounter = 0;

	/**
	 * The DBAL Connection.
	 *
	 * @var \Doctrine\DBAL\Connection
	 */
	private $connection;

	/**
	 * @param Connection $connection The database connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
		$this->queryBuilder = $this->connection->createQueryBuilder();
	}

	/**
	 * Returns the type this query cares for.
	 *
	 * @return string
	 * @api
	 */
	public function getType() {
		return $this->queryBuilder->getType();
	}

	/**
	 * Returns the query result count.
	 *
	 * @return int The query result count
	 * @api
	 */
	public function count() {
		// TODO: Implement count() method.
	}

	/**
	 * Sets the property names to order the result by. Like this:
	 * array(
	 *     'foo' => \TYPO3\DoctrineDbal\Persistence\QueryInterface::ORDER_ASCENDING,
	 *     'bar' => \TYPO3\DoctrineDbal\Persistence\QueryInterface::ORDER_DESCENDING,
	 * )
	 *
	 * Returns $this to allow chaining (Fluent interface).
	 *
	 * @param array $orderings The property names to order by
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\QueryInterface
	 * @api
	 */
	public function setOrderings(array $orderings) {
		$this->orderings = $orderings;
		$this->queryBuilder->resetQueryPart('orderBy');
		foreach ($this->orderings as $propertyName => $order) {
			$this->queryBuilder->addOrderBy($this->getPropertyNameWithAlias($propertyName), $order);
		}

		return $this;
	}

	/**
	 * Gets the property names to order the result by. Like this:
	 * array(
	 *     'foo' => \TYPO3\DoctrineDbal\Persistence\QueryInterface::ORDER_ASCENDING,
	 *     'bar' => \TYPO3\DoctrineDbal\Persistence\QueryInterface::ORDER_DESCENDING,
	 * )
	 *
	 * @return array
	 * @api
	 */
	public function getOrderings() {
		return $this->orderings;
	}

	/**
	 * Set the maximum size of the result set to limit.
	 * Returns $this to allow chaining (Fluent interface).
	 *
	 * @param int $limit
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\QueryInterface
	 * @api
	 */
	public function setLimit($limit) {
		$this->limit = $limit;
		$this->queryBuilder->setMaxResults($limit);

		return $this;
	}

	/**
	 * Returns the maximum size of the result set to limit
	 *
	 * @return int
	 * @api
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Set the start offset of the result set to offset.
	 * Returns $this to allow chaining (Fluent interface).
	 *
	 * @param int $offset
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\QueryInterface
	 * @api
	 */
	public function setOffset($offset) {
		$this->offset = $offset;
		$this->queryBuilder->setFirstResult($offset);

		return $this;
	}

	/**
	 * Returns the start offset of the result set
	 *
	 * @return int
	 * @api
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * The constraint used to limit the result set.
	 * Returns $this to allow chaining (Fluent interface).
	 *
	 * @param object $constraint Some constraint, depending on the backend
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\QueryInterface
	 * @api
	 */
	public function matching($constraint) {
		$this->constraint = $constraint;
		$this->queryBuilder->where($constraint);

		return $this;
	}

	/**
	 * Gets the constraint for this query
	 *
	 * @return mixed The constraint, or NULL if none.
	 * @api
	 */
	public function getConstraint() {
		return $this->constraint;
	}

	/**
	 * Performs a logical conjunction of the two given constraints. The method takes
	 * one or more constraints and concatenates them with a boolean AND.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 *
	 * @return object
	 * @api
	 */
	public function logicalAnd($constraint1) {
		if (is_array($constraint1)) {
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
		}

		return call_user_func_array(array($this->queryBuilder->expr(), 'andX'), $constraints);
	}

	/**
	 * Performs a logical disjunction of the two given constraints. The method takes
	 * one or more constraints and concatenates them with a boolean OR.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 *
	 * @return object
	 * @api
	 */
	public function logicalOr($constraint1) {
		if (is_array($constraint1)) {
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
		}

		return call_user_func_array(array($this->queryBuilder->expr(), 'orX'), $constraints);
	}

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 *
	 * @return object
	 * @api
	 */
	public function logicalNot($constraint) {
		return $this->queryBuilder->expr()->not($constraint);
	}

	/**
	 * Returns an '=' criterion used for matching objects against a query.
	 *
	 * It matches if the $operand equals the value of the property named $propertyName.
	 * If $operand is NULL a strict check for NULL is done. For strings the comparison can be done
	 * with or without case-sensitivity.
	 *
	 * @param string $propertyName  The name of the property to compare against
	 * @param mixed  $operand       The value to compare to
	 * @param bool   $caseSensitive Whether the equality test should be done case-sensitives for strings
	 *
	 * @return object
	 * @api
	 * @todo Decide what to do about equality on multivalued properties
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE) {
		$this->parameters[] = $operand;
		return $this->queryBuilder->expr()->eq($propertyName, '?');
	}

	/**
	 * Returns a 'LIKE' criterion used for matching objects against a query.
	 * It matches if the $propertyName is like the $operand, using standard SQL wildcards.
	 *
	 * @param string $propertyName  The name of the property to compare against
	 * @param string $operand       The value to compare with
	 * @param bool   $caseSensitive Whether the matching should be done case-sensitives
	 *
	 * @return object
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidQueryException
	 * @api
	 */
	public function like($propertyName, $operand, $caseSensitive = TRUE) {
		// TODO: Implement like() method.
	}

	/**
	 * Returns an 'CONTAINS' criterion used for matching objects against a query.
	 * It matches if the multivalued $property contains the given $operand.
	 *
	 * If NULL is given as $operand, there will never be a match!
	 *
	 * @param string $propertyName The name of the multivalued property to compare against
	 * @param mixed  $operand      The value to compare with
	 *
	 * @return object
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidQueryException
	 * @api
	 */
	public function contains($propertyName, $operand) {
		// TODO: Implement contains() method.
	}

	/**
	 * Returns an 'IS EMPTY' criterion used for matching objects against a query.
	 * It matches if the multivalued property contains no value or is NULL.
	 *
	 * @param string $propertyName The name of the multivalued property to compare against
	 *
	 * @return object
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidQueryException
	 * @api
	 */
	public function isEmpty($propertyName) {
		// TODO: Implement isEmpty() method.
	}

	/**
	 * Returns an 'IN' criterion used for matching objects against a query.
	 * It matches if the property's value is contained in the multivalued operand.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed  $operand      The value to compare with, multivalued
	 *
	 * @return object
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidQueryException
	 * @api
	 */
	public function in($propertyName, $operand) {

		return $this->queryBuilder->expr()->in($propertyName, $operand);
	}

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed  $operand      The value to compare with
	 *
	 * @return object
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidQueryException
	 * @api
	 */
	public function lessThan($propertyName, $operand) {
		$this->parameters[] = $operand;
		return $this->queryBuilder->expr()->lt($propertyName, '?');
	}

	/**
	 * Returns a less than or equal criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed  $operand      The value to compare with
	 *
	 * @return object
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidQueryException
	 * @api
	 */
	public function lessThanOrEqual($propertyName, $operand) {
		$this->parameters[] = $operand;
		return $this->queryBuilder->expr()->lte($propertyName, '?');
	}

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed  $operand      The value to compare with
	 *
	 * @return object
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidQueryException
	 * @api
	 */
	public function greaterThan($propertyName, $operand) {
		$this->parameters[] = $operand;
		return $this->queryBuilder->expr()->gt($propertyName, '?');
	}

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed  $operand      The value to compare with
	 *
	 * @return object
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidQueryException
	 * @api
	 */
	public function greaterThanOrEqual($propertyName, $operand) {
		$this->parameters[] = $operand;
		return $this->queryBuilder->expr()->gte($propertyName, '?');
	}

	/**
	 * Creates a DELETE statement
	 *
	 * @param string $table The table to delete from
	 * @param string $alias The alias of the table
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\Query
	 * @api
	 */
	public function delete($table, $alias = NULL) {
		$this->type = self::DELETE;
		$this->queryBuilder->delete($table, $alias);

		return $this;
	}

	/**
	 * Returns the query as a string
	 *
	 * @return string
	 * @api
	 */
	public function getSql() {
		return (string) $this->queryBuilder->getSQL();
	}

	/**
	 * Get the needle for parameter building
	 *
	 * @param $operand
	 *
	 * @return string
	 */
	protected function getParamNeedle($operand) {
		$index = $this->parameterIndex++;
		$this->queryBuilder->setParameter($index, $operand);

		return '?' . $index;
	}

	protected function getPropertyNameWithAlias($propertyPath) {
		//$aliases = $this->queryBuilder->get
		return array($propertyPath, $propertyPath);
	}

	/**
	 * We need to drop the query builder, as is contains a PDO instance deep inside.
	 *
	 * @return array
	 */
	public function __sleep() {
		$this->parameters = $this->queryBuilder->getParameters();
		return array('constraint', 'orderings', 'parameterIndex', 'limit', 'offset', 'parameters', 'joins');
	}

	/**
	 * Recreate query builder and set state again.
	 *
	 * @return void
	 */
	public function __wakeup() {
		if ($this->constraint !== NULL) {
			$this->queryBuilder->where($this->constraint);
		}

		if (is_array($this->orderings)) {

		}

		if (is_array($this->joins)) {
			foreach ($this->joins as $joinAlias => $join) {
				$this->queryBuilder->leftJoin($join, $joinAlias);
			}
		}

		$this->queryBuilder->setFirstResult($this->offset);
		$this->queryBuilder->setMaxResults($this->limit);
		$this->queryBuilder->setParameters($this->parameters);
		unset($this->parameters);
	}

	/**
	 * Cloning the query clones also the internal QueryBuilder, as they are tightly coupled.
	 */
	public function __clone() {
		$this->queryBuilder = clone $this->queryBuilder;
	}

	/**
	 * Executes the query and returns the result.
	 *
	 * @return mixed
	 * @api
	 */
	public function execute() {
		$this->queryBuilder->setParameters($this->parameters);
		return $this->queryBuilder->execute();
	}

	/**
	 * Set the query parameters for prepared queries
	 *
	 * @param array $parameters
	 *
	 * @return void
	 * @api
	 */
	public function setParameters(array $parameters) {
		$this->queryBuilder->setParameters($parameters);
	}
}
