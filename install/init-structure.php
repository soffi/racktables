alter database character set utf8;
set names 'utf8';

CREATE TABLE `Config` (
  `varname` char(32) NOT NULL,
  `varvalue` char(255) NOT NULL,
  `vartype` enum('string','uint') NOT NULL default 'string',
  `emptyok` enum('yes','no') NOT NULL default 'no',
  `is_hidden` enum('yes','no') NOT NULL default 'yes',
  `description` text,
  PRIMARY KEY  (`varname`)
) ENGINE=MyISAM;

CREATE TABLE `PortCompat` (
  `type1` int(10) unsigned NOT NULL,
  `type2` int(10) unsigned NOT NULL,
  KEY `type1` (`type1`),
  KEY `type2` (`type2`)
) ENGINE=MyISAM;

CREATE TABLE `Script` (
  `script_name` char(64) NOT NULL,
  `script_text` longtext,
  PRIMARY KEY  (`script_name`)
) TYPE=MyISAM;

CREATE TABLE `UserAccount` (
  `user_id` int(10) unsigned NOT NULL auto_increment,
  `user_name` char(64) NOT NULL,
  `user_password_hash` char(128) default NULL,
  `user_realname` char(64) default NULL,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `user_name` (`user_name`)
) ENGINE=MyISAM AUTO_INCREMENT=10000;

CREATE TABLE `revision` (
  `id` bigint(20) unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  `user_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `milestone` (
  `id` int(10) unsigned NOT NULL,
  `rev` bigint(20) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `operation` (
  `id` int(10) unsigned NOT NULL,
  `rev` bigint(20) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `Registry` (
  `id` char(64) NOT NULL,
  `data` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

<?php

require_once '../inc/orm.php';
$database_meta = DatabaseMeta::$database_meta;
foreach($database_meta as $tname => $tvalue)
{
	$queryMain = "CREATE TABLE $tname (
	id int unsigned not null,
";
	$queryRev = "CREATE TABLE ${tname}__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
";
	foreach ($tvalue['fields'] as $fname=>$fvalue)
	{
		$f = "$fname ".$fvalue['type']." ".($fvalue['null']?'':'not null');
		if ($fvalue['revisioned'])
			$queryRev .= "\t$f,\n";
		else
			$queryMain .= "\t$f,\n";
	}
	$queryMain .= "\tkey(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
	$queryRev .= "\tkey(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

	echo $queryMain."\n";
	echo $queryRev."\n";
}	
?>
