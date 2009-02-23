<?php
class DatabaseMeta {
public static $database_meta = array (
	'RackSpace' => array(
		'fields' => array(
			'state' => array (
				'revisioned' => true,
				'type' => "enum('A','U','T','W','F')",
				'null' => false
			),
			'object_id' => array (
				'revisioned' => true,
				'type' => "int(10) unsigned",
				'null' => true
			),
			'rack_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'unit_no' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'atom' => array (
				'revisioned' => false,
				'type' => "enum('front','interior','rear')",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
				array('rack_id', 'unit_no', 'atom')
			)
		),
		'indices' => array(
			array('object_id')
		)
	), //end of table RackSpace
	'Rack' => array(
		'fields' => array(
			'name' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => true
			),
			'row_id' => array (
				'revisioned' => true,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'height' => array (
				'revisioned' => true,
				'type' => "tinyint(3) unsigned",
				'null' => false
			),
			'comment' => array (
				'revisioned' => true,
				'type' => "text",
				'null' => true
			),
			'thumb_data' => array (
				'revisioned' => false,
				'type' => "blob",
				'null' => true
			)
		),
		'constraints' => array(
			'unique' => array(
				array('row_id', 'name')
			)
		),
		'indices' => array(
		)
	), //end of table Rack
	'RackRow' => array(
		'fields' => array(
			'name' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
			)
		),
		'indices' => array(
		)
	), //end of table RackRow
	'RackObject' => array(
		'fields' => array(
			'name' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => true
			),
			'label' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => true
			),
			'barcode' => array (
				'revisioned' => true,
				'type' => "char(16)",
				'null' => true
			),
			'objtype_id' => array (
				'revisioned' => true,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'asset_no' => array (
				'revisioned' => true,
				'type' => "char(64)",
				'null' => true
			),
			'has_problems' => array (
				'revisioned' => true,
				'type' => "enum('yes','no')",
				'null' => false
			),
			'comment' => array (
				'revisioned' => true,
				'type' => "text",
				'null' => true
			)
		),
		'constraints' => array(
			'unique' => array(
				array('asset_no'),
				array('name'),
				array('barcode')
			)
		),
		'indices' => array(
		)
	), //end of table RackObject
	'Port' => array(
		'fields' => array(
			'name' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => false
			),
			'type' => array (
				'revisioned' => true,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'l2address' => array (
				'revisioned' => true,
				'type' => "char(64)",
				'null' => true
			),
			'reservation_comment' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => true
			),
			'label' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => true
			),
			'object_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
				array('object_id', 'name'),
				array('l2address')
			)
		),
		'indices' => array(
			array('l2address')
		)
	), //end of table Port
	'Link' => array(
		'fields' => array(
			'label' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => false
			),
			'labela' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => false
			),
			'labelb' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => false
			),
			'porta' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'portb' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
				array('porta'),
				array('portb')
			)
		),
		'indices' => array(
			array('porta', 'portb')
		)
	), //end of table Link
	'IPv4Address' => array(
		'fields' => array(
			'name' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => false
			),
			'reserved' => array (
				'revisioned' => true,
				'type' => "enum('yes','no')",
				'null' => true
			),
			'ip' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
				array('ip')
			)
		),
		'indices' => array(
		)
	), //end of table IPv4Address
	'IPv4Allocation' => array(
		'fields' => array(
			'name' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => false
			),
			'type' => array (
				'revisioned' => true,
				'type' => "enum('regular','shared','virtual','router')",
				'null' => true
			),
			'object_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'ip' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
				array('object_id', 'ip')
			)
		),
		'indices' => array(
		)
	), //end of table IPv4Allocation
	'IPv4NAT' => array(
		'fields' => array(
			'proto' => array (
				'revisioned' => true,
				'type' => "enum('TCP','UDP')",
				'null' => false
			),
			'localip' => array (
				'revisioned' => true,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'localport' => array (
				'revisioned' => true,
				'type' => "smallint(5) unsigned",
				'null' => false
			),
			'remoteip' => array (
				'revisioned' => true,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'remoteport' => array (
				'revisioned' => true,
				'type' => "smallint(5) unsigned",
				'null' => false
			),
			'description' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => true
			),
			'object_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
				array('object_id', 'proto', 'localip', 'localport', 'remoteip', 'remoteport')
			)
		),
		'indices' => array(
			array('localip'),
			array('remoteip'),
			array('object_id')
		)
	), //end of table IPv4NAT
	'IPv4Network' => array(
		'fields' => array(
			'name' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => true
			),
			'ip' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'mask' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
				array('ip', 'mask')
			)
		),
		'indices' => array(
		)
	), //end of table IPv4Network
	'Attribute' => array(
		'fields' => array(
			'name' => array (
				'revisioned' => true,
				'type' => "char(64)",
				'null' => true
			),
			'type' => array (
				'revisioned' => false,
				'type' => "enum('string','uint','float','dict')",
				'null' => true
			)
		),
		'constraints' => array(
			'unique' => array(
				array('name')
			)
		),
		'indices' => array(
		)
	), //end of table Attribute
	'AttributeMap' => array(
		'fields' => array(
			'objtype_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'attr_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'chapter_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
				array('objtype_id', 'attr_id')
			)
		),
		'indices' => array(
		)
	), //end of table AttributeMap
	'AttributeValue' => array(
		'fields' => array(
			'string_value' => array (
				'revisioned' => true,
				'type' => "char(128)",
				'null' => true
			),
			'uint_value' => array (
				'revisioned' => true,
				'type' => "int(10) unsigned",
				'null' => true
			),
			'float_value' => array (
				'revisioned' => true,
				'type' => "float",
				'null' => true
			),
			'object_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => true
			),
			'attr_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => true
			)
		),
		'constraints' => array(
			'unique' => array(
				array('object_id', 'attr_id')
			)
		),
		'indices' => array(
		)
	), //end of table AttributeValue
	'Dictionary' => array(
		'fields' => array(
			'dict_value' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => true
			),
			'chapter_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
				array('chapter_id', 'dict_value')
			)
		),
		'indices' => array(
		)
	), //end of table Dictionary
	'Chapter' => array(
		'fields' => array(
			'name' => array (
				'revisioned' => true,
				'type' => "char(128)",
				'null' => false
			),
			'sticky' => array (
				'revisioned' => false,
				'type' => "enum('yes','no')",
				'null' => true
			)
		),
		'constraints' => array(
			'unique' => array(
				array('name')
			)
		),
		'indices' => array(
		)
	), //end of table Chapter
	'IPv4LB' => array(
		'fields' => array(
			'vsconfig' => array (
				'revisioned' => true,
				'type' => "text",
				'null' => true
			),
			'rsconfig' => array (
				'revisioned' => true,
				'type' => "text",
				'null' => true
			),
			'object_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => true
			),
			'rspool_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => true
			),
			'vs_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => true
			)
		),
		'constraints' => array(
			'unique' => array(
				array('object_id', 'vs_id')
			)
		),
		'indices' => array(
		)
	), //end of table IPv4LB
	'IPv4RS' => array(
		'fields' => array(
			'inservice' => array (
				'revisioned' => true,
				'type' => "enum('yes','no')",
				'null' => false
			),
			'rsip' => array (
				'revisioned' => true,
				'type' => "int(10) unsigned",
				'null' => true
			),
			'rsport' => array (
				'revisioned' => true,
				'type' => "smallint(5) unsigned",
				'null' => true
			),
			'rsconfig' => array (
				'revisioned' => true,
				'type' => "text",
				'null' => true
			),
			'rspool_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => true
			)
		),
		'constraints' => array(
			'unique' => array(
				array('rspool_id', 'rsip', 'rsport')
			)
		),
		'indices' => array(
		)
	), //end of table IPv4RS
	'IPv4RSPool' => array(
		'fields' => array(
			'name' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => true
			),
			'vsconfig' => array (
				'revisioned' => true,
				'type' => "text",
				'null' => true
			),
			'rsconfig' => array (
				'revisioned' => true,
				'type' => "text",
				'null' => true
			)
		),
		'constraints' => array(
			'unique' => array(
			)
		),
		'indices' => array(
		)
	), //end of table IPv4RSPool
	'IPv4VS' => array(
		'fields' => array(
			'vip' => array (
				'revisioned' => true,
				'type' => "int(10) unsigned",
				'null' => true
			),
			'vport' => array (
				'revisioned' => true,
				'type' => "smallint(5) unsigned",
				'null' => true
			),
			'proto' => array (
				'revisioned' => true,
				'type' => "enum('TCP','UDP')",
				'null' => false
			),
			'name' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => true
			),
			'vsconfig' => array (
				'revisioned' => true,
				'type' => "text",
				'null' => true
			),
			'rsconfig' => array (
				'revisioned' => true,
				'type' => "text",
				'null' => true
			)
		),
		'constraints' => array(
			'unique' => array(
			)
		),
		'indices' => array(
		)
	), //end of table IPv4VS
	'TagStorage' => array(
		'fields' => array(
			'entity_realm' => array (
				'revisioned' => false,
				'type' => "enum('file','ipv4net','ipv4vs','ipv4rspool','object','rack','user')",
				'null' => false
			),
			'entity_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'tag_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
				array('entity_realm', 'entity_id', 'tag_id')
			)
		),
		'indices' => array(
		)
	), //end of table TagStorage
	'TagTree' => array(
		'fields' => array(
			'parent_id' => array (
				'revisioned' => true,
				'type' => "int(10) unsigned",
				'null' => true
			),
			'valid_realm' => array (
				'revisioned' => true,
				'type' => "set('file','ipv4net','ipv4vs','ipv4rspool','object','rack','user')",
				'null' => false
			),
			'tag' => array (
				'revisioned' => true,
				'type' => "char(255)",
				'null' => true
			)
		),
		'constraints' => array(
			'unique' => array(
				array('tag')
			)
		),
		'indices' => array(
		)
	), //end of table TagTree
	'FileLink' => array(
		'fields' => array(
			'file_id' => array (
				'revisioned' => false,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'entity_type' => array (
				'revisioned' => false,
				'type' => "enum('ipv4net','ipv4rspool','ipv4vs','object','rack','user')",
				'null' => false
			),
			'entity_id' => array (
				'revisioned' => false,
				'type' => "int(10)",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
				array('file_id', 'entity_type', 'entity_id')
			)
		),
		'indices' => array(
			array('file_id')
		)
	), //end of table FileLink
	'File' => array(
		'fields' => array(
			'contents' => array (
				'revisioned' => true,
				'type' => "longblob",
				'null' => false
			),
			'size' => array (
				'revisioned' => true,
				'type' => "int(10) unsigned",
				'null' => false
			),
			'comment' => array (
				'revisioned' => true,
				'type' => "text",
				'null' => true
			),
			'name' => array (
				'revisioned' => false,
				'type' => "char(255)",
				'null' => false
			),
			'type' => array (
				'revisioned' => false,
				'type' => "char(255)",
				'null' => false
			),
			'atime' => array (
				'revisioned' => false,
				'type' => "datetime",
				'null' => false
			)
		),
		'constraints' => array(
			'unique' => array(
			)
		),
		'indices' => array(
		)
	) //end of table File
);

}
