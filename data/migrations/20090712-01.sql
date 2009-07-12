CREATE TABLE `tag` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `tag` VARCHAR( 255 ) NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY ( `id` )
)  ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `tag` (`tag`) VALUES 
('php'),
('zend'),
('symfony'),
('cakephp'),
('codeigniter'),
('pear'),
('pecl'),
('codeigniter'),
('yii'),
('flow3'),
('recess'),
('limonade'),
('wordpress'),
('drupal'),
('joomla');

ALTER TABLE `blog_post` ADD `tags` VARCHAR( 255 ) NOT NULL COMMENT 'tag that got this post accepted' AFTER `blog_id`;
ALTER TABLE `twitter_post` ADD `tags` VARCHAR( 255 ) NOT NULL COMMENT 'tag that got this post accepted' AFTER `twitter_id`;