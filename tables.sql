CREATE TABLE un_posts (
  un_id VARCHAR(255) NOT NULL,
  un_child VARCHAR(255) NOT NULL
) /*$wgDBTableOptions*/;

CREATE TABLE un_threads (
  unt_id VARCHAR(255) NOT NULL,
  unt_post VARCHAR(255) NOT NULL
) /*$wgDBTableOptions*/;
