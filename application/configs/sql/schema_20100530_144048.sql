CREATE TABLE blog (id BIGINT AUTO_INCREMENT, feed VARCHAR(255), url VARCHAR(255), live TINYINT(1) DEFAULT '1', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) ENGINE = INNODB;
CREATE TABLE blog_post (id BIGINT AUTO_INCREMENT, guid VARCHAR(255), title VARCHAR(255), author VARCHAR(255), content TEXT, url VARCHAR(255), posted_on DATETIME, blog_id BIGINT, tags VARCHAR(255), created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX blog_id_idx (blog_id), PRIMARY KEY(id)) ENGINE = INNODB;
CREATE TABLE tag (id BIGINT AUTO_INCREMENT, tag VARCHAR(255), created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) ENGINE = INNODB;
CREATE TABLE twitter (id BIGINT AUTO_INCREMENT, screen_name VARCHAR(40), live TINYINT(1), created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) ENGINE = INNODB;
CREATE TABLE twitter_post (id BIGINT AUTO_INCREMENT, guid VARCHAR(255), content TEXT, posted_on DATETIME, twitter_id INT, tags VARCHAR(255), created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX twitter_id_idx (twitter_id), PRIMARY KEY(id)) ENGINE = INNODB;
ALTER TABLE blog_post ADD CONSTRAINT blog_post_blog_id_blog_id FOREIGN KEY (blog_id) REFERENCES blog(id);
ALTER TABLE twitter_post ADD CONSTRAINT twitter_post_twitter_id_twitter_id FOREIGN KEY (twitter_id) REFERENCES twitter(id);
