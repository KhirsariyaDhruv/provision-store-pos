@echo off
set PGPASSWORD=Abhi98250
echo Attempting to add cost_price column...
"C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -d pos_db -c "ALTER TABLE products ADD COLUMN IF NOT EXISTS cost_price DECIMAL(10,2) DEFAULT 0;"
echo Done.
