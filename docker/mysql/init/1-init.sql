SET GLOBAL max_allowed_packet=4194304;

CREATE USER IF NOT EXISTS 'everlywell'@'%' IDENTIFIED BY 'qwerty123';

GRANT ALL PRIVILEGES ON *.* TO 'everlywell'@'%';

FLUSH PRIVILEGES;
