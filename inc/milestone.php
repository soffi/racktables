<?php

class Milestone {
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

	public function setMilestone($rev)
	{
		Database::startTransaction();
		$result = Database::getDBLink()->query("select milestone.id as id, milestone.rev as rev from milestone join (select max(rev) as rev from milestone) as m on milestone.rev = m.rev for update");
		$row = $result->fetch();
		Database::closeCursor($result);
		if ($rev > $row[1])
			Database::getDBLink()->exec("insert into milestone set id=".($row[0]+1).", rev = $rev");
		Database::commit();
		if ($rev > $row[1])
			return $row[0]+1;
		else
			return $row[0];
	}
}

?>
