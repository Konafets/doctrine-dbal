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
 * Class DeleteQueryTest
 * 
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class DeleteQueryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\DoctrineDbal\Persistence\Doctrine\DeleteQuery
	 */
	private $subject = NULL;

	/**
	 * @var string
	 */
	protected $testTable;

	/**
	 * @var string
	 */
	protected $testField;

	/**
	 * @var string
	 */
	protected $testFieldSecond;

	/**
	 * Set the test up
	 *
	 * @return void
	 */
	public function setUp() {
		$GLOBALS['TYPO3_DB']->connectDB();
		$this->subject = $GLOBALS['TYPO3_DB']->createDeleteQuery();
		$this->testTable = 'test_t3lib_dbtest';
		$this->testField = 'fieldblob';
		$this->testFieldSecond = 'fieldblub';
		$GLOBALS['TYPO3_DB']->sql_query('CREATE TABLE ' . $this->testTable . ' (
			id int(11) unsigned NOT NULL auto_increment,' .
			$this->testField . ' mediumblob,' .
			$this->testFieldSecond . ' int,
			PRIMARY KEY (id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		');
	}

	/**
	 * Tear the test down
	 *
	 * @return void
	 */
	public function tearDown() {
		$GLOBALS['TYPO3_DB']->sql_query('DROP TABLE ' . $this->testTable . ';');
		$GLOBALS['TYPO3_DB']->getDatabaseHandle()->close();
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function deleteTable() {
		$this->subject->delete($this->testTable);
		$expectedSql = 'DELETE FROM ' . $this->testTable;
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 * @expectedException \Doctrine\DBAL\Query\QueryException
	 */
	public function deleteWithoutTableThrowsException() {
		$query = $this->subject->delete('');
		$query->getSql();
	}

	/**
	 * @test
	 * @expectedException \Doctrine\DBAL\Query\QueryException
	 */
	public function deleteWithIntegerAsTableThrowsException() {
		$query = $this->subject->delete(0);
		$query->getSql();
	}

	/**
	 * @test
	 */
	public function deleteWithSimpleWhere() {
		$this->subject->delete($this->testTable)
				->where(
					$this->subject->expr->equals($this->testField, 'Foo')
				);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . ' = Foo';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function deleteWithSimpleWherePreparedStatementWithCustomPlaceholder() {
		$this->subject->delete($this->testTable)
				->where(
					$this->subject->expr->equals($this->testField, $this->subject->bindValue('Foo', ':Foo'))
				);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . ' = :Foo';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function deleteWithSimpleWherePreparedStatementWithAutomaticPlaceholders() {
		$this->subject->delete($this->testTable)
				->where(
					$this->subject->expr->logicalAnd(
							$this->subject->expr->equals($this->testField, $this->subject->bindValue('Foo')),
							$this->subject->expr->equals($this->testFieldSecond, $this->subject->bindValue(2))
					)
				);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE (' . $this->testField . ' = :placeholder1) AND (' . $this->testFieldSecond . ' = :placeholder2)';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function deleteWithSimpleWherePreparedStatementWithAutomaticAndCustomPlaceholder() {
		$this->subject->delete($this->testTable)
				->where(
					$this->subject->expr->logicalAnd(
							$this->subject->expr->equals($this->testField, $this->subject->bindValue('Foo', ':Foo')),
							$this->subject->expr->equals($this->testFieldSecond, $this->subject->bindValue(2))
					)
				);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE (' . $this->testField . ' = :Foo) AND (' . $this->testFieldSecond . ' = :placeholder1)';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function deleteWhereAndLessThan() {
		$this->subject->delete($this->testTable)->where(
			$this->subject->expr->lessThan($this->testField, 'Foo')
		);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . ' < Foo';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}


	/**
	 * @test
	 */
	public function deleteWhereAndLessThanOrEquals() {
		$this->subject->delete($this->testTable)->where(
			$this->subject->expr->lessThanOrEquals($this->testField, 'Foo')
		);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . ' <= Foo';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function deleteWhereAndGreaterThan() {
		$this->subject->delete($this->testTable)->where(
			$this->subject->expr->greaterThan($this->testField, 'Foo')
		);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . ' > Foo';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}


	/**
	 * @test
	 */
	public function deleteWhereAndGreaterThanOrEquals() {
		$this->subject->delete($this->testTable)->where(
			$this->subject->expr->greaterThanOrEquals($this->testField, 'Foo')
		);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . ' >= Foo';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function deleteAndWhere() {
		$this->subject->delete($this->testTable)->where(
			$this->subject->expr->logicalAnd(
				$this->subject->expr->lessThan($this->testField, 94839839834),
				$this->subject->expr->equals($this->testFieldSecond, 'session_name')
			)
		);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE (' . $this->testField . ' < 94839839834) AND (' . $this->testFieldSecond . ' = session_name)';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function deleteOrWhere() {
		$this->subject->delete($this->testTable)->where(
			$this->subject->expr->logicalOr(
				$this->subject->expr->lessThan($this->testField, 94839839834),
				$this->subject->expr->equals($this->testFieldSecond, 'session_name')
			)
		);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE (' . $this->testField . ' < 94839839834) OR (' . $this->testFieldSecond . ' = session_name)';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function deleteWithDynamicWhere() {
		for ($i = 0; $i < 2; ++$i) {
			if ($i === 1) {
				$sql = $this->subject->expr->logicalAnd(
							$this->subject->expr->lessThanOrEquals($this->testField, 1392291517),
							$this->subject->expr->greaterThan($this->testFieldSecond, 0)
						);

				$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE (' . $this->testField . ' <= 1392291517) AND (' . $this->testFieldSecond . ' > 0)';
			} else {
				$sql = $this->subject->expr->lessThan($this->testFieldSecond, 1392291518);

				$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testFieldSecond . ' < 1392291518';
			}

			$this->subject->delete($this->testTable)->where($sql);
			$this->assertSame($expectedSql, $this->subject->getSql());
			$this->subject = $GLOBALS['TYPO3_DB']->createDeleteQuery();
		}
	}

	/**
	 * @test
	 */
	public function deleteWithWhereFromArray(){
		$removeClauses = array();
		for ($i = 0; $i < 5; $i++) {
			if ($i === 1) {
				$removeClauses[] = $this->subject->expr->lessThanOrEquals($this->testField, $GLOBALS['EXEC_TIME']);
				$removeClauses[] = $this->subject->expr->greaterThan($this->testField, 0);
			} else if ($i === 2) {
				$removeClauses[] = $this->subject->expr->lessThan($this->testFieldSecond,$GLOBALS['EXEC_TIME']);
			} else if ($i === 3) {
				$removeClauses[] = $this->subject->expr->equals($this->testFieldSecond, 4456);
			} else {
				$removeClauses[] = $this->subject->expr->greaterThanOrEquals($this->testFieldSecond, 42);
			}
		}
		$this->subject->delete($this->testTable)->where($removeClauses);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testFieldSecond . ' >= 42 AND ' . $this->testField . ' <= ' . $GLOBALS['EXEC_TIME'] .' AND ' . $this->testField . ' > 0 AND ' . $this->testFieldSecond . ' < ' . $GLOBALS['EXEC_TIME'] . ' AND ' . $this->testFieldSecond . ' = 4456 AND '  . $this->testFieldSecond . ' >= 42';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function testDeleteWithIn() {
		$this->subject->delete($this->testTable)->where(
			$this->subject->expr->in($this->testField, array(1, 2, 3))
		);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . ' IN (1, 2, 3)';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function executeDeleteReturnsAffectedRows() {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'test'));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'foo'));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'bar'));
		$this->subject->delete($this->testTable);
		$expectedSql = 'DELETE FROM ' . $this->testTable;
		$this->assertSame($expectedSql, $this->subject->getSql());
		$this->assertSame(3, $this->subject->execute());
	}

	/**
	 * @test
	 */
	public function deleteWithSimpleWhereAndPositionalNamedParameters() {
		$this->markTestIncomplete();
		$this->subject->delete($this->testTable)->where(
			$this->subject->expr->equals($this->testTable, ':parameter1')
		);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testTable . ' = :parameter1';
		$this->assertSame($expectedSql, $this->subject->getSql());
	}


	/**
	 * @test
	 */
	public function deleteWithSimpleWherePreparedStatementExecute() {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'Test'));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'Foo'));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'Bar'));

		$this->subject->delete($this->testTable)
				->where(
					$this->subject->expr->equals($this->testField, $this->subject->bindValue('Foo', ':Foo'))
				);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . ' = :Foo';
		$this->assertSame($expectedSql, $this->subject->getSql());

		$stmt = $this->subject->prepare();
		$this->assertEquals(1, $stmt->execute());
	}

	/**
	 * @test
	 */
	public function deleteWithSimpleWherePreparedStatementExecuteOnQuery() {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'Test'));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'Foo'));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'Bar'));

		$this->subject->delete($this->testTable)
				->where(
					$this->subject->expr->equals($this->testField, $this->subject->bindValue('Foo', ':Foo'))
				);

		$expectedSql = 'DELETE FROM ' . $this->testTable . ' WHERE ' . $this->testField . ' = :Foo';
		$this->assertSame($expectedSql, $this->subject->getSql());
		$this->assertSame(1, $this->subject->execute());
	}

	/**
	 * @test
	 */
	public function deleteComplexInOrAndQuery() {
		$query = $this->subject;
		$expr = $query->expr;
		$pidList = array(1, 2, 3, 4);
		$checkKeys = array(5, 6, 7);

		$query->delete('tx_linkvalidator_link')->where(
			$expr->logicalOr(
				$expr->in('record_pid', $pidList),
				$expr->in('record_uid', $pidList)
			),
			$expr->like('table_name', 'pages'),
			$expr->in('link_type', $checkKeys)
		);

		$expectedSql = 'DELETE FROM tx_linkvalidator_link WHERE (record_pid IN (1, 2, 3, 4)) OR (record_uid IN (1, 2, 3, 4)) AND table_name LIKE pages AND link_type IN (5, 6, 7)';
		$this->assertSame($expectedSql, $query->getSql());
	}
}