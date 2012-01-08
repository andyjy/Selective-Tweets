<?php
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 */
require dirname(__FILE__) . '/DB.php';
/**
 * MySQL DB Driver
 */
class MySQL extends DB
{
	protected function connect($host, $database, $user, $password)
	{
		$this->con = new PDO('mysql:host=' . $host . ';dbname=' . $database, $user, $password);
	}

	public function quote($string)
	{
		return $this->con->quote($string);
	}
}

