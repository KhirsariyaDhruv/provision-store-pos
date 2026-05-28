-- Add bill_number column if not exists
ALTER TABLE sales ADD COLUMN IF NOT EXISTS bill_number INTEGER;

-- Backfill existing records with their global ID (so they aren't null)
UPDATE sales SET bill_number = id WHERE bill_number IS NULL;

-- Verify it worked (will output results)
SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'sales' AND column_name = 'bill_number';
