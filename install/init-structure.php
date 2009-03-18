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
  `user_id` int(10) unsigned NOT NULL,
  `comment` text NOT NULL
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
$queryProc =  "CREATE PROCEDURE init_legacy_tables ()\nBEGIN\n";
foreach($database_meta as $tname => $tvalue)
{
	$queryMain = "CREATE TABLE ${tname}__s (
	id int unsigned not null,
";
	$queryRev = "CREATE TABLE ${tname}__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
";
	$queryViewRevFields = '';
	$queryViewStatFields = '';
	foreach ($tvalue['fields'] as $fname=>$fvalue)
	{
		$f = "$fname ".$fvalue['type']." ".($fvalue['null']?'':'not null');
		if ($fvalue['revisioned'])
		{
			$queryRev .= "\t$f,\n";
			$queryViewRevFields .= ",\n\t\t${tname}__r.$fname AS $fname";
		}
		else
		{
			$queryMain .= "\t$f,\n";
			$queryViewStatFields .= ",\n\t\t${tname}__s.$fname AS $fname";
		}
	}
	$queryMain .= "\tkey(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
	$queryRev .= "\tkey(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
	$queryDropLegacy = "DROP TABLE IF EXISTS ${tname};\n";
	$queryCreateLegacy = "CREATE TABLE ${tname} as \n\tSELECT \n\t\t${tname}__s.id AS id $queryViewRevFields $queryViewStatFields \n\tFROM \n\t\t${tname}__r \n\t\tJOIN (SELECT id, max(rev) AS rev FROM ${tname}__r GROUP BY id) AS ${tname}__vr ON ${tname}__r.id = ${tname}__vr.id and ${tname}__r.rev = ${tname}__vr.rev \n\t\tJOIN ${tname}__s ON ${tname}__s.id = ${tname}__r.id \n\tWHERE ${tname}__r.rev_terminal = 0;\n";
	echo $queryMain."\n";
	echo $queryRev."\n";
	$queryProc .= $queryDropLegacy . $queryCreateLegacy;

}
$queryProc .= "END\n";
echo "\n\ndelimiter //\n";
echo "$queryProc//\n";
echo "\n\ndelimiter ;\n\n";
echo "CALL init_legacy_tables();";
	
?>
