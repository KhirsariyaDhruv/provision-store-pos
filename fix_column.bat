@echo off
set PGPASSWORD=Abhi98250
echo Attempting to add bill_number column...
"C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -d pos_db -c "ALTER TABLE sales ADD COLUMN IF NOT EXISTS bill_number INTEGER;"
"C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -d pos_db -c "UPDATE sales SET bill_number = id WHERE bill_number IS NULL;"
echo Done.
