CREATE TABLE `event` (
      `id` int(11) NOT NULL auto_increment,
      `when` datetime NOT NULL,
      `content` text,
      `url` varchar(255) default NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime NOT NULL,
      PRIMARY KEY  (`id`)
);
