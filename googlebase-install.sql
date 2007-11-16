-- 
-- Table structure for table `googlebase`
-- 

CREATE TABLE IF NOT EXISTS `googlebase` (
  `googlebase_id` int(11) NOT NULL auto_increment,
  `googlebase_url` varchar(200) NOT NULL default '',
  `products_id` int(11) NOT NULL default '',
  `googlebase_last_modified` datetime default NULL,
  `googlebase_expiration` datetime default NULL,
  `googlebase_item_xml` TEXT NOT NULL,
  PRIMARY KEY  (`googlebase_id`),
  UNIQUE KEY `googlebase_url` (`googlebase_url`),
  UNIQUE KEY `products_id` (`products_id`)
);

--- INSERT INTO `configuration_group` 
---						(`configuration_group_title` ,
---						 `configuration_group_description` ,
---						 `sort_order` ,
---						 `visible` ) 
--- VALUES ('Google Base', 'Google Base Configuration Options', NULL , '1'));

INSERT INTO `configuration`
						(`configuration_title`,
						 `configuration_key`,
						 `configuration_value`,
						 `configuration_description`,
						 `configuration_group_id`)
VALUES ('',
				'GOOGLEBASE_BULK_OPTIONS',
				'a:5:{s:5:"draft";b:1;s:10:"authorname";s:0:"";s:11:"authoremail";s:0:"";s:10:"maxuploads";i:200;s:3:"upc";b:0;s:7:"enabled";b:1}',
				'Google Base bulk uploader options', 6);
