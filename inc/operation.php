<?php
class Operation {

	private static $maxop;
	private static $headRev;
	private static $user_id = 0;

	public function init()
	{
		Database::startTransaction();

		$q = Database::getDBLink()->query("select max(id) from operation");
		$row = $q->fetch();
		self::$maxop = $row[0];
		Database::closeCursor($q);

		self::$headRev = Database::getHeadRevision();
	}

	public function setUser($u)
	{
		self::$user_id = $u;
	}

	public function getOperationsSince($rev)
	{
		$q = Database::getDBLink()->prepare("select operation.id as id, operation.rev as rev, unix_timestamp(revision.timestamp) as timestamp, UserAccount.user_name as user_name from operation join revision on operation.rev = revision.id left join UserAccount on operation.user_id = UserAccount.user_id where operation.rev > ?");
		$q->bindValue(1, $rev);
		$q->execute();
		$result = $q->fetchAll();
		Database::closeCursor($q);
		return $result;
	}

        public function getHeadOperation()
        {
                $result = Database::getDBLink()->query("select operation.id as id, operation.rev as rev from operation join (select max(rev) as rev from operation) as o on operation.rev = o.rev ");
                if ($row = $result->fetch())
                {
                        Database::closeCursor($result);
                        return $row;
                }
                else
                        return array(null, null);
        }


	public function getOperationId($rev)
	{
		$result = Database::getDBLink()->query("select id from operation where rev = $rev");
		if ($row = $result->fetch())
		{
			Database::closeCursor($result);
			return $row[0];
		}
		else
			return null;
	}

	public function getOperation($op)
	{
		$result = Database::getDBLink()->query("select id, rev, user_id from operation where id = $op");
		if ($row = $result->fetch())
		{
			$result->closeCursor();
			return $row;
		}
		else
			return null;
	}


	public function getSupOperation($rev)
	{
		$result = Database::getDBLink()->query("select operation.id as id, operation.rev as rev from operation join (select min(rev) as rev from operation where rev > $rev) as o on operation.rev = o.rev ");
		$row = $result->fetch();
		Database::closeCursor($result);
		return $row;
	}

	public function getSubOperation($rev)
	{
		$result = Database::getDBLink()->query("select operation.id as id, operation.rev as rev from operation join (select max(rev) as rev from operation where rev < $rev) as o on operation.rev = o.rev ");
		$row = $result->fetch();
		Database::closeCursor($result);
		return $row;
	}

	public function getCorrespondingOperation($rev)
	{
		if ($rev === 'head')
			return self::getHeadOperation();;
		$op = self::getOperationId($rev);
		if (is_null($op))
		{
			$row = self::getSupOperation($rev);
			if (is_null($row))
				throw new Exception ("Got unknown operation for revision $rev");
			return $row; //that will be 'id'=>..., 'rev'=>...
		}
		return array(0=>$op, 1=>$rev, 'id'=>$op, 'rev'=>$rev);
	}

	public function getOperationsForHistory($history)
	{
		$lastRevRow = end($history);
		$lastRev = $lastRevRow['rev'];
		$firstRevRow = reset($history);
		$firstRev = $firstRevRow['rev'];
		$lastOp = 0;
		$operations = array();
		$output = array();
		$q = Database::getDBLink()->prepare("select id, rev, user_id from operation where rev >= ? and rev< ? or rev = (select min(rev) from operation where rev >= ?) order by rev");
		$q->bindValue(1, $firstRev);
		$q->bindValue(2, $lastRev);
		$q->bindValue(3, $lastRev);
		$q->execute();
		while($row = $q->fetch())
		{
			$operations[$row['rev']] = $row;
			$lastOp = $row['rev'];
		}
		Database::closeCursor($q);
		$foundRev = NULL;
		for($rev = $firstRev; $rev <= $lastOp; $rev++)
		{
			if (isset($history[$rev]) and isset($operations[$rev]))
			{
				$output[$rev] = $history[$rev];
				$foundRev = NULL;
			}
			elseif (isset($history[$rev]))
			{
				$foundRev = $rev;
			}
			elseif (isset($operations[$rev]))
			{
				if (isset($foundRev))
				{
					$output[$rev] = $history[$foundRev];
					$foundRev = NULL;
				}
			}
		}
		
		return $output; 
	}

	public function getRevisionsForOperation($op)
	{
		$operation = self::getOperation($op);
		if (!isset($operation))
			throw new Exception("Can't find operation $op");
		$revision = $operation[1];
		if ($op == 0)
		{
			$prev_revision = -1;
		}
		else
		{
			$prev_operation = self::getOperation($op - 1);
			$prev_revision = $prev_operation[1];
		}

		$ret = Database::getMainHistory($prev_revision, $revision);
		return $ret;
	}

	public function finalize()
	{
		$newRev = Database::getHeadRevision();
		if ($newRev != self::$headRev)
		{
			$q = Database::getDBLink()->prepare("insert into operation set id = ?, rev = ?, user_id = ?");
			$q->bindValue(1, self::$maxop + 1);
			$q->bindValue(2, $newRev);
			$q->bindValue(3, self::$user_id);
			$q->execute();
			Database::closeCursor($q);
		}
		Database::commit();
	}
}

?>
