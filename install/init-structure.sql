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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `PortCompat` (
  `type1` int(10) unsigned NOT NULL,
  `type2` int(10) unsigned NOT NULL,
  KEY `type1` (`type1`),
  KEY `type2` (`type2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `Script` (
  `script_name` char(64) NOT NULL,
  `script_text` longtext,
  PRIMARY KEY  (`script_name`)
) TYPE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `UserAccount` (
  `user_id` int(10) unsigned NOT NULL auto_increment,
  `user_name` char(64) NOT NULL,
  `user_password_hash` char(128) default NULL,
  `user_realname` char(64) default NULL,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `user_name` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=10000;

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

CREATE TABLE RackSpace__s (
	id int unsigned not null,
	rack_id int(10) unsigned not null,
	unit_no int(10) unsigned not null,
	atom enum('front','interior','rear') not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE RackSpace__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	state enum('A','U','T','W','F') not null,
	object_id int(10) unsigned ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Rack__s (
	id int unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Rack__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	name char(255) ,
	row_id int(10) unsigned not null,
	height tinyint(3) unsigned not null,
	comment text ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE RackRow__s (
	id int unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE RackRow__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	name char(255) not null,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE RackObject__s (
	id int unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE RackObject__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	name char(255) ,
	label char(255) ,
	barcode char(16) ,
	objtype_id int(10) unsigned not null,
	asset_no char(64) ,
	has_problems enum('yes','no') not null,
	comment text ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Port__s (
	id int unsigned not null,
	object_id int(10) unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Port__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	name char(255) not null,
	type int(10) unsigned not null,
	l2address char(64) ,
	reservation_comment char(255) ,
	label char(255) ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Link__s (
	id int unsigned not null,
	porta int(10) unsigned not null,
	portb int(10) unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Link__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	label char(255) ,
	labela char(255) ,
	labelb char(255) ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4Address__s (
	id int unsigned not null,
	ip int(10) unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4Address__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	name char(255) not null,
	reserved enum('yes','no') ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4Allocation__s (
	id int unsigned not null,
	object_id int(10) unsigned not null,
	ip int(10) unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4Allocation__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	name char(255) not null,
	type enum('regular','shared','virtual','router') ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4NAT__s (
	id int unsigned not null,
	object_id int(10) unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4NAT__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	proto enum('TCP','UDP') not null,
	localip int(10) unsigned not null,
	localport smallint(5) unsigned not null,
	remoteip int(10) unsigned not null,
	remoteport smallint(5) unsigned not null,
	description char(255) ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4Network__s (
	id int unsigned not null,
	ip int(10) unsigned not null,
	mask int(10) unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4Network__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	name char(255) ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Attribute__s (
	id int unsigned not null,
	type enum('string','uint','float','dict') ,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Attribute__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	name char(64) ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE AttributeMap__s (
	id int unsigned not null,
	objtype_id int(10) unsigned not null,
	attr_id int(10) unsigned not null,
	chapter_id int(10) unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE AttributeMap__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE AttributeValue__s (
	id int unsigned not null,
	object_id int(10) unsigned ,
	attr_id int(10) unsigned ,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE AttributeValue__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	string_value char(128) ,
	uint_value int(10) unsigned ,
	float_value float ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Dictionary__s (
	id int unsigned not null,
	chapter_id int(10) unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Dictionary__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	dict_value char(255) ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Chapter__s (
	id int unsigned not null,
	sticky enum('yes','no') ,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Chapter__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	name char(128) not null,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4LB__s (
	id int unsigned not null,
	object_id int(10) unsigned ,
	rspool_id int(10) unsigned ,
	vs_id int(10) unsigned ,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4LB__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	vsconfig text ,
	rsconfig text ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4RS__s (
	id int unsigned not null,
	rspool_id int(10) unsigned ,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4RS__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	inservice enum('yes','no') not null,
	rsip int(10) unsigned ,
	rsport smallint(5) unsigned ,
	rsconfig text ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4RSPool__s (
	id int unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4RSPool__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	name char(255) ,
	vsconfig text ,
	rsconfig text ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4VS__s (
	id int unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IPv4VS__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	vip int(10) unsigned ,
	vport smallint(5) unsigned ,
	proto enum('TCP','UDP') not null,
	name char(255) ,
	vsconfig text ,
	rsconfig text ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE TagStorage__s (
	id int unsigned not null,
	entity_realm enum('file','ipv4net','ipv4vs','ipv4rspool','object','rack','user') not null,
	entity_id int(10) unsigned not null,
	tag_id int(10) unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE TagStorage__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE TagTree__s (
	id int unsigned not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE TagTree__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	parent_id int(10) unsigned ,
	valid_realm set('file','ipv4net','ipv4vs','ipv4rspool','object','rack','user') not null,
	tag char(255) ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE FileLink__s (
	id int unsigned not null,
	file_id int(10) unsigned not null,
	entity_type enum('ipv4net','ipv4rspool','ipv4vs','object','rack','user') not null,
	entity_id int(10) not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE FileLink__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE File__s (
	id int unsigned not null,
	name char(255) not null,
	type char(255) not null,
	atime datetime not null,
	key(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE File__r (
	id int unsigned not null,
	rev bigint unsigned not null,
	rev_terminal tinyint not null,
	contents longblob not null,
	size int(10) unsigned not null,
	comment text ,
	key(id), key(rev), key(rev_terminal), unique id_rev(id, rev)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
