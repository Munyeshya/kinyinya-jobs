-- Kinyinya Jobs — demo seed data
-- Run after schema.sql:
--   mysql -u root -p kinyinya_jobs < database/seed.sql
-- Safe to re-run: it clears the tables first (child tables before parents,
-- to respect foreign keys) and re-inserts the same demo rows.

USE kinyinya_jobs;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE notifications;
TRUNCATE TABLE messages;
TRUNCATE TABLE applications;
TRUNCATE TABLE jobs;
TRUNCATE TABLE seekers;
TRUNCATE TABLE employers;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO users (id, email, password_hash, role) VALUES
(1, 'techhub@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'employer'),
(2, 'freshproduce@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'employer'),
(3, 'umurava@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'employer'),
(4, 'aline@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'seeker'),
(5, 'eric@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'seeker'),
(6, 'claudine@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'seeker'),
(7, 'jeanbosco@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'seeker'),
(8, 'diane@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'seeker'),
(9, 'admin@kinyinya.rw', '$2y$10$p781j4MD9af4c4PWhlub4OOmiv89bXM.tXwFCxS18bcizET.q4I4.', 'admin');

INSERT INTO employers (id, user_id, name, industry, location, about) VALUES
(1, 1, 'Kinyinya Tech Hub',       'Information Technology', 'Kinyinya, Gasabo',    'A co-working and software services company supporting local startups.'),
(2, 2, 'Gasabo Fresh Produce Ltd','Agribusiness / Retail',  'Kinyinya Market Road','Wholesale and retail distributor of fresh produce across Gasabo District.'),
(3, 3, 'Umurava Construction',    'Construction',           'Kinyinya, Gasabo',    'Residential and small commercial construction contractor.');

INSERT INTO seekers (id, user_id, name, skills, education, location) VALUES
(1, 4, 'Aline Uwase',        'Bookkeeping, Excel, Customer Service', 'A2 Accounting',                 'Kinyinya'),
(2, 5, 'Eric Niyonzima',     'Web Development, PHP, React',          'BSc Information Technology',    'Kinyinya'),
(3, 6, 'Claudine Mukamana',  'Sales, Inventory, Communication',      'A2 Sales & Marketing',           'Kabuga'),
(4, 7, 'Jean Bosco Habimana','Masonry, Site Supervision',            'Vocational Diploma',             'Kinyinya'),
(5, 8, 'Diane Ingabire',     'Graphic Design, Social Media',         'BSc Information Technology',     'Kinyinya');

INSERT INTO jobs (id, employer_id, title, type, category, description, requirements, salary_min, salary_max, positions_total, deadline, posted, active, status, views) VALUES
(101, 1, 'Junior PHP Developer', 'Full-time', 'IT',
 'Maintain and extend internal web applications for local SME clients.',
 'BSc in IT/CS or equivalent experience, PHP, MySQL basics.',
 250000, 350000, 2, DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY), 1, 'approved', 84),
(102, 1, 'IT Support Assistant', 'Part-time', 'IT',
 'First-line support for co-working members: network, printers, basic troubleshooting.',
 'A2/A1 in IT, good communication skills.',
 120000, 160000, 1, DATE_ADD(CURDATE(), INTERVAL 21 DAY), DATE_SUB(CURDATE(), INTERVAL 18 DAY), 1, 'approved', 51),
(201, 2, 'Sales Attendant', 'Full-time', 'Retail',
 'Serve customers at the Kinyinya market stall, manage stock rotation.',
 'A2 in any field, prior retail experience a plus.',
 100000, 140000, 3, DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 16 DAY), 1, 'approved', 63),
(202, 2, 'Inventory Clerk', 'Full-time', 'Retail',
 'Track incoming and outgoing stock across two storage sites.',
 'Basic bookkeeping, comfortable with mobile apps.',
 130000, 170000, 2, DATE_ADD(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), 1, 'approved', 39),
(301, 3, 'Mason (Skilled)', 'Contract', 'Construction',
 'Skilled mason needed for a residential project in Kinyinya, 3-month contract.',
 '3+ years masonry experience, own basic tools.',
 180000, 220000, 5, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 35 DAY), 0, 'approved', 47),
(302, 3, 'Site Supervisor', 'Full-time', 'Construction',
 'Oversee daily site activity, labor scheduling and safety compliance.',
 'Vocational diploma in construction or 5+ years site experience.',
 220000, 280000, 1, DATE_ADD(CURDATE(), INTERVAL 18 DAY), DATE_SUB(CURDATE(), INTERVAL 12 DAY), 1, 'approved', 28),
(303, 3, 'Electrician (Apprentice)', 'Contract', 'Construction',
 'Assist licensed electricians on residential wiring jobs across Kinyinya.',
 'Basic electrical training, willingness to learn on the job.',
 90000, 120000, 4, DATE_ADD(CURDATE(), INTERVAL 40 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1, 'pending', 0);

INSERT INTO applications (id, job_id, seeker_id, date, status, cover_letter) VALUES
(1, 101, 2, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'shortlisted',  'I have built two small business websites and I am eager to grow with a local team.'),
(2, 101, 5, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'submitted',    'While my background is design-focused, I have completed a PHP fundamentals course and want to move into development.'),
(3, 102, 5, DATE_SUB(CURDATE(), INTERVAL 9 DAY), 'under_review', 'I manage the social pages for two shops in Kinyinya and troubleshoot their devices already.'),
(4, 201, 3, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'hired',        'I sold produce at Nyabugogo market for two years before relocating to Kinyinya.'),
(5, 202, 1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'rejected',     'I keep the books for a small tailoring shop and want to move into a larger operation.'),
(6, 301, 4, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'shortlisted',  'I have worked on three residential sites in Gasabo District over the past four years.');

INSERT INTO messages (id, application_id, sender, body, sent_at, is_read) VALUES
(1, 1, 'employer', 'Thanks for applying — are you available for a short call this week?', '2026-07-06 09:12:00', 1),
(2, 1, 'seeker',   'Yes, I am free Thursday afternoon.', '2026-07-06 10:40:00', 1),
(3, 6, 'employer', 'Please bring your ID and past site references on Monday.', '2026-07-08 14:05:00', 0);
