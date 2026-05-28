@echo off
set PGPASSWORD=Abhi98250
echo Creating Database 'pos_db'...
"C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -c "CREATE DATABASE pos_db;"

echo.
echo Importing Tables from database.sql...
"C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -d pos_db -f database.sql

echo.
echo Database Setup Complete!
pause
