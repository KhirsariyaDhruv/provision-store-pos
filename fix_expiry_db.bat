@echo off
set PGPASSWORD=Abhi98250
"C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -d pos_db -c "ALTER TABLE products ADD COLUMN IF NOT EXISTS expiry_date DATE DEFAULT NULL;"
"C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -d pos_db -c "\d products"
echo Done.
