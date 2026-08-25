USE kinyinya_jobs;

ALTER TABLE seekers ADD COLUMN IF NOT EXISTS resume_url VARCHAR(255) NULL AFTER location;
