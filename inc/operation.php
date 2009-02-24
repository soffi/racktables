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
