# Sqlite3 to MariaDB format

Convert .sql of SQLITE to MariaDB format. Simply change the `sqlite.sqlite`.
```bash
sqlite3 sqlite.sqlite .dump > sqlite.sql
curl -o script.php https://raw.githubusercontent.com/huedaya/sqlite3-to-mariadb-format/main/script.php
php script.php sqlite.sql > output.mariadb.sql
```


## Bug
- Still error if column has a JSON format default value (https://github.com/huedaya/sqlite3-to-mariadb-format/blob/798fbefc46661586d041a4e0ac61fd609d50de0d/script.php#L41)
