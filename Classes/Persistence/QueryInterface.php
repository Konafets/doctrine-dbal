<?php
namespace TYPO3\DoctrineDbal\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Stefano Kowalke <blueduck@gmx.net>
 *  (c) TYPO3 Flow Team
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

interface QueryInterface {

	/**
	 * The 'SELECT' query type
	 * @api
	 */
	const SELECT = 0;

	/**
	 * The 'DELETE' query type
	 * @api
	 */
	const DELETE = 1;

	/**
	 * The 'UPDATE' query type
	 * @api
	 */
	const UPDATE = 2;

	/**
	 * The '=' comparison operator.
	 * @api
	 */
	const OPERATOR_EQUAL_TO = 1;

	/**
	 * The '!=' comparison operator.
	 * @api
	 */
	const OPERATOR_NOT_EQUAL_TO = 2;

	/**
	 * The '<' comparison operator.
	 * @api
	 */
	const OPERATOR_LESS_THAN = 3;

	/**
	 * The '<=' comparison operator.
	 * @api
	 */
	const OPERATOR_LESS_THAN_OR_EQUAL_TO = 4;

	/**
	 * The '>' comparison operator.
	 * @api
	 */
	const OPERATOR_GREATER_THAN = 5;

	/**
	 * The '>=' comparison operator.
	 * @api
	 */
	const OPERATOR_GREATER_THAN_OR_EQUAL_TO = 6;

	/**
	 * The 'LIKE' comparison operator.
	 * @api
	 */
	const OPERATOR_LIKE = 7;

	/**
	 * The 'CONTAINS' comparison operator for collections.
	 * @api
	 */
	const OPERATOR_CONTAINS = 8;

	/**
	 * The 'IN' comparison operator.
	 * @api
	 */
	const OPERATOR_IN = 9;

	/**
	 * The 'IS NULL' comparison operator.
	 * @api
	 */
	const OPERATOR_IS_NULL = 10;

	/**
	 * The 'IS EMPTY' comparison operator for collections.
	 * @api
	 */
	const OPERATOR_IS_EMPTY = 11;

	/**
	 * Constant representing the ascending direction when sorting the result sets.
	 * @api
	 */
	const ORDER_ASCENDING = 'ASC';

	/**
	 * Constant representing the descending direction when sorting the result sets.
	 * @api
	 */
	const ORDER_DESCENDING = 'DESC';

	/**
	 * Returns the type this query cares for.
	 *
	 * @return string
	 * @api
	 */
	public function getType();

	/**
	 * Executes the query and returns the result.
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\QueryInterface The query result
	 * @api
	 */
	public function execute();

	/**
	 * Returns the query result count.
	 *
	 * @return int The query result count
	 * @api
	 */
	public function count();

	/**
	 * Set the query parameters for prepared queries
	 *
	 * @param array $parameters
	 *
	 * @return void
	 */
	public function setParameters(array $parameters);

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
	public function setOrderings(array $orderings);

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
	public function getOrderings();

	/**
	 * Set the maximum size of the result set to limit.
	 * Returns $this to allow chaining (Fluent interface).
	 *
	 * @param int $limit
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\QueryInterface
	 * @api
	 */
	public function setLimit($limit);

	/**
	 * Returns the maximum size of the result set to limit
	 *
	 * @return int
	 * @api
	 */
	public function getLimit();

	/**
	 * Set the start offset of the result set to offset.
	 * Returns $this to allow chaining (Fluent interface).
	 *
	 * @param int $offset
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\QueryInterface
	 * @api
	 */
	public function setOffset($offset);

	/**
	 * Returns the start offset of the result set
	 *
	 * @return int
	 * @api
	 */
	public function getOffset();

	/**
	 * The constraint used to limit the result set.
	 * Returns $this to allow chaining (Fluent interface).
	 *
	 * @param object $constraint Some constraint, depending on the backend
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\QueryInterface
	 * @api
	 */
	public function matching($constraint);

	/**
	 * Gets the constraint for this query
	 *
	 * @return mixed The constraint, or NULL if none.
	 * @api
	 */
	public function getConstraint();

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
	public function logicalAnd($constraint1);

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
	public function logicalOr($constraint1);

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 *
	 * @return object
	 * @api
	 */
	public function logicalNot($constraint);

	/**
	 * Returns an '==' criterion used for matching objects against a query.
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
	public function equals($propertyName, $operand, $caseSensitive = TRUE);

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
	public function like($propertyName, $operand, $caseSensitive = TRUE);

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
	public function contains($propertyName, $operand);

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
	public function isEmpty($propertyName);

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
	public function in($propertyName, $operand);

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
	public function lessThan($propertyName, $operand);

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
	public function lessThanOrEqual($propertyName, $operand);

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
	public function greaterThan($propertyName, $operand);

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
	public function greaterThanOrEqual($propertyName, $operand);

	/**
	 * Creates a DELETE statement
	 *
	 * @param string $table The table to delete from
	 * @param string $alias The alias of the table
	 *
	 * @return \TYPO3\DoctrineDbal\Database\Query
	 * @api
	 */
	public function delete($table, $alias = NULL);

	/**
	 * Returns the query as a string
	 *
	 * @return string
	 * @api
	 */
	public function getSql();
}
