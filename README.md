# Sqlite3 to MariaDB format

Convert .sql of SQLITE to MariaDB format
```bash
sqlite3 sqlite-db.sqlite .dump > sqlite.sql
php sqlite-to-mariadb.php sqlite.sql > output.mariadb.sql
```
