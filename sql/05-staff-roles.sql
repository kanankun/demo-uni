-- Staff is not one flat role. "Admin" meant two different jobs squeezed
-- into one account: the registrar-office work (post notices, approve
-- registrations, post coursework) and the one genuinely dangerous action
-- (reset anyone's password). A lecturer needs the first set, never the
-- second.
--
-- is_lecturer rides alongside the existing is_admin flag rather than
-- replacing it: every `ss_is_admin($conn)` check already scattered across
-- the app (announcements, assignments, class registrations, the ADMIN nav
-- tab) keeps working unchanged for a lecturer, because a lecturer IS an
-- admin account for all of that. is_lecturer only narrows one thing -
-- admin.php's password reset - to accounts where it's false.
ALTER TABLE users ADD COLUMN is_lecturer TINYINT(1) DEFAULT 0 AFTER is_admin;

-- Each class now has one lecturer responsible for it - who shows up on
-- the Timetable, and whose own Classes page this class appears on.
ALTER TABLE class_sessions ADD COLUMN lecturer_id INT DEFAULT NULL AFTER intake;
ALTER TABLE class_sessions ADD CONSTRAINT fk_class_lecturer
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE SET NULL;

-- Results and fees used to be pure decoration - generated on the fly from
-- the viewer's own id, nothing stored, nothing a staff account could act
-- on. These two tables are where a result a lecturer enters, or a fee a
-- staff member marks paid, actually lives. The generated transcript/
-- statement stays as the baseline a student sees by default; these are
-- the real records layered on top of it.
CREATE TABLE results (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    course_code  VARCHAR(20)  NOT NULL,
    course_title VARCHAR(120) NOT NULL,
    credits      TINYINT      NOT NULL DEFAULT 3,
    grade        VARCHAR(5)   NOT NULL,
    entered_by   INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (entered_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE fees (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    label      VARCHAR(100) NOT NULL,
    status     VARCHAR(20) DEFAULT 'paid',   -- this table only ever records a PAID override
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY one_override_per_item (user_id, label)
);

-- Three lecturers, each already teaching one of the four seeded classes
-- and owning one of the three seeded assignments - so "different lecturer,
-- different class, different assignment" is true from the first page
-- load, not just once someone starts clicking around.
INSERT INTO users (username, email, password, avatar, is_admin, is_lecturer, bio, full_name, phone, ic_number, address, created_at) VALUES
('dr.hassan',  'hassan@universitya.edu.my',  'Lecture!99',  'u1.svg', 1, 1, 'Faculty of Computing - Networks & Security', 'Dr. Hassan Rahim',       '+6012-700-1122', '780214-10-5523', 'Block B, Faculty of Computing, University A', DATE_SUB(NOW(), INTERVAL 400 DAY)),
('dr.chong',   'chong@universitya.edu.my',   'Lecture!98',  'u1.svg', 1, 1, 'Faculty of Computing - Databases',           'Dr. Chong Mei Yee',      '+6012-700-1133', '820530-14-5567', 'Block B, Faculty of Computing, University A', DATE_SUB(NOW(), INTERVAL 380 DAY)),
('mr.farid',   'farid@universitya.edu.my',   'Lecture!97',  'u1.svg', 1, 1, 'Faculty of Languages - Academic Writing',    'Mr. Farid Iskandar',     '+6012-700-1144', '880912-10-5589', 'Block D, Faculty of Languages, University A', DATE_SUB(NOW(), INTERVAL 360 DAY));

UPDATE class_sessions SET lecturer_id = (SELECT id FROM users WHERE username = 'dr.hassan') WHERE title = 'Introduction to Networks';
UPDATE class_sessions SET lecturer_id = (SELECT id FROM users WHERE username = 'dr.chong')  WHERE title = 'Database Systems Lab';
UPDATE class_sessions SET lecturer_id = (SELECT id FROM users WHERE username = 'mr.farid')  WHERE title = 'Academic Writing Workshop';
UPDATE class_sessions SET lecturer_id = (SELECT id FROM users WHERE username = 'dr.hassan') WHERE title = 'Industrial Training Briefing';

UPDATE assignments SET created_by = (SELECT id FROM users WHERE username = 'dr.chong')  WHERE title = 'Database Systems Lab - ER diagram';
UPDATE assignments SET created_by = (SELECT id FROM users WHERE username = 'mr.farid')  WHERE title = 'Academic Writing Workshop - draft outline';
UPDATE assignments SET created_by = (SELECT id FROM users WHERE username = 'dr.hassan') WHERE title = 'Introduction to Networks - subnetting exercise';

INSERT INTO assignments (title, instructions, due_at, created_by, created_at) VALUES
('Introduction to Networks - packet capture writeup',
 'Capture ten minutes of your own network traffic with Wireshark and submit a one-page writeup identifying three protocols you see and what each one was doing.',
 DATE_ADD(CURDATE(), INTERVAL 18 DAY), (SELECT id FROM users WHERE username = 'dr.hassan'), DATE_SUB(NOW(), INTERVAL 1 DAY));
