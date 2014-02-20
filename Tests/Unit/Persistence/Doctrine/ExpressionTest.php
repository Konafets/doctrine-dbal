<?php

namespace TYPO3\DoctrineDbal\Tests\Unit\Persistence\Doctrine;

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
 * Class ExpressionTest
 *
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class ExpressionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\DoctrineDbal\Persistence\Doctrine\Expression
	 */
	private $subject = NULL;

	/**
	 * Set the tests up
	 */
	public function setUp() {
		$GLOBALS['TYPO3_DB']->connectDB();
		$this->subject = $GLOBALS['TYPO3_DB']->createDeleteQuery()->expr;
	}

	/**
	 * Tears the test down
	 */
	public function tearDown() {
		$GLOBALS['TYPO3_DB']->getDatabaseHandle()->close();
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function andConstraintWithSingleExpression() {
		$and = $this->subject->logicalAnd('pages = 3');
		$this->assertEquals('pages = 3', $and);

		$eq = $this->subject->equals('pages', 3);
		$and = $this->subject->logicalAnd($eq);

		$this->assertEquals('pages = 3', $and);
	}

	/**
	 * @test
	 */
	public function andConstraintTestWithMultipleExpressions() {
		$and = $this->subject->logicalAnd('pages = 3', 'pages > 0');
		$expectedSql = '(pages = 3) AND (pages > 0)';
		$this->assertEquals($expectedSql, $and);

		$eq = $this->subject->equals('pages', 3);
		$lt = $this->subject->greaterThan('pages', 0);
		$and = $this->subject->logicalAnd($eq, $lt);

		$expectedSql = '(pages = 3) AND (pages > 0)';
		$this->assertEquals($expectedSql, $and);
	}

	/**
	 * @test
	 */
	public function orConstraintWithSingleExpression() {
		$or = $this->subject->logicalOr('pages > 0');
		$this->assertEquals('pages > 0', $or);

		$gt = $this->subject->greaterThan('pages', 0);
		$or = $this->subject->logicalOr($gt);
		$this->assertEquals('pages > 0', $or);
	}


	/**
	 * @test
	 */
	public function orConstraintTestWithMultipleExpressions() {
		$or = $this->subject->logicalOr('pages = 3', 'pages > 0');
		$expectedSql = '(pages = 3) OR (pages > 0)';
		$this->assertEquals($expectedSql, $or);

		$eq = $this->subject->equals('pages', 3);
		$gt = $this->subject->greaterThan('pages', 0);
		$or = $this->subject->logicalOr($eq, $gt);

		$expectedSql = '(pages = 3) OR (pages > 0)';
		$this->assertEquals($expectedSql, $or);
	}

	/**
	 * Data Provider for testing the where equalsTest()
	 *
	 * @return array
	 */
	public function equalsDataProvider() {
		return array(
			'with raw integer as input' => array(array('pages', 3), 'pages = 3'),
			'with raw string as input' => array(array('title', 'Foo'), 'title = Foo'),
		);
	}

	/**
	 * @test
	 * @dataProvider equalsDataProvider
	 *
	 * @param string $whereClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function equalsTest($whereClause, $expectedResult) {
		$string = $this->subject->equals($whereClause[0], $whereClause[1]);
		$this->assertEquals($expectedResult, $string);
	}

	/**
	 * Data Provider for testing the where notEqualsTest()
	 *
	 * @return array
	 */
	public function notEqualsDataProvider() {
		return array(
			'with raw integer as input' => array(array('pages', 3), 'pages <> 3'),
			'with raw string as input' => array(array('title', 'Foo'), 'title <> Foo'),
		);
	}

	/**
	 * @test
	 * @dataProvider notEqualsDataProvider
	 *
	 * @param string $whereClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function notEqualsTest($whereClause, $expectedResult) {
		$string = $this->subject->notEquals($whereClause[0], $whereClause[1]);
		$this->assertEquals($expectedResult, $string);
	}

	/**
	 * Data Provider for testing the where lessThanTest()
	 *
	 * @return array
	 */
	public function lessThanDataProvider() {
		return array(
			'with raw integer as input' => array(array('pages', 3), 'pages < 3'),
			'with raw string as input' => array(array('pages', 'Foo'), 'pages < Foo'),
		);
	}

	/**
	 * @test
	 * @dataProvider lessThanDataProvider
	 *
	 * @param string $whereClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function lessThanTest($whereClause, $expectedResult) {
		$string = $this->subject->lessThan($whereClause[0], $whereClause[1]);
		$this->assertEquals($expectedResult, $string);
	}

	/**
	 * Data Provider for testing the where lessThanOrEqualTest()
	 *
	 * @return array
	 */
	public function lessThanOrEqualDataProvider() {
		return array(
			'with raw integer as input' => array(array('pages', 3), 'pages <= 3'),
			'with raw string as input' => array(array('pages', 'Foo'), 'pages <= Foo'),
		);
	}

	/**
	 * @test
	 * @dataProvider lessThanOrEqualDataProvider
	 *
	 * @param string $whereClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function lessThanOrEqualTest($whereClause, $expectedResult) {
		$string = $this->subject->lessThanOrEquals($whereClause[0], $whereClause[1]);
		$this->assertEquals($expectedResult, $string);
	}

	/**
	 * Data Provider for testing the where greaterThanTest()
	 *
	 * @return array
	 */
	public function greaterThanDataProvider() {
		return array(
			'with raw integer as input' => array(array('pages', 3), 'pages > 3'),
			'with raw string as input' => array(array('pages', 'Foo'), 'pages > Foo'),
		);
	}

	/**
	 * @test
	 * @dataProvider greaterThanDataProvider
	 *
	 * @param string $whereClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function greaterThanTest($whereClause, $expectedResult) {
		$string = $this->subject->greaterThan($whereClause[0], $whereClause[1]);
		$this->assertEquals($expectedResult, $string);
	}

	/**
	 * Data Provider for testing the where greaterThanOrEqualTest()
	 *
	 * @return array
	 */
	public function greaterThanOrEqualDataProvider() {
		return array(
			'with raw integer as input' => array(array('pages', 3), 'pages >= 3'),
			'with raw string as input' => array(array('pages', 'Foo'), 'pages >= Foo'),
//			'with positional parameter as input' => array(array('pages', '?'), 'pages >= ?'),
//			'with named parameter as input' => array(array('pages', ':named'), 'pages >= :named'),
		);
	}

	/**
	 * @test
	 * @dataProvider greaterThanOrEqualDataProvider
	 *
	 * @param string $whereClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function greaterThanOrEqualTest($whereClause, $expectedResult) {
		$string = $this->subject->greaterThanOrEquals($whereClause[0], $whereClause[1]);
		$this->assertEquals($expectedResult, $string);
	}

	/**
	 * Data Provider for testing the where inTest()
	 *
	 * @return array
	 */
	public function inDataProvider() {
		return array(
			'with one integer as input' => array(array('pages', array(1)), 'pages IN (1)'),
			'with multiple integers as input' => array(array('pages', array(1, 2, 3)), 'pages IN (1, 2, 3)'),
			'with string as input' => array(array('pages', array('Foo')), 'pages IN (Foo)'),
			'with multiple stringa as input' => array(array('pages', array('foo', 'bar', 'baz')), 'pages IN (foo, bar, baz)'),
		);
	}

	/**
	 * @test
	 * @dataProvider inDataProvider
	 *
	 * @param string $whereClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function inTest($whereClause, $expectedResult) {
		$string = $this->subject->in($whereClause[0], $whereClause[1]);

		$this->assertEquals($expectedResult, $string);
	}

	/**
	 * Data Provider for testing the where notInTest()
	 *
	 * @return array
	 */
	public function notInDataProvider() {
		return array(
			'with one integer as input' => array(array('pages', array(1)), 'pages NOT IN (1)'),
			'with multiple integers as input' => array(array('pages', array(1, 2, 3)), 'pages NOT IN (1, 2, 3)'),
			'with string as input' => array(array('pages', array('Foo')), 'pages NOT IN (Foo)'),
			'with multiple stringa as input' => array(array('pages', array('foo', 'bar', 'baz')), 'pages NOT IN (foo, bar, baz)'),
		);
	}

	/**
	 * @test
	 * @dataProvider notInDataProvider
	 *
	 * @param string $whereClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function notInTest($whereClause, $expectedResult) {
		$string = $this->subject->notIn($whereClause[0], $whereClause[1]);
		$this->assertEquals($expectedResult, $string);
	}

	/**
	 * Data Provider for testing the where likeTest()
	 *
	 * @return array
	 */
	public function likeDataProvider() {
		return array(
			'with raw integer as input' => array(array('pages', 3), 'pages LIKE 3'),
			'with raw string as input' => array(array('pages', 'Foo'), 'pages LIKE Foo'),
		);
	}

	/**
	 * @test
	 * @dataProvider likeDataProvider
	 *
	 * @param string $whereClause  The clause to test
	 * @param string $expectedResult The expected result
	 *
	 * @return void
	 */
	public function likeTest($whereClause, $expectedResult) {
		$string = $this->subject->like($whereClause[0], $whereClause[1]);
		$this->assertEquals($expectedResult, $string);
	}

	/**
	 * @test
	 */
	public function lower() {
		$this->assertEquals('LOWER(LowerCasedTestString)', $this->subject->lower('LowerCasedTestString'));
	}

	/**
	 * @test
	 */
	public function upper() {
		$this->assertEquals('UPPER(LowerCasedTestString)', $this->subject->upper('LowerCasedTestString'));
	}

	/**
	 * @test
	 */
	public function differentWeightOfTheAndComparisionOwnApi() {
		$expr = $this->subject;
		$or = $expr->logicalOr(
				$expr->logicalAnd($expr->equals('extension_key', 'a1_teasermenu'), $expr->equals('integer_version', 2000)),
				$expr->logicalAnd($expr->equals('extension_key', 'tt_news'), $expr->equals('integer_version', 4500))
		);

		$expectedSql = '((extension_key = a1_teasermenu) AND (integer_version = 2000)) OR ((extension_key = tt_news) AND (integer_version = 4500))';
		$this->assertEquals($expectedSql, (string)$or);
	}

	/**
	 * @test
	 */
	public function findInSet() {
		$sql = $this->subject->findInSet('54,53,23,42', 'treelist');
		$expectedSql = 'FIND_IN_SET(\'treelist\',\'54,53,23,42\')';

		$this->assertEquals($expectedSql, $sql);
	}

	/**
	 * @test
	 * @expectedException \Doctrine\DBAL\Query\QueryException
	 */
	public function findInListThrowsExceptionIfSecondParameterContainsComma() {
		$this->subject->findInSet('54,53,23,42', 'treelist,');
	}
}