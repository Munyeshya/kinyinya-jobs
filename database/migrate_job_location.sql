USE kinyinya_jobs;

ALTER TABLE jobs ADD COLUMN location VARCHAR(150) NOT NULL DEFAULT 'Kinyinya' AFTER category;
