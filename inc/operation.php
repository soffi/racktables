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
		$q->closeCursor();

		self::$headRev = Database::getHeadRevision();
	}

	public function setUser($u)
	{
		self::$user_id = $u;
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
		$q->closeCursor();
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
			$q->closeCursor();
		}
		Database::commit();
	}
}

?>
