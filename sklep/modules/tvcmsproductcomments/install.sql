CREATE TABLE IF NOT EXISTS `PREFIX_tvcmsproduct_comment` (
  `id_tvcmsproduct_comment` int(10) unsigned NOT NULL auto_increment,
  `id_product` int(10) unsigned NOT NULL,
  `id_customer` int(10) unsigned NOT NULL,
  `id_guest` int(10) unsigned NULL,
  `title` varchar(64) NULL,
  `content` text NOT NULL,
  `customer_name` varchar(64) NULL,
  `grade` float unsigned NOT NULL,
  `validate` tinyint(1) NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `date_add` datetime NOT NULL,
  PRIMARY KEY (`id_tvcmsproduct_comment`),
  KEY `id_product` (`id_product`),
  KEY `id_customer` (`id_customer`),
  KEY `id_guest` (`id_guest`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_tvcmsproduct_comment_criterion` (
  `id_tvcmsproduct_comment_criterion` int(10) unsigned NOT NULL auto_increment,
  `id_tvcmsproduct_comment_criterion_type` tinyint(1) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id_tvcmsproduct_comment_criterion`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_tvcmsproduct_comment_criterion_product` (
  `id_product` int(10) unsigned NOT NULL,
  `id_tvcmsproduct_comment_criterion` int(10) unsigned NOT NULL,
  PRIMARY KEY(`id_product`, `id_tvcmsproduct_comment_criterion`),
  KEY `id_tvcmsproduct_comment_criterion` (`id_tvcmsproduct_comment_criterion`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_tvcmsproduct_comment_criterion_lang` (
  `id_tvcmsproduct_comment_criterion` INT(11) UNSIGNED NOT NULL ,
  `id_lang` INT(11) UNSIGNED NOT NULL ,
  `name` VARCHAR(64) NOT NULL ,
  PRIMARY KEY ( `id_tvcmsproduct_comment_criterion` , `id_lang` )
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_tvcmsproduct_comment_criterion_category` (
  `id_tvcmsproduct_comment_criterion` int(10) unsigned NOT NULL,
  `id_category` int(10) unsigned NOT NULL,
  PRIMARY KEY(`id_tvcmsproduct_comment_criterion`, `id_category`),
  KEY `id_category` (`id_category`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_tvcmsproduct_comment_grade` (
  `id_tvcmsproduct_comment` int(10) unsigned NOT NULL,
  `id_tvcmsproduct_comment_criterion` int(10) unsigned NOT NULL,
  `grade` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_tvcmsproduct_comment`, `id_tvcmsproduct_comment_criterion`),
  KEY `id_tvcmsproduct_comment_criterion` (`id_tvcmsproduct_comment_criterion`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_tvcmsproduct_comment_usefulness` (
  `id_tvcmsproduct_comment` int(10) unsigned NOT NULL,
  `id_customer` int(10) unsigned NOT NULL,
  `usefulness` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id_tvcmsproduct_comment`, `id_customer`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_tvcmsproduct_comment_report` (
  `id_tvcmsproduct_comment` int(10) unsigned NOT NULL,
  `id_customer` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_tvcmsproduct_comment`, `id_customer`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `PREFIX_tvcmsproduct_comment_criterion` VALUES ('1', '1', '1');

INSERT IGNORE INTO `PREFIX_tvcmsproduct_comment_criterion_lang` (`id_tvcmsproduct_comment_criterion`, `id_lang`, `name`)
  (
    SELECT '1', l.`id_lang`, 'Quality'
    FROM `PREFIX_lang` l
  );

