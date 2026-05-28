@echo off
"C:\Program Files\PostgreSQL\18\bin\psql.exe" -Upostgres -d pos_db -c "ALTER TABLE products ADD COLUMN expiry_date DATE DEFAULT NULL;"
pause
