<?php

class Milestone {

	private static $user_id = '';

	public function setUser($u)
	{
		self::$user_id = $u;
	}

	public function getHeadMilestone()
	{
		$result = Database::getDBLink()->query("select milestone.id as id, milestone.rev as rev from milestone join (select max(rev) as rev from milestone) as m on milestone.rev = m.rev ");
		if ($row = $result->fetch())
		{
			Database::closeCursor($result);
			return $row;
		}
		else
			return array(null, null);
	}

	public function getMilestoneRev($id)
	{
		$result = Database::getDBLink()->query("select rev from milestone where id = $id");
		$row = $result->fetch();
		Database::closeCursor($result);
		return $row[0];
	}

	public function getMilestoneId($rev)
	{
		$result = Database::getDBLink()->query("select id from milestone where rev = $rev");
		if ($row = $result->fetch())
		{
			Database::closeCursor($result);
			return $row[0];
		}
		else
			return null;
	}

	public function getSupMilestone($rev)
	{
		$result = Database::getDBLink()->query("select milestone.id as id, milestone.rev as rev from milestone join (select min(rev) as rev from milestone where rev > $rev) as m on milestone.rev = m.rev ");
		$row = $result->fetch();
		Database::closeCursor($result);
		return $row;
	}

	public function getSubMilestone($rev)
	{
		$result = Database::getDBLink()->query("select milestone.id as id, milestone.rev as rev from milestone join (select max(rev) as rev from milestone where rev < $rev) as m on milestone.rev = m.rev ");
		$row = $result->fetch();
		Database::closeCursor($result);
		return $row;
	}

	public function setMilestone($rev, $comment)
	{
		Database::startTransaction();
		$result = Database::getDBLink()->query("select milestone.id as id, milestone.rev as rev from milestone join (select max(rev) as rev from milestone) as m on milestone.rev = m.rev for update");
		$row = $result->fetch();
		Database::closeCursor($result);
		if ($rev > $row[1])
		{
			$q = Database::getDBLink()->prepare("insert into milestone set id = ? , rev = ? , comment = ? , user_name = ? ");
			$q->bindValue(1, $row[0]+1);
			$q->bindValue(2, $rev);
			$q->bindValue(3, $comment);
			$q->bindValue(4, self::$user_id);
			$result = $q->execute();
			Database::closeCursor($result);
		}
			
		Database::commit();
		if ($rev > $row[1])
			return $row[0]+1;
		else
			return $row[0];
	}

	public function getMilestonesSince($rev)
	{
		$q = Database::getDBLink()->prepare("select milestone.id as id, milestone.rev as rev, milestone.comment as comment, unix_timestamp(revision.timestamp) as timestamp, milestone.user_name as user_name from milestone join revision on milestone.rev = revision.id left where milestone.rev > ?");
		$q->bindValue(1, $rev);
		$q->execute();
		$result = $q->fetchAll();
		Database::closeCursor($q);
		return $result;
	}


}

?>
