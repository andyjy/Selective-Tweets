<?php
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 */

/**
 * DB interface/wrapper
 *
 * Uses PDO
 * Concrete subclasses implement for different DB drivers
 */
abstract class DB
{
	/** @var  PDO  Connection object **/
	protected $con;

	/**
	 * connect to the DB
	 *
	 * Initialises $this->con with PDO instance
	 * override in driver-specific subclasses
	 */
	abstract protected function connect($host, $database, $user, $password);

	/**
	 * sql-escape the given value for insertion into SQL
	 *
	 * Override in driver-specific subclasses
	 *
	 * @return  string The input $string enclosed by quotes and escaped as appropriate
	 */
	abstract function quote($string);

	/**
	 * factory
	 * 
	 * @return  DB  Instance of the driver class
	 */
	public static function factory($host, $database, $user, $password)
	{
		$instance = new static;
		$instance->connect($host, $database, $user, $password);
		return $instance;
	}

	/**
	 * exec sql and return number of affected rows
	 * - wrapper for PDO::exec($sql) with error handling
	 * for non-crititcal queries set $throw_exception to false
	 */
	public function exec($sql, $throw_exception = true, $log_error = true)
	{
		try {
			$affected_rows = $this->con->exec($sql);
			if ($affected_rows === false) {
				throw new Exception('SQL exec failed');
			}
			return $affected_rows;
		} catch (Exception $e) {
			if ($throw_exception) {
				throw $e;
			} else {
				if ($log_error) {
					// we only log error if we're not throwing it
					trigger_error('exec failed: ' . $e->getCode() . ' ' . $e->getMessage() . ' SQL:' . $sql);
					// ErrorHandler::logExceptionWithInfo($e, $sql);
				}
				return false;
			}
		}
	}

	/**
	 * exec sql and return result as PDOStatement
	 * - wrapper for PDO::query($sql) with error handling
	 * for non-crititcal queries set $throw_exception to false
	 *
	 * @param string     $sql to execute
	 * @param bool       $thow_exception on failure
	 * @param PDO        $con  PDO Connection
	 *
	 * @return PDOStatement
	 */
	public function query($sql, $throw_exception = true)
	{
		try {
			$stmt = $this->con->query($sql);
			if ($stmt === false) {
				throw new Exception('SQL query failed');
			}
			$stmt->setFetchMode(PDO::FETCH_ASSOC);
			return $stmt;
		} catch (Exception $e) {
			if ($throw_exception) {
				throw $e;
			} else {
				if ($log_error) {
					// we only log error if we're not throwing it
					trigger_error('query failed: ' . $e->getCode() . ' ' . $e->getMessage() . ' SQL:' . $sql);
					// ErrorHandler::logExceptionWithInfo($e, $sql);
				}
				return false;
			}
		}
	}

	/**
	 * Similar to query() but for queries that return a single value - 1 row 1 column, i.e. scalar vs. vector
	 */
	public function queryScalar($sql, $throw_exception = true)
	{
		$stmt = $this->query($sql, $throw_exception);
		if ($stmt && $row = $stmt->fetch()) {
			return reset($row);
		}
	}

	/**
	 * Return the last autoincrement column value from an insert exec() statement
	 */
	public static function lastInsertId()
	{
		return $this->con->lastInsertId();
	}
}

