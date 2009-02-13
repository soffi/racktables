<?php

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
						break;
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
						break 3;
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
						break;
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
					self::$parseError = "Found ${token['token']} after table name";
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




class Database {

	private static $database_meta = array (
/*		'book' => array (
			'fields' => array (
				'name' => array (
					'revisioned' => true
				), //end of 'name' field
				'owner' => array (
					'revisioned' => false
				) //end of 'owner' field
			) //end of table fields
		), //end of table
		'chapter' => array (
			'fields' => array (
				'name' => array (
					'revisioned' => true
				), //end of 'name' field
				'book_id' => array (
					'revisioned' => false
				) //end of 'book_id' field
			) //end of table field
		) //end of table */
	);
	private static $database_meta_nonrev = array();
	private static $commitMe = true;
	private static $transactionStarted = false;

	private static $currentRevision = 'head';

	private static $lastInsertId = NULL;

	private static $dbxlink;

	public function getLastInsertId()
	{
		return self::$lastInsertId;
	}

	public function setRevision($r)
	{
		if ($r == 'head')
			self::$currentRevision = $r;
		elseif (is_numeric($r) and intval($r) > 0)
			self::$currentRevision = intval($r);
	}

	public function getRevision()
	{
		return self::$currentRevision;
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
		if (self::$currentRevision != 'head')
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

	public function init($link)
	{
		self::$dbxlink = $link;
		self::$dbxlink->exec('set session transaction isolation level read committed');
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
				$result1->closeCursor();
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
				$result1->closeCursor();
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
					$result1->closeCursor();
				}
			}
		}
		$result->closeCursor();

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
			$q->closeCursor();
			throw new Exception ("Id $id has never been registered in table $table");
		}
		$q->closeCursor();
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
		$result->closeCursor();
		self::autoCommit();
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
			$result->closeCursor();
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
				$q->closeCursor();
				if (! self::isDeleted($table, $id))
				{
					$q = self::$dbxlink->query('select max(id) from revision for update');
					$row = $q->fetch(PDO::FETCH_NUM);
					$q->closeCursor();
					$next_revision = $row[0] + 1;

					$q = self::$dbxlink->prepare("insert into ${table}__r set id = ?, rev = ?, rev_terminal=true");
					$q->bindValue(1, $id);
					$q->bindValue(2, $next_revision);
					$q->execute();
					$q->closeCursor();

					$q = self::$dbxlink->prepare("insert into revision set id = ?, timestamp = now()");
					$q->bindValue(1, $next_revision);
					$q->execute();
					$q->closeCursor();
				}
			}
			else
			{
				$q->closeCursor();
			}
			self::autoCommit();
		}
		else
		{
			$q = self::$dbxlink->prepare("delete from $table where id = ?");
			$q->bindValue(1, $id);
			$q->execute();
			$q->closeCursor();
		}

	}



	public function insert($fields, $table)
	{
		if (isset(self::$database_meta[$table]))
		{
			$staticParams = array();
			$staticValues = array();
			$revisionedParams = array();
			$revisionedValues = array();
			$modified = false;
			foreach ($fields as $column => $value)
			{
				if (!isset(self::$database_meta[$table]['fields'][$column]))
				{
					throw new Exception("Unknown field $column in table $table");
				}
				if (self::$database_meta[$table]['fields'][$column]['revisioned'] == true)
				{
					$revisionedParams[] = "$column = ?";
					$revisionedValues[] = $value;
				}
				else
				{
					$staticParams[] = "$column = ?";
					$staticValues[] = $value;
				}
			}
			self::startTransaction(true);
			$result = self::$dbxlink->query("select max(id) from $table for update");
			$row = $result->fetch(PDO::FETCH_NUM);
			$result->closeCursor();
			$next_id = $row[0] + 1;
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
			$q->closeCursor();

			$q = self::$dbxlink->prepare("insert into $table set id = ?".(count($staticParams)>0?',':'')." ".implode(', ', $staticParams));
			$q->bindValue(1, $next_id);
			$paramno = 2;
			foreach($staticValues as $value)
				$q->bindValue($paramno++, $value);
			$q->execute();
			$q->closeCursor();

			$q = self::$dbxlink->prepare("insert into revision set id = ?, timestamp = now()");
			$q->bindValue(1, $next_revision);
			$q->execute();
			$q->closeCursor();

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
				$params[] = "$column = ?";
				$values[] = $value;
			}

			$q = self::$dbxlink->prepare("insert into $table set ".implode(', ', $params));
			$paramno = 1;
			foreach($values as $value)
				$q->bindValue($paramno++, $value);
			$q->execute();
			$q->closeCursor();
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
				self::update($fields, $table, $id);
			}
			$result->closeCursor();
		}

	}



	public function update($fields, $table, $id)
	{
		if (isset(self::$database_meta[$table]))
		{
			$staticParams = array();
			$revisionedParams = array();
			$revisionedParamsOld = array();
			$modified = false;
			//creating list of possible revisioned props to fetch existing values later
			foreach (self::$database_meta[$table]['fields'] as $field => $prop)
			{
				if ($prop['revisioned'] == true)
					$revisionedParamsOld[$field] = '';
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
				$q->closeCursor();
				if (! self::isDeleted($table, $id))
				{
					//fetching existing revisioned props
					$q = self::$dbxlink->prepare("select ".implode(', ', array_keys($revisionedParamsOld))." from ${table}__r join (select id, max(rev) as rev from ${table}__r where id = ? group by id) as tmp__r on tmp__r.id = ${table}__r.id and tmp__r.rev = ${table}__r.rev for update");
					$q->bindValue(1, $id);
					$q->execute();
					$row = $q->fetch(PDO::FETCH_ASSOC);
					$q->closeCursor();
					//and comparing them to update request
					foreach ($revisionedParamsOld as $field => $prop)
					{
						$revisionedParamsOld[$field] = $row[$field];
						if (isset($revisionedParams[$field]) and $revisionedParams[$field] == $revisionedParamsOld[$field])
						{
							unset($revisionedParams[$field]);
						}
					}
					$result = self::$dbxlink->query('select max(id) from revision for update');
					$row = $result->fetch(PDO::FETCH_NUM);
					$result->closeCursor();
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
							$q->bindValue($paramno++, $value);
						$q->execute();
						$q->closeCursor();

						$q = self::$dbxlink->prepare("insert into revision set id = ?, timestamp = now()");
						$q->bindValue(1, $next_revision);
						$q->execute();
						$q->closeCursor();
					}
					if (count($staticParams) > 0)
					{
						$q = self::$dbxlink->prepare("update $table set ".self::assembleUpdateString($staticParams)." where id = ?");
						$paramno = 1;
						foreach($staticParams as $value)
							$q->bindValue($paramno++, $value);
						$q->bindValue($paramno++, id);
						$q->execute();
						$q->closeCursor();
					}
				}
				else
				{
					$result->closeCursor();
				}
			}
			self::autoCommit();
		}
		else
		{
			$q = self::$dbxlink->prepare("update $table set ".self::assembleUpdateString($fields)." where id = ?");
			$paramno = 1;
			foreach($fields as $value)
				$q->bindValue($paramno++, $value);
			$q->bindValue($paramno++, id);
			$q->execute();
			$q->closeCursor();
		}
	}

	public function query($query, $bindParams = array())
	{
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
				if ($tok['type'] == 'eos') break;
				if ($tok['lexemType'] == 'illegal')
				{
					debug_print_backtrace();
					error_log("Failed to parse '$query': ".ParseToTable::getError());
					return NULL;
				}
				if ($tok['lexemType'] == 'table')
				{
					$subst = self::substituteTable($tok['token']);
					$newQuery .= substr($query, $lastTokenPos, $tok['start']-$lastTokenPos-1);
					$newQuery .= $subst;
					$lastTokenPos = $tok['end'];
					$lastTablename = $tok['token'];
					$justFoundTable = true;
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
			$q = self::$dbxlink->prepare($query);
			foreach($bindParams as $param => $value)
				$q->bindValue($param, $value);
			$q->execute();
			return $q;
		}
		else
		{
			error_log("Unknown query type");
			return NULL;
		}
		
	}

	public function lastError()
	{
		return mysql_error();
	}

	public function get($field, $table, $id)
	{
		$result = self::query("select $field from $table where id = ?", array(1=>$id));
		list($ret) = $result->fetch(PDO::FETCH_NUM);
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
		$q->closeCursor();
		if ($appeared == -1)
			return null;
		return array($appeared, $disappeared);
	}

	private function __construct() {}
}
?>
