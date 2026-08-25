USE kinyinya_jobs;

ALTER TABLE seekers ADD COLUMN resume_url VARCHAR(255) NULL AFTER location;
