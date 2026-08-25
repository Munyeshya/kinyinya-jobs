USE kinyinya_jobs;

-- Run once when upgrading a database created before job capacity was added.
ALTER TABLE jobs
  ADD COLUMN positions_total INT UNSIGNED NOT NULL DEFAULT 1 AFTER salary_max;
