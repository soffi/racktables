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



delimiter //
CREATE PROCEDURE init_legacy_tables ()
BEGIN
DROP TABLE IF EXISTS RackSpace;
CREATE TABLE RackSpace as 
	SELECT 
		RackSpace__s.id AS id ,
		RackSpace__r.state AS state,
		RackSpace__r.object_id AS object_id ,
		RackSpace__s.rack_id AS rack_id,
		RackSpace__s.unit_no AS unit_no,
		RackSpace__s.atom AS atom 
	FROM 
		RackSpace__r 
		JOIN (SELECT id, max(rev) AS rev FROM RackSpace__r GROUP BY id) AS RackSpace__vr ON RackSpace__r.id = RackSpace__vr.id and RackSpace__r.rev = RackSpace__vr.rev 
		JOIN RackSpace__s ON RackSpace__s.id = RackSpace__r.id 
	WHERE RackSpace__r.rev_terminal = 0;
DROP TABLE IF EXISTS Rack;
CREATE TABLE Rack as 
	SELECT 
		Rack__s.id AS id ,
		Rack__r.name AS name,
		Rack__r.row_id AS row_id,
		Rack__r.height AS height,
		Rack__r.comment AS comment  
	FROM 
		Rack__r 
		JOIN (SELECT id, max(rev) AS rev FROM Rack__r GROUP BY id) AS Rack__vr ON Rack__r.id = Rack__vr.id and Rack__r.rev = Rack__vr.rev 
		JOIN Rack__s ON Rack__s.id = Rack__r.id 
	WHERE Rack__r.rev_terminal = 0;
DROP TABLE IF EXISTS RackRow;
CREATE TABLE RackRow as 
	SELECT 
		RackRow__s.id AS id ,
		RackRow__r.name AS name  
	FROM 
		RackRow__r 
		JOIN (SELECT id, max(rev) AS rev FROM RackRow__r GROUP BY id) AS RackRow__vr ON RackRow__r.id = RackRow__vr.id and RackRow__r.rev = RackRow__vr.rev 
		JOIN RackRow__s ON RackRow__s.id = RackRow__r.id 
	WHERE RackRow__r.rev_terminal = 0;
DROP TABLE IF EXISTS RackObject;
CREATE TABLE RackObject as 
	SELECT 
		RackObject__s.id AS id ,
		RackObject__r.name AS name,
		RackObject__r.label AS label,
		RackObject__r.barcode AS barcode,
		RackObject__r.objtype_id AS objtype_id,
		RackObject__r.asset_no AS asset_no,
		RackObject__r.has_problems AS has_problems,
		RackObject__r.comment AS comment  
	FROM 
		RackObject__r 
		JOIN (SELECT id, max(rev) AS rev FROM RackObject__r GROUP BY id) AS RackObject__vr ON RackObject__r.id = RackObject__vr.id and RackObject__r.rev = RackObject__vr.rev 
		JOIN RackObject__s ON RackObject__s.id = RackObject__r.id 
	WHERE RackObject__r.rev_terminal = 0;
DROP TABLE IF EXISTS Port;
CREATE TABLE Port as 
	SELECT 
		Port__s.id AS id ,
		Port__r.name AS name,
		Port__r.type AS type,
		Port__r.l2address AS l2address,
		Port__r.reservation_comment AS reservation_comment,
		Port__r.label AS label ,
		Port__s.object_id AS object_id 
	FROM 
		Port__r 
		JOIN (SELECT id, max(rev) AS rev FROM Port__r GROUP BY id) AS Port__vr ON Port__r.id = Port__vr.id and Port__r.rev = Port__vr.rev 
		JOIN Port__s ON Port__s.id = Port__r.id 
	WHERE Port__r.rev_terminal = 0;
DROP TABLE IF EXISTS Link;
CREATE TABLE Link as 
	SELECT 
		Link__s.id AS id ,
		Link__r.label AS label,
		Link__r.labela AS labela,
		Link__r.labelb AS labelb ,
		Link__s.porta AS porta,
		Link__s.portb AS portb 
	FROM 
		Link__r 
		JOIN (SELECT id, max(rev) AS rev FROM Link__r GROUP BY id) AS Link__vr ON Link__r.id = Link__vr.id and Link__r.rev = Link__vr.rev 
		JOIN Link__s ON Link__s.id = Link__r.id 
	WHERE Link__r.rev_terminal = 0;
DROP TABLE IF EXISTS IPv4Address;
CREATE TABLE IPv4Address as 
	SELECT 
		IPv4Address__s.id AS id ,
		IPv4Address__r.name AS name,
		IPv4Address__r.reserved AS reserved ,
		IPv4Address__s.ip AS ip 
	FROM 
		IPv4Address__r 
		JOIN (SELECT id, max(rev) AS rev FROM IPv4Address__r GROUP BY id) AS IPv4Address__vr ON IPv4Address__r.id = IPv4Address__vr.id and IPv4Address__r.rev = IPv4Address__vr.rev 
		JOIN IPv4Address__s ON IPv4Address__s.id = IPv4Address__r.id 
	WHERE IPv4Address__r.rev_terminal = 0;
DROP TABLE IF EXISTS IPv4Allocation;
CREATE TABLE IPv4Allocation as 
	SELECT 
		IPv4Allocation__s.id AS id ,
		IPv4Allocation__r.name AS name,
		IPv4Allocation__r.type AS type ,
		IPv4Allocation__s.object_id AS object_id,
		IPv4Allocation__s.ip AS ip 
	FROM 
		IPv4Allocation__r 
		JOIN (SELECT id, max(rev) AS rev FROM IPv4Allocation__r GROUP BY id) AS IPv4Allocation__vr ON IPv4Allocation__r.id = IPv4Allocation__vr.id and IPv4Allocation__r.rev = IPv4Allocation__vr.rev 
		JOIN IPv4Allocation__s ON IPv4Allocation__s.id = IPv4Allocation__r.id 
	WHERE IPv4Allocation__r.rev_terminal = 0;
DROP TABLE IF EXISTS IPv4NAT;
CREATE TABLE IPv4NAT as 
	SELECT 
		IPv4NAT__s.id AS id ,
		IPv4NAT__r.proto AS proto,
		IPv4NAT__r.localip AS localip,
		IPv4NAT__r.localport AS localport,
		IPv4NAT__r.remoteip AS remoteip,
		IPv4NAT__r.remoteport AS remoteport,
		IPv4NAT__r.description AS description ,
		IPv4NAT__s.object_id AS object_id 
	FROM 
		IPv4NAT__r 
		JOIN (SELECT id, max(rev) AS rev FROM IPv4NAT__r GROUP BY id) AS IPv4NAT__vr ON IPv4NAT__r.id = IPv4NAT__vr.id and IPv4NAT__r.rev = IPv4NAT__vr.rev 
		JOIN IPv4NAT__s ON IPv4NAT__s.id = IPv4NAT__r.id 
	WHERE IPv4NAT__r.rev_terminal = 0;
DROP TABLE IF EXISTS IPv4Network;
CREATE TABLE IPv4Network as 
	SELECT 
		IPv4Network__s.id AS id ,
		IPv4Network__r.name AS name ,
		IPv4Network__s.ip AS ip,
		IPv4Network__s.mask AS mask 
	FROM 
		IPv4Network__r 
		JOIN (SELECT id, max(rev) AS rev FROM IPv4Network__r GROUP BY id) AS IPv4Network__vr ON IPv4Network__r.id = IPv4Network__vr.id and IPv4Network__r.rev = IPv4Network__vr.rev 
		JOIN IPv4Network__s ON IPv4Network__s.id = IPv4Network__r.id 
	WHERE IPv4Network__r.rev_terminal = 0;
DROP TABLE IF EXISTS Attribute;
CREATE TABLE Attribute as 
	SELECT 
		Attribute__s.id AS id ,
		Attribute__r.name AS name ,
		Attribute__s.type AS type 
	FROM 
		Attribute__r 
		JOIN (SELECT id, max(rev) AS rev FROM Attribute__r GROUP BY id) AS Attribute__vr ON Attribute__r.id = Attribute__vr.id and Attribute__r.rev = Attribute__vr.rev 
		JOIN Attribute__s ON Attribute__s.id = Attribute__r.id 
	WHERE Attribute__r.rev_terminal = 0;
DROP TABLE IF EXISTS AttributeMap;
CREATE TABLE AttributeMap as 
	SELECT 
		AttributeMap__s.id AS id  ,
		AttributeMap__s.objtype_id AS objtype_id,
		AttributeMap__s.attr_id AS attr_id,
		AttributeMap__s.chapter_id AS chapter_id 
	FROM 
		AttributeMap__r 
		JOIN (SELECT id, max(rev) AS rev FROM AttributeMap__r GROUP BY id) AS AttributeMap__vr ON AttributeMap__r.id = AttributeMap__vr.id and AttributeMap__r.rev = AttributeMap__vr.rev 
		JOIN AttributeMap__s ON AttributeMap__s.id = AttributeMap__r.id 
	WHERE AttributeMap__r.rev_terminal = 0;
DROP TABLE IF EXISTS AttributeValue;
CREATE TABLE AttributeValue as 
	SELECT 
		AttributeValue__s.id AS id ,
		AttributeValue__r.string_value AS string_value,
		AttributeValue__r.uint_value AS uint_value,
		AttributeValue__r.float_value AS float_value ,
		AttributeValue__s.object_id AS object_id,
		AttributeValue__s.attr_id AS attr_id 
	FROM 
		AttributeValue__r 
		JOIN (SELECT id, max(rev) AS rev FROM AttributeValue__r GROUP BY id) AS AttributeValue__vr ON AttributeValue__r.id = AttributeValue__vr.id and AttributeValue__r.rev = AttributeValue__vr.rev 
		JOIN AttributeValue__s ON AttributeValue__s.id = AttributeValue__r.id 
	WHERE AttributeValue__r.rev_terminal = 0;
DROP TABLE IF EXISTS Dictionary;
CREATE TABLE Dictionary as 
	SELECT 
		Dictionary__s.id AS id ,
		Dictionary__r.dict_value AS dict_value ,
		Dictionary__s.chapter_id AS chapter_id 
	FROM 
		Dictionary__r 
		JOIN (SELECT id, max(rev) AS rev FROM Dictionary__r GROUP BY id) AS Dictionary__vr ON Dictionary__r.id = Dictionary__vr.id and Dictionary__r.rev = Dictionary__vr.rev 
		JOIN Dictionary__s ON Dictionary__s.id = Dictionary__r.id 
	WHERE Dictionary__r.rev_terminal = 0;
DROP TABLE IF EXISTS Chapter;
CREATE TABLE Chapter as 
	SELECT 
		Chapter__s.id AS id ,
		Chapter__r.name AS name ,
		Chapter__s.sticky AS sticky 
	FROM 
		Chapter__r 
		JOIN (SELECT id, max(rev) AS rev FROM Chapter__r GROUP BY id) AS Chapter__vr ON Chapter__r.id = Chapter__vr.id and Chapter__r.rev = Chapter__vr.rev 
		JOIN Chapter__s ON Chapter__s.id = Chapter__r.id 
	WHERE Chapter__r.rev_terminal = 0;
DROP TABLE IF EXISTS IPv4LB;
CREATE TABLE IPv4LB as 
	SELECT 
		IPv4LB__s.id AS id ,
		IPv4LB__r.vsconfig AS vsconfig,
		IPv4LB__r.rsconfig AS rsconfig ,
		IPv4LB__s.object_id AS object_id,
		IPv4LB__s.rspool_id AS rspool_id,
		IPv4LB__s.vs_id AS vs_id 
	FROM 
		IPv4LB__r 
		JOIN (SELECT id, max(rev) AS rev FROM IPv4LB__r GROUP BY id) AS IPv4LB__vr ON IPv4LB__r.id = IPv4LB__vr.id and IPv4LB__r.rev = IPv4LB__vr.rev 
		JOIN IPv4LB__s ON IPv4LB__s.id = IPv4LB__r.id 
	WHERE IPv4LB__r.rev_terminal = 0;
DROP TABLE IF EXISTS IPv4RS;
CREATE TABLE IPv4RS as 
	SELECT 
		IPv4RS__s.id AS id ,
		IPv4RS__r.inservice AS inservice,
		IPv4RS__r.rsip AS rsip,
		IPv4RS__r.rsport AS rsport,
		IPv4RS__r.rsconfig AS rsconfig ,
		IPv4RS__s.rspool_id AS rspool_id 
	FROM 
		IPv4RS__r 
		JOIN (SELECT id, max(rev) AS rev FROM IPv4RS__r GROUP BY id) AS IPv4RS__vr ON IPv4RS__r.id = IPv4RS__vr.id and IPv4RS__r.rev = IPv4RS__vr.rev 
		JOIN IPv4RS__s ON IPv4RS__s.id = IPv4RS__r.id 
	WHERE IPv4RS__r.rev_terminal = 0;
DROP TABLE IF EXISTS IPv4RSPool;
CREATE TABLE IPv4RSPool as 
	SELECT 
		IPv4RSPool__s.id AS id ,
		IPv4RSPool__r.name AS name,
		IPv4RSPool__r.vsconfig AS vsconfig,
		IPv4RSPool__r.rsconfig AS rsconfig  
	FROM 
		IPv4RSPool__r 
		JOIN (SELECT id, max(rev) AS rev FROM IPv4RSPool__r GROUP BY id) AS IPv4RSPool__vr ON IPv4RSPool__r.id = IPv4RSPool__vr.id and IPv4RSPool__r.rev = IPv4RSPool__vr.rev 
		JOIN IPv4RSPool__s ON IPv4RSPool__s.id = IPv4RSPool__r.id 
	WHERE IPv4RSPool__r.rev_terminal = 0;
DROP TABLE IF EXISTS IPv4VS;
CREATE TABLE IPv4VS as 
	SELECT 
		IPv4VS__s.id AS id ,
		IPv4VS__r.vip AS vip,
		IPv4VS__r.vport AS vport,
		IPv4VS__r.proto AS proto,
		IPv4VS__r.name AS name,
		IPv4VS__r.vsconfig AS vsconfig,
		IPv4VS__r.rsconfig AS rsconfig  
	FROM 
		IPv4VS__r 
		JOIN (SELECT id, max(rev) AS rev FROM IPv4VS__r GROUP BY id) AS IPv4VS__vr ON IPv4VS__r.id = IPv4VS__vr.id and IPv4VS__r.rev = IPv4VS__vr.rev 
		JOIN IPv4VS__s ON IPv4VS__s.id = IPv4VS__r.id 
	WHERE IPv4VS__r.rev_terminal = 0;
DROP TABLE IF EXISTS TagStorage;
CREATE TABLE TagStorage as 
	SELECT 
		TagStorage__s.id AS id  ,
		TagStorage__s.entity_realm AS entity_realm,
		TagStorage__s.entity_id AS entity_id,
		TagStorage__s.tag_id AS tag_id 
	FROM 
		TagStorage__r 
		JOIN (SELECT id, max(rev) AS rev FROM TagStorage__r GROUP BY id) AS TagStorage__vr ON TagStorage__r.id = TagStorage__vr.id and TagStorage__r.rev = TagStorage__vr.rev 
		JOIN TagStorage__s ON TagStorage__s.id = TagStorage__r.id 
	WHERE TagStorage__r.rev_terminal = 0;
DROP TABLE IF EXISTS TagTree;
CREATE TABLE TagTree as 
	SELECT 
		TagTree__s.id AS id ,
		TagTree__r.parent_id AS parent_id,
		TagTree__r.valid_realm AS valid_realm,
		TagTree__r.tag AS tag  
	FROM 
		TagTree__r 
		JOIN (SELECT id, max(rev) AS rev FROM TagTree__r GROUP BY id) AS TagTree__vr ON TagTree__r.id = TagTree__vr.id and TagTree__r.rev = TagTree__vr.rev 
		JOIN TagTree__s ON TagTree__s.id = TagTree__r.id 
	WHERE TagTree__r.rev_terminal = 0;
DROP TABLE IF EXISTS FileLink;
CREATE TABLE FileLink as 
	SELECT 
		FileLink__s.id AS id  ,
		FileLink__s.file_id AS file_id,
		FileLink__s.entity_type AS entity_type,
		FileLink__s.entity_id AS entity_id 
	FROM 
		FileLink__r 
		JOIN (SELECT id, max(rev) AS rev FROM FileLink__r GROUP BY id) AS FileLink__vr ON FileLink__r.id = FileLink__vr.id and FileLink__r.rev = FileLink__vr.rev 
		JOIN FileLink__s ON FileLink__s.id = FileLink__r.id 
	WHERE FileLink__r.rev_terminal = 0;
DROP TABLE IF EXISTS File;
CREATE TABLE File as 
	SELECT 
		File__s.id AS id ,
		File__r.contents AS contents,
		File__r.size AS size,
		File__r.comment AS comment ,
		File__s.name AS name,
		File__s.type AS type,
		File__s.atime AS atime 
	FROM 
		File__r 
		JOIN (SELECT id, max(rev) AS rev FROM File__r GROUP BY id) AS File__vr ON File__r.id = File__vr.id and File__r.rev = File__vr.rev 
		JOIN File__s ON File__s.id = File__r.id 
	WHERE File__r.rev_terminal = 0;
END
//


delimiter ;

CALL init_legacy_tables();