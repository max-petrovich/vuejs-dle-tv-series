<?php

require_once 'engine/api/api.class.php';


// CREATE TABLES
$dle_api->db->query("CREATE TABLE IF NOT EXISTS `".PREFIX."_tv_series` (
  `id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `release_date` datetime DEFAULT NULL,
  `number` int(6) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

");

$dle_api->db->query("CREATE TABLE IF NOT EXISTS `".PREFIX."_tv_series_file_sharing` (
  `id` varchar(100) NOT NULL,
  `icon` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$dle_api->db->query("CREATE TABLE IF NOT EXISTS `".PREFIX."_tv_series_links` (
  `id` int(11) NOT NULL,
  `series_id` int(11) NOT NULL,
  `lang` varchar(10) NOT NULL,
  `file_sharing_id` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dle_tv_series_links_series_id` (`series_id`,`lang`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;

");

// Install module to admin
$dle_api->install_admin_module('tv_series', 'TV Series - сериалы в полной новости', 'Модуль позволяет выводить в полной новости сериалы', 'tv_series.png');

echo 'TV Series successfully installed! All ok!';