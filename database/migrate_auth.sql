USE kinyinya_jobs;

CREATE TABLE IF NOT EXISTS users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email          VARCHAR(190) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('seeker','employer','admin') NOT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE employers ADD COLUMN user_id INT UNSIGNED NULL UNIQUE AFTER id;
ALTER TABLE seekers ADD COLUMN user_id INT UNSIGNED NULL UNIQUE AFTER id;

INSERT INTO users (id, email, password_hash, role) VALUES
(1, 'techhub@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'employer'),
(2, 'freshproduce@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'employer'),
(3, 'umurava@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'employer'),
(4, 'aline@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'seeker'),
(5, 'eric@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'seeker'),
(6, 'claudine@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'seeker'),
(7, 'jeanbosco@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'seeker'),
(8, 'diane@example.com', '$2y$10$uQSOBhH1JxHwRBkIJIjWcehzA34yUPhZq2iz2pPCWpj7JC4qWjuni', 'seeker'),
(9, 'admin@kinyinya.rw', '$2y$10$p781j4MD9af4c4PWhlub4OOmiv89bXM.tXwFCxS18bcizET.q4I4.', 'admin')
ON DUPLICATE KEY UPDATE email = VALUES(email);

UPDATE employers SET user_id = id WHERE user_id IS NULL AND id BETWEEN 1 AND 3;
UPDATE seekers SET user_id = id + 3 WHERE user_id IS NULL AND id BETWEEN 1 AND 5;

ALTER TABLE employers ADD CONSTRAINT fk_employers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE seekers ADD CONSTRAINT fk_seekers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
