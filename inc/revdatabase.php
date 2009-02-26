<?php

class UniqueConstraintException extends Exception
{

}

class ObjectNotFoundException extends Exception
{

}

class OutOfRevisionRangeException extends Exception
{
	private $appeared;
	private $disappeared;
	private $table;
	private $id;
	function __construct($table, $id, $appeared, $disappeared)
	{
		parent::__construct("Object '$table' id '$id' is not available in the current revision. The lifetime is (rev#$appeared, rev#$disappeared)");
		$this->appeared = $appeared;
		$this->disappeared = $disappeared;
		$this->table = $table;
		$this->id = $id;
	}
	function getLifetime()
	{
		return array($this->appeared, $this->disappeared);
	}
	function getAppeared()
	{
		return $this->appeared;
	}
	function getDisappeared()
	{
		return $this->disappeared>-1?$this->disappeared:'head';
	}
	function getTable()
	{
		return $this->table;
	}
	function getId()
	{
		return $this->id;
	}
}


class ParseToTable
{
	private static $joins = array(
		',',
		'join',
		'left',
		'natural',
		'right',
		'inner',
		'outer' );
	private static $start_subclause = array('(');
	private static $end_subclause = array(')');
	private static $end_table = array ('on');
	private static $alias_table = array('as');
	private static $start_terms = array('from');
	private static $end_terms = array (
		'order',
		'where',
		'group',
		'having' );
	private static $special_chars = array (
		',',
		'(',
		')',
		'='
	);
	private static $delimiters = array (
		"\n",
		' ',
		"\t",
		"\r"
	);

	private static $lexerString = '';
	private static $lexerPos = NULL;
	private static $lexerState = NULL;
	private static $lastLexem = NULL;
	private static $parseError = NULL;
	private static $startToken = NULL;
	private static $endToken = NULL;

	public function getError()
	{
		return self::$parseError;
	}

	public function initLexer($string)
	{
		self::$lexerString = $string;
		self::$lexerPos = 0;
		self::$lexerState = 'unknown';
		self::$lastLexem = 'unknown';
	}

	private function getNextChar()
	{
		if (self::$lexerPos == strlen(self::$lexerString))
			return NULL;
		return substr(self::$lexerString, self::$lexerPos++, 1);
	}
	private function getBackOneChar()
	{
		if (self::$lexerPos > 0) self::$lexerPos--;
	}
	public function getToken()
	{
		$token = '';
		$tokenType = 'unknown';
		while(true)
		{
			$char = self::getNextChar();
			$charType = 'common';
			if ($char === NULL)
				$charType = 'eos';
			elseif (in_array($char, self::$special_chars))
				$charType = 'special';
			elseif (in_array($char, self::$delimiters))
				$charType = 'delimiter';
//			echo "\tGot char $char of type $charType at state ".self::$lexerState."\n";
			switch (self::$lexerState)
			{
				case 'unknown':
					switch ($charType)
					{
						case 'eos':
							self::$lexerState = 'eos';
							$tokenType = 'unknown';
						break 3;
						case 'common':
							self::$startToken = self::$lexerPos;
							self::$lexerState = 'common';
							$token .= $char;
						break;
						case 'special':
							self::$startToken = self::$lexerPos;
							self::$lexerState = 'special';
							$token .= $char;
						break 3;
						case 'delimiter':
							self::$lexerState = 'special';
						break;
					}
				break;
				case 'common':
					switch ($charType)
					{
						case 'eos':
							self::$lexerState = 'eos';
							$tokenType = 'common';
						break 3;
						case 'common':
							$token .= $char;
						break;
						case 'special':
							self::$startToken = self::$lexerPos;
							self::$lexerState = 'special';
							self::getBackOneChar();
							$tokenType = 'common';
						break 3;
						case 'delimiter':
							self::$lexerState = 'delimiter';
							$tokenType = 'common';
						break 3;
					}
				break;
				case 'special':
					switch ($charType)
					{
						case 'eos':
							self::$lexerState = 'eos';
							$tokenType = 'special';
						break 3;
						case 'common':
							self::$startToken = self::$lexerPos;
							self::$lexerState = 'common';
							self::getBackOneChar();
							$tokenType = 'special';
						break;
						case 'special':
							self::$startToken = self::$lexerPos;
							self::$lexerState = 'special';
							$token .= $char;
							$tokenType = 'special';
						break 3;
						case 'delimiter':
							self::$lexerState = 'delimiter';
						break;
					}
				break;
				case 'delimiter':
					switch ($charType)
					{
						case 'eos':
							self::$lexerState = 'eos';
							$tokenType = 'eos';
						break 3;
						case 'common':
							self::$startToken = self::$lexerPos;
							self::$lexerState = 'common';
							$token .= $char;
						break;
						case 'special':
							self::$startToken = self::$lexerPos;
							self::$lexerState = 'special';
							$token .= $char;
						break 3;
						case 'delimiter':
						break;
					}
				break;
				case 'eos':
					$tokenType = 'eos';
				break 2;
			}
		} //end of while
		return array('token'=>$token, 'type'=>$tokenType, 'start'=>self::$startToken, 'end'=>self::$lexerPos-1);
	}

	public function getLexem()
	{
		$token = self::getToken();
		$lexemType = 'unknown';
		$inParent = 0;
		switch (self::$lastLexem)
		{
			case 'unknown':
				if (in_array(strtolower($token['token']), self::$start_terms))
						self::$lastLexem = 'startTerms';
			break;
			case 'startTerms':
				if (in_array(strtolower($token['token']), self::$start_subclause))
					self::$lastLexem = 'startSubclause';
				else
					self::$lastLexem = 'table';
			break;
			case 'table':
				if (in_array(strtolower($token['token']), self::$alias_table))
					self::$lastLexem = 'aliasTable';
				elseif (in_array(strtolower($token['token']), self::$joins))
					self::$lastLexem = 'joins';
				elseif (in_array(strtolower($token['token']), self::$end_table))
					self::$lastLexem = 'endTable';
				elseif (in_array(strtolower($token['token']), self::$end_subclause))
					self::$lastLexem = 'endSubclause';
				elseif (in_array(strtolower($token['token']), self::$end_terms))
					self::$lastLexem = 'unknown';
				else
				{
					self::$lastLexem = 'illegal';
					self::$parseError = "Found ${token['token']} after table name, position ".self::$lexerPos;
				}
			break;
			case 'joins':
				if (in_array(strtolower($token['token']), self::$joins))
					self::$lastLexem = 'joins';
				elseif (in_array(strtolower($token['token']), self::$start_subclause))
					self::$lastLexem = 'startSubclause';
				else
					self::$lastLexem = 'table';
			break;
			case 'aliasTable':
				self::$lastLexem = 'tableAlias';
			break;
			case 'tableAlias':
				if (in_array(strtolower($token['token']), self::$joins))
					self::$lastLexem = 'joins';
				elseif (in_array(strtolower($token['token']), self::$end_table))
					self::$lastLexem = 'endTable';
				elseif (in_array(strtolower($token['token']), self::$end_subclause))
					self::$lastLexem = 'endSubclause';
				elseif (in_array(strtolower($token['token']), self::$end_terms))
					self::$lastLexem = 'unknown';
				else
				{
					self::$lastLexem = 'illegal';
					self::$parseError = "Found ${token['token']} after table alias";
				}
			break;
			case 'endTable':
				self::$lastLexem = 'endTableCondition';
			break;
			case 'endTableCondition':
				if (in_array(strtolower($token['token']), self::$joins))
					self::$lastLexem = 'joins';
				elseif (in_array(strtolower($token['token']), self::$end_subclause))
					self::$lastLexem = 'endSubclause';
				elseif (in_array(strtolower($token['token']), self::$end_terms))
					self::$lastLexem = 'unknown';
				else
					self::$lastLexem = 'endTableCondition';
			break;
			case 'startSubclause':
				if (in_array(strtolower($token['token']), self::$start_subclause))
					self::$lastLexem = 'startSubclause';
				else
					self::$lastLexem = 'table';
			break;
			case 'endSubclause':
				if (in_array(strtolower($token['token']), self::$end_subclause))
					self::$lastLexem = 'endSubclause';
				elseif (in_array(strtolower($token['token']), self::$joins))
					self::$lastLexem = 'joins';
				elseif (in_array(strtolower($token['token']), self::$end_terms))
					self::$lastLexem = 'unknown';
				else
				{
					self::$lastLexem = 'illegal';
					self::$parseError = "Found ${token['token']} after end of subclause";
				}
			break;
			case 'illegal':
				die("Can't recover after illegal state");
			break;
			default:
				self::$lastLexem = 'illegal';
				self::$parseError = "Unknown state ".self::$lastLexem;
			break;
			
				
		}
		$token['lexemType'] = self::$lastLexem;
		return $token;
	}

}


require_once 'inc/orm.php';


class Database {

	private static $database_meta = array();



	private static $database_meta_nonrev = array();
	private static $commitMe = true;
	private static $transactionStarted = false;

	private static $currentRevision = 'head';
	private static $userId = 0;

	private static $lastInsertId = NULL;

	private static $dbxlink;


	private static $debugLevel = 0;
	private static $debugTable = 'Rack';
	private static $debugLongQueries = 0.1;



	public function getLastInsertId()
	{
		return self::$lastInsertId;
	}

	public function setRevision($r)
	{
		if ($r == 'head')
			self::$currentRevision = $r;
		elseif (is_numeric($r) and intval($r) >= 0)
			self::$currentRevision = intval($r);
	}

	public function getRevision()
	{
		return self::$currentRevision;
	}

	public function setUser($u)
	{
		self::$userId = $u;
	}


	private function substituteTable($table)
	{
		if (!isset(self::$database_meta[$table]))
			return $table;
		$sql = "( select ${table}.id, ${table}__r.rev ";
		foreach (self::$database_meta[$table]['fields'] as $field => $prop)
		{
			$sql .= " , $field ";
		}
		$sql.= " from ${table}__r ";
		$sql .= " join ( ";
		$sql .= "select id, max(rev) as rev from ${table}__r";
		if (self::$currentRevision !== 'head')
			$sql .= " where rev <= ".self::$currentRevision;
		$sql .= " group by id ) as tmp__r on ${table}__r.id = tmp__r.id and ${table}__r.rev = tmp__r.rev ";
		$sql .= " join ${table} ";
		$sql .= " on ${table}.id = ${table}__r.id where ${table}__r.rev_terminal = 0 )";
		return $sql;
	}

	public function startTransaction($autoCommit = false)
	{
		if (self::$transactionStarted == true)
			return;
		if ($autoCommit == false)
		{
			self::$commitMe = false;
		}
		self::$dbxlink->exec('start transaction');
		self::$transactionStarted = true;
	}


	public function updateDatabaseMeta()
	{
		$result = self::$dbxlink->query('show tables');
		$tables = array();
		while($row = $result->fetch(PDO::FETCH_NUM))
		{
			if (substr($row[0], -3) == '__r')
			{
				$table = substr($row[0], 0, -3);
				if (isset(self::$database_meta_nonrev[$table]))
					unset(self::$database_meta_nonrev[$table]);
				self::$database_meta[$table] = array('fields'=>array());
				$result1 = self::$dbxlink->query("describe $table");
				while($row1 = $result1->fetch(PDO::FETCH_NUM))
				{
					$field = $row1[0];
					if ($field != 'id')
					{
						self::$database_meta[$table]['fields'][$field] = array('revisioned' => false);
						if ($row1[2] == 'NO')
							self::$database_meta[$table]['fields'][$field]['nullable'] = false;
						else
							self::$database_meta[$table]['fields'][$field]['nullable'] = true;
					}
				}
				self::closeCursor($result1);
				$result1 = self::$dbxlink->query("describe ${table}__r");
				while($row1 = $result1->fetch(PDO::FETCH_NUM))
				{
					$field = $row1[0];
					if ( ($field != 'id') &&
						($field != 'rev') &&
						($field != 'rev_terminal') )
					{
						self::$database_meta[$table]['fields'][$field] = array('revisioned' => true);
						if ($row1[2] == 'NO')
							self::$database_meta[$table]['fields'][$field]['nullable'] = false;
						else
							self::$database_meta[$table]['fields'][$field]['nullable'] = true;
					}
				}
				self::closeCursor($result1);
			}
			else
			{
				$table = $row[0];
				if (!isset(self::$database_meta[$table]))
				{
					self::$database_meta_nonrev[$table] = array('fields'=>array());
					$result1 = self::$dbxlink->query("describe $table");
					while($row1 = $result1->fetch(PDO::FETCH_NUM))
					{
						$field = $row1[0];
						if ($field != 'id')
						{
							self::$database_meta_nonrev[$table]['fields'][$field] = array('revisioned' => false);
							if ($row1[2] == 'NO')
								self::$database_meta_nonrev[$table]['fields'][$field]['nullable'] = false;
							else
								self::$database_meta_nonrev[$table]['fields'][$field]['nullable'] = true;
						}
					}
					self::closeCursor($result1);
				}
			}
		}
		self::closeCursor($result);	
	}


	public function init($link)
	{
		self::$dbxlink = $link;
		self::$dbxlink->exec('set session transaction isolation level read committed');
		self::$database_meta = DatabaseMeta::$database_meta;

	}

	public function getDBLink()
	{
		return self::$dbxlink;
	}

	public function commit()
	{
		self::$dbxlink->exec('commit');
		self::$transactionStarted = false;
	}

	private function autoCommit()
	{
		if (self::$commitMe == true)
		{
			self::commit();
		}
	}

	private function isDeleted($table, $id)
	{
		$q = self::$dbxlink->prepare("select rev_terminal from (select id, max(rev) as rev from ${table}__r where id = ? group by id) as tmp__r join ${table}__r on ${table}__r.id = tmp__r.id and ${table}__r.rev = tmp__r.rev for update");
		$q->bindValue(1, $id);
		$q->execute();
		if (!($row = $q->fetch(PDO::FETCH_NUM)))
		{
			self::closeCursor($q);
			throw new Exception ("Id $id has never been registered in table $table");
		}
		self::closeCursor($q);
		if ($row[0] == 0)
			return false;
		else
			return true;
	}

	public function getHeadRevision()
	{
		self::startTransaction(true);
		$result = self::$dbxlink->query('select max(id) from revision for update');
		$row = $result->fetch(PDO::FETCH_NUM);
		self::closeCursor($result);
		self::autoCommit();
		return $row[0];
	}

	public function getObjectsChangedBetween($rev1, $rev2, $table)
	{
		$sql = "select distinct id from ${table}__r where rev > ? and rev <= ?";
		$q = self::$dbxlink->prepare($sql);
		$q->bindValue(1, $rev1);
		$q->bindValue(2, $rev2);
		$q->execute();
		return $q;
	}

	public function getHeadRevisionForObject($table, $id)
	{
		$sql = "select max(rev) from ${table}__r where id = ?";
		$q = self::$dbxlink->prepare($sql);
		$q->bindValue(1, $id);
		$q->execute();
		$row = $q->fetch();
		self::closeCursor($q);
		return $row[0];
	}

	public function getRevisionForObjectLessThan($table, $id, $revision)
	{
		$sql = "select max(rev) from ${table}__r where id = ? and rev < ?";
		$q = self::$dbxlink->prepare($sql);
		$q->bindValue(1, $id);
		$q->bindValue(2, $revision);
		$q->execute();
		$row = $q->fetch();
		self::closeCursor($q);
		return $row[0];
	}

	public function getTailRevisionForObject($table, $id)
	{
		$sql = "select min(rev) from ${table}__r where id = ?";
		$q = self::$dbxlink->prepare($sql);
		$q->bindValue(1, $id);
		$q->execute();
		$row = $q->fetch();
		self::closeCursor($q);
		return $row[0];
	}

	public function deleteWhere($table, $fields)
	{
		$where = '';
		$bindPosition = 1;
		$bindParams = array();
		foreach($fields as $key => $value)
		{
			if ($where == '')
			{
				$where = "$key = ?";
			}
			else
			{
				$where .= " and $key = ?";
			}
			$bindParams[$bindPosition] = $value;
			$bindPosition++;
		}

		if ($where!='')
		{
			$result = self::query("select id from $table where $where", $bindParams);
			while(list($id) = $result->fetch(PDO::FETCH_NUM))
			{
				self::delete($table, $id);
			}
			self::closeCursor($result);
		}

	}

	public function delete($table, $id)
	{
		if (isset(self::$database_meta[$table]))
		{
			self::startTransaction(true);
			$q = self::$dbxlink->prepare("select id from $table where id = ? for update");
			$q->bindValue(1, $id);
			$q->execute();
			if ($q->rowCount() > 0)
			{
				self::closeCursor($q);
				if (! self::isDeleted($table, $id))
				{
					$q = self::$dbxlink->query('select max(id) from revision for update');
					$row = $q->fetch(PDO::FETCH_NUM);
					self::closeCursor($q);
					$next_revision = $row[0] + 1;

					$q = self::$dbxlink->prepare("insert into ${table}__r set id = ?, rev = ?, rev_terminal=true");
					$q->bindValue(1, $id);
					$q->bindValue(2, $next_revision);
					$q->execute();
					self::closeCursor($q);

					$q = self::$dbxlink->prepare("insert into revision set id = ?, timestamp = now(), user_id = ?");
					$q->bindValue(1, $next_revision);
					$q->bindValue(2, self::$userId);
					$q->execute();
					self::closeCursor($q);
				}
			}
			else
			{
				self::closeCursor($q);
			}
			self::autoCommit();
		}
		else
		{
			$q = self::$dbxlink->prepare("delete from $table where id = ?");
			$q->bindValue(1, $id);
			$q->execute();
			self::closeCursor($q);
		}

	}

	public function checkUniqueConstraints($table, $revisionedParams, $staticParams, $exceptId = NULL)
	{
		$mergedParams = array_merge($revisionedParams, $staticParams);
		if(isset(self::$database_meta[$table]))
		{
			$uniques = self::$database_meta[$table]['constraints']['unique'];

			foreach($uniques as $uniq)
			{
				$values = array();
				$sql = "select count(*) from ( ${table}__r join (select max(rev) as rev, id from ${table}__r group by id) as __t on ${table}__r.id = __t.id and ${table}__r.rev = __t.rev ) join $table on __t.id = $table.id where rev_terminal = 0";
				foreach ($uniq as $field)
				{
					if (gettype($mergedParams[$field]) == 'array')
					{
						$sql .= " and $field = ".$mergedParams[$field]['left']." ? ".$mergedParams[$field]['right'];
						$values[] = $mergedParams[$field]['value'];
					}
					else
					{
						$sql .= " and $field = ?";
						$values[] = $mergedParams[$field];
					}
				}
				if (isset($exceptId))
					$sql .= " and __t.id != ?";
				$sql .= " for update";
				$q = self::$dbxlink->prepare($sql);
				$pos = 1;
				foreach($values as $value)
					$q->bindValue($pos++, $value);
				if (isset($exceptId))
					$q->bindValue($pos++, $exceptId);
				$q->execute();
				$row = $q->fetch();
				if ($row[0] > 0)
				{
					$error = "Duplicate entry (";
					$first = true;
					foreach($values as $value)
					{
						if (!$first)
							$error .= ", ";
						$first = false;
						$error .= "'$value'";
					}
					$error .= ") for key (".implode(', ', $uniq).")";
					throw new UniqueConstraintException($error);
				}
			}
		}
		return TRUE;
	}

	public function insert($fields, $table)
	{
		if (isset(self::$database_meta[$table]))
		{
			$staticParams = array();
			$staticValues = array();
			$revisionedParams = array();
			$revisionedValues = array();
			$revisionedParamsMap = array();
			$staticParamsMap = array();
			$modified = false;
			foreach ($fields as $column => $value)
			{
				if (!isset(self::$database_meta[$table]['fields'][$column]))
				{
					throw new Exception("Unknown field $column in table $table");
				}
				
				if (gettype($value) == 'array')
				{
					$addParam = "$column = ${value['left']} ? ${value['right']}";
					$addValue = $value['value'];
				}
				else
				{
					$addParam = "$column = ?";
					$addValue = $value;
				}
				if (self::$database_meta[$table]['fields'][$column]['revisioned'] == true)
				{
					$revisionedParams[] = $addParam;
					$revisionedValues[] = $addValue;
					$revisionedParamsMap[$column] = $value;
				}
				else
				{
					$staticParams[] = $addParam;
					$staticValues[] = $addValue;
					$staticParamsMap[$column] = $value;
				}
			}
			self::startTransaction(true);
			self::checkUniqueConstraints($table, $revisionedParamsMap, $staticParamsMap);
			$result = self::$dbxlink->query("select max(id) from $table for update");
			$row = $result->fetch(PDO::FETCH_NUM);
			self::closeCursor($result);
			$next_id = $row[0] + 1;
			if (isset(self::$database_meta[$table]['start_increment']))
				$next_id = max($next_id, self::$database_meta[$table]['start_increment']);
			$result = self::$dbxlink->query('select max(id) from revision for update');
			$row = $result->fetch(PDO::FETCH_NUM);
			$next_revision = $row[0] + 1;

			$q = self::$dbxlink->prepare("insert into ${table}__r set id = ?, rev = ?".(count($revisionedParams)>0?',':'')." ".implode(', ', $revisionedParams));
			$q->bindValue(1, $next_id);
			$q->bindValue(2, $next_revision);
			$paramno = 3;
			foreach($revisionedValues as $value)
				$q->bindValue($paramno++, $value);
			$q->execute();
			self::closeCursor($q);

			$q = self::$dbxlink->prepare("insert into $table set id = ?".(count($staticParams)>0?',':'')." ".implode(', ', $staticParams));
			$q->bindValue(1, $next_id);
			$paramno = 2;
			foreach($staticValues as $value)
				$q->bindValue($paramno++, $value);
			$q->execute();
			self::closeCursor($q);

			$q = self::$dbxlink->prepare("insert into revision set id = ?, timestamp = now(), user_id = ?");
			$q->bindValue(1, $next_revision);
			$q->bindValue(2, self::$userId);
			$q->execute();
			self::closeCursor($q);

			self::autoCommit();
			self::$lastInsertId = $next_id;
			return $next_id;
		}
		else
		{
			$params = array();
			$values = array();
			foreach ($fields as $column => $value)
			{
				if (gettype($value) == 'array')
				{
					$params[] = "$column = ${value['left']} ? ${value['right']}";
					$values[] = $value['value'];
				}
				else
				{
					$params[] = "$column = ?";
					$values[] = $value;
				}
			}
			$query = "insert into $table set ".implode(', ', $params);
			$q = self::$dbxlink->prepare($query);
			$paramno = 1;
			foreach($values as $value)
				$q->bindValue($paramno++, $value);
			$q->execute();
			self::closeCursor($q);
			$q = self::$dbxlink->query("select last_insert_id()");
			list($lastId) = $q->fetch(PDO::FETCH_NUM);
			self::closeCursor($q);
			self::$lastInsertId = $lastId;
			return $lastId;
		}

	}

	private function assembleUpdateString($params)
	{
		$first = true;
		$s = '';
		foreach($params as $key => $value)
		{
			if ($first != true)
				$s .= ', ';
			if (gettype($value) == 'array')
				$s .= "$key = ${value['left']} ? ${value['right']}";
			else
				$s .= "$key = ?";
			$first = false;
		}
		return $s;
	}

	public function updateWhere($fields, $table, $cond)
	{
		$where = '';
		$bindParams = array();
		$bindPosition = 1;
		foreach($cond as $key => $value)
		{
			if ($where != '')
				$where .= ' and ';
			if (gettype($value) == 'array')
			{
				$where .= "$key = ${value['left']} ? ${value['right']}";
				$bindParams[$bindPosition] = $value['value'];
			}
			else
			{
				$where .= "$key = ?";
				$bindParams[$bindPosition] = $value;
			}
			$bindPosition++;
		}

		if ($where!='')
		{
			$result = self::query("select id from $table where $where", $bindParams);
			while(list($id) = $result->fetch(PDO::FETCH_NUM))
			{
				self::update($fields, $table, $id);
			}
			self::closeCursor($result);
		}

	}



	public function update($fields, $table, $id)
	{
		if (isset(self::$database_meta[$table]))
		{
			$staticParams = array();
			$staticParamsOld = array();
			$revisionedParams = array();
			$revisionedParamsOld = array();
			$modified = false;
			//creating list of possible revisioned props to fetch existing values later
			foreach (self::$database_meta[$table]['fields'] as $field => $prop)
			{
				if ($prop['revisioned'] == true)
					$revisionedParamsOld[$field] = '';
				else
					$staticParamsOld[$field] = '';
			}
			//separating revisioned and static props
			foreach ($fields as $column => $value)
			{
				if (!isset(self::$database_meta[$table]['fields'][$column]))
				{
					throw new Exception("Unknown field $column in table $table");
				}
				if (self::$database_meta[$table]['fields'][$column]['revisioned'] == true)
				{
					$revisionedParams[$column] = $value;
				}
				else
				{
					$staticParams[$column] = $value;
				}
			}

			self::startTransaction(true);
			$q = self::$dbxlink->prepare("select id from $table where id = ? for update");
			$q->bindValue(1, $id);
			$q->execute();
			if ($q->rowCount() > 0)
			{
				self::closeCursor($q);
				if (! self::isDeleted($table, $id))
				{
					//fetching existing revisioned props
					$q = self::$dbxlink->prepare("select ".implode(', ', array_keys($revisionedParamsOld))." from ${table}__r join (select id, max(rev) as rev from ${table}__r where id = ? group by id) as tmp__r on tmp__r.id = ${table}__r.id and tmp__r.rev = ${table}__r.rev for update");
					$q->bindValue(1, $id);
					$q->execute();
					$row = $q->fetch(PDO::FETCH_ASSOC);
					self::closeCursor($q);
					//and comparing them to update request
					foreach ($revisionedParamsOld as $field => $prop)
					{
						$revisionedParamsOld[$field] = $row[$field];
						if (array_key_exists($field, $revisionedParams) and $revisionedParams[$field] == $revisionedParamsOld[$field])
						{
							unset($revisionedParams[$field]);
						}
					}
					if (count($staticParamsOld) > 0)
					{
						//and fetching existing static props (just to have them for the check constraints stuff)
						$q = self::$dbxlink->prepare("select id, ".implode(', ', array_keys($staticParamsOld))." from ${table} where id = ? for update");
						$q->bindValue(1, $id);
						$q->execute();
						$row = $q->fetch(PDO::FETCH_ASSOC);
						self::closeCursor($q);
						foreach ($staticParamsOld as $field => $prop)
						{
							$staticParamsOld[$field] = $row[$field];
						}
					}
					
					self::checkUniqueConstraints($table, array_merge($revisionedParamsOld, $revisionedParams), array_merge($staticParamsOld, $staticParams), $id);


					$result = self::$dbxlink->query('select max(id) from revision for update');
					$row = $result->fetch(PDO::FETCH_NUM);
					self::closeCursor($result);
					$next_revision = $row[0] + 1;
					if (count($revisionedParams) > 0)
					{
						//If we have at least something to update, we need to preserve other revisioned props
						$revisionedParams = array_merge($revisionedParamsOld, $revisionedParams);
						$q = self::$dbxlink->prepare("insert into ${table}__r set id = ?, rev = ?, ".self::assembleUpdateString($revisionedParams));
						$q->bindValue(1, $id);
						$q->bindValue(2, $next_revision);
						$paramno = 3;
						foreach($revisionedParams as $value)
							if (gettype($value) == 'array')
								$q->bindValue($paramno++, $value['value']);
							else
								$q->bindValue($paramno++, $value);
						$q->execute();
						self::closeCursor($q);

						$q = self::$dbxlink->prepare("insert into revision set id = ?, timestamp = now(), user_id = ?");
						$q->bindValue(1, $next_revision);
						$q->bindValue(2, self::$userId);
						$q->execute();
						self::closeCursor($q);
					}
					if (count($staticParams) > 0)
					{
						$q = self::$dbxlink->prepare("update $table set ".self::assembleUpdateString($staticParams)." where id = ?");
						$paramno = 1;
						foreach($staticParams as $value)
							if (gettype($value) == 'array')
								$q->bindValue($paramno++, $value['value']);
							else
								$q->bindValue($paramno++, $value);
						$q->bindValue($paramno++, $id);
						$q->execute();
						self::closeCursor($q);
					}
				}
				else
				{
					self::closeCursor($result);
				}
			}
			self::autoCommit();
		}
		else
		{
			$q = self::$dbxlink->prepare("update $table set ".self::assembleUpdateString($fields)." where id = ?");
			$paramno = 1;
			foreach($fields as $value)
				if (gettype($value) == 'array')
					$q->bindValue($paramno++, $value['value']);
				else
					$q->bindValue($paramno++, $value);
			$q->bindValue($paramno++, id);
			$q->execute();
			self::closeCursor($q);
		}
	}

	public function getHistory($table, $id)
	{
		if (isset(self::$database_meta[$table]))
		{
			$fields = array(
				$table.'__r.id as id',
				'rev', 
				'rev_terminal',
				'unix_timestamp(revision.timestamp) as timestamp',
				'UserAccount.user_id as user_id', 
				'UserAccount.user_name as user_name'
			);
			foreach(self::$database_meta[$table]['fields'] as $fname => $fvalue)
				if ($fvalue['revisioned'])
					$fields[] = $fname;
			$q = self::$dbxlink->prepare(
				"select ".
				implode(', ', $fields).
				" from ${table}__r ".
				"join revision on ${table}__r.rev = revision.id ".
				"join UserAccount on revision.user_id = UserAccount.user_id ".
				(isset($id)?"where ${table}__r.id = ? ":'').
				"order by rev");
			if (isset($id))
				$q->bindValue(1, $id);
			$q->execute();
			return $q;
		}
		else
		{
			throw new Exception("Table $table is not versionized");
		}
	}

	public function getStatic($table, $id)
	{
		if (isset(self::$database_meta[$table]))
		{
			$fields = array(
				'id'
			);
			foreach(self::$database_meta[$table]['fields'] as $fname => $fvalue)
				if (!$fvalue['revisioned'])
					$fields[] = $fname;
			$q = self::$dbxlink->prepare(
				"select ".
				implode(', ', $fields).
				" from ${table} ".
				(isset($id)?"where ${table}.id = ? ":'')
			);
			if (isset($id))
				$q->bindValue(1, $id);
			$q->execute();
			return $q;
		}
		else
		{
			throw new Exception("Table $table is not versionized");
		}
	}

	public function query($query, $bindParams = array())
	{
		$foundDebugTable = false;
		ParseToTable::initLexer($query);
		$firstTok = ParseToTable::getLexem();
		if (strtolower($firstTok['token']) == 'select')
		{
			$justFoundTable = false;
			$lastTablename = '';
			$newQuery = '';
			$lastTokenPos = 0;
			while (true)
			{
				$tok = ParseToTable::getLexem();
				if ($tok['type'] == 'eos')
				{
					if ($justFoundTable)
						$newQuery .=  ' as '.$lastTablename;
					$justFoundTable = false;
					break;
				}
				if ($tok['lexemType'] == 'illegal')
				{
					throw new Exception ("Failed to parse '$query', parsed '$newQuery' so far: ".ParseToTable::getError()); 
				}
				if ($tok['lexemType'] == 'table')
				{
					$subst = self::substituteTable($tok['token']);
					$newQuery .= substr($query, $lastTokenPos, $tok['start']-$lastTokenPos-1);
					$newQuery .= $subst;
					$lastTokenPos = $tok['end'];
					$lastTablename = $tok['token'];
					$justFoundTable = true;
					if ($lastTablename == self::$debugTable)
						$foundDebugTable = true;
				}
				else
				{
					if ($justFoundTable && $tok['lexemType'] != 'aliasTable')
					{
						$newQuery .=  ' as '.$lastTablename.' ';
					}
					$justFoundTable = false;
				}
			}
			$newQuery .= substr($query, $lastTokenPos);
			$query = $newQuery;
			if ($foundDebugTable and self::$debugLevel>0)
				error_log($query);
			try {
				$q = self::$dbxlink->prepare($query);
				foreach($bindParams as $param => $value)
					$q->bindValue($param, $value);
				$t1 = microtime(true);
				$q->execute();
				$t2 = microtime(true);
				if ($foundDebugTable and self::$debugLevel>1)
					error_log("Query took ".($t2-$t1)." seconds");
				if (self::$debugLongQueries>0 and ($t2-$t1)>self::$debugLongQueries)
					error_log("Long query '$query' took ".($t2-$t1)." seconds");
			} catch (Exception $e) {
				throw new Exception ($e->getMessage()." Computed query: ".$query, $e->getCode());
			}
			return $q;
		}
		else
		{
			throw new Exception ("Unknown query type");
		}
		
	}

	public function get($field, $table, $id)
	{
		$result = self::query("select $field from $table where id = ?", array(1=>$id));
		list($ret) = $result->fetch(PDO::FETCH_NUM);
		self::closeCursor($result);
		return $ret;
	}

	public function lifetime($table, $id)
	{
		$appeared = -1;
		$disappeared = -1;
		$revs = array();
		$q = self::$dbxlink->prepare("select rev, rev_terminal  from ${table}__r where id = ? order by rev");
		$q->bindValue(1, $id);
		$q->execute();
		while($row = $q->fetch(PDO::FETCH_NUM))
		{
			if ($appeared == -1)
				$appeared = $row[0];
			if ($row[1] == 1)
			{
				$disappeared = $row[0]-1;
			}
		}
		self::closeCursor($q);
		if ($appeared == -1)
			return null;
		return array($appeared, $disappeared);
	}

	public function inLifetime($table, $id)
	{
		$lifetime = self::lifetime($table, $id);
		if (is_null($lifetime))
			throw new ObjectNotFoundException("Object '$table' id '$id' is not found");
		list($appeared, $disappeared) = $lifetime;
		if (self::$currentRevision >= $appeared and (self::$currentRevision < $disappeared or $disappeared == -1 ))
			return array($appeared, $disappeared);
		throw new OutOfRevisionRangeException($table, $id, $appeared, $disappeared);
	}

	public function closeCursor(&$result)
	{
		$result->closeCursor();
		unset($result);
	}

	private function __construct() {}
}
?>
