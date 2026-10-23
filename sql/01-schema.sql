-- SeaShaw lab schema.
--
-- The members, photos, comments, likes, tags and follows below are the real
-- seed data from the main SeaShaw application, exported from its database so
-- the labs feel like the same site. Only the four documented accounts have
-- usable passwords.

DROP TABLE IF EXISTS bookmarks;
DROP TABLE IF EXISTS follows;
DROP TABLE IF EXISTS upload_tags;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS class_sessions;
DROP TABLE IF EXISTS uploads;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(100) NOT NULL,
    password   VARCHAR(255) NOT NULL,
    avatar     VARCHAR(255) DEFAULT NULL,
    is_admin   TINYINT(1)   DEFAULT 0,
    -- profile fields: the private half is what an access-control bug leaks
    bio        VARCHAR(255) DEFAULT '',
    full_name  VARCHAR(100) DEFAULT '',
    phone      VARCHAR(50)  DEFAULT '',
    ic_number  VARCHAR(50)  DEFAULT '',
    address    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE uploads (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    title      VARCHAR(150),
    filename   VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    upload_id  INT,
    user_id    INT,
    comment    VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
);

CREATE TABLE likes (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    upload_id INT,
    user_id   INT,
    UNIQUE KEY one_like (upload_id, user_id),
    FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
);

CREATE TABLE upload_tags (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    upload_id INT,
    tag       VARCHAR(40),
    KEY by_tag (tag),
    FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE CASCADE
);

CREATE TABLE follows (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT,
    followee_id INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_follow (follower_id, followee_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (followee_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE bookmarks (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    upload_id  INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_save (user_id, upload_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE CASCADE
);

-- Course registrations. One row per student per course - course_title is a
-- snapshot of the class_sessions.title the student picked, not free text:
-- apply.php fills it from the real course catalogue below, it never takes
-- a value typed by hand.
CREATE TABLE applications (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT,
    course_title   VARCHAR(100),
    file_path      VARCHAR(255),
    status         VARCHAR(20) DEFAULT 'pending',   -- pending | approved | rejected
    admin_response VARCHAR(500) DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY one_registration_per_course (user_id, course_title)
);

CREATE TABLE class_sessions (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    title     VARCHAR(120),
    intake    TINYINT,
    starts_at DATETIME,
    location  VARCHAR(120),
    seats     INT DEFAULT 12,
    taken     INT DEFAULT 0,
    notes     VARCHAR(255) DEFAULT ''
);

-- ---------------------------------------------------------------
-- [VULN] Passwords are stored exactly as the member typed them.
--
-- This is the single decision that turns "someone read our database" into
-- "someone is logging in as our administrator". Hashing would not have
-- stopped the SQL injection; it would have stopped it from mattering.
-- ---------------------------------------------------------------
INSERT INTO users (id, username, email, password, avatar, is_admin, bio, full_name, phone, ic_number, address, created_at) VALUES
(1, 'admin', 'admin@universitya.edu.my', 'Sunset!Reef2019', 'u1.svg', 1, 'IT Services, Registrar\'s Office', 'Nurul Aina binti Rashid', '+6012-345-6789', '800101-14-5566', 'Block C, University A Staff Quarters, Nilai, Negeri Sembilan', '2026-07-08 02:24:15'),
(2, 'ahmad.faiz', 'dan@universitya.edu.my', 'student123', 'u2.svg', 0, 'Diploma in Information Technology, Year 1', 'Ahmad Faiz bin Hassan', '+6019-999-8888', '950505-10-1234', '12 Jalan Seri Utama, Nilai, Negeri Sembilan', '2026-07-18 02:24:15'),
(3, 'sarah.lim', 'sarah@universitya.edu.my', 'password123', 'u3.svg', 0, 'Bachelor of Business Administration, Year 3', 'Sarah Lim Wei Ting', '+1-555-0199', '981212-14-5555', '15 George St, Sydney', '2026-07-20 02:24:15'),
(4, 'jack.tan', 'jack@universitya.edu.my', 'BlackPearl!77', 'u4.svg', 0, 'Bachelor of Accounting, Year 2', 'Jonathan Tan', '+44-20-7946-0958', '750101-10-9999', '27 Baker Street, London', '2026-07-23 02:24:15'),
(5, 'iqbal.rahman', 'iqbal@universitya.edu.my', 'iqbal1234', 'u5.svg', 0, 'Diploma in Business Studies, Year 1', 'Iqbal Rahman', '+6013-222-4455', '921103-14-2211', '7 Jalan Kelapa, Penang', '2026-08-07 02:24:15'),
(6, 'zoe.tan', 'zoe@universitya.edu.my', 'password123', 'u6.svg', 0, 'Bachelor of Psychology, Year 2', 'Zoe Tan', '+6018-664-2093', '970512-07-5521', '26 Jalan Bayu, Kuantan', '2026-08-09 02:24:15'),
(7, 'mira.kamarudin', 'mira@universitya.edu.my', 'qwerty123', 'u7.svg', 0, 'Diploma in Multimedia Design, Year 2', 'Mira Kamarudin', '+6012-887-3321', '930214-14-5008', '18 Jalan Pantai, Port Dickson', '2026-08-17 02:24:15'),
(8, 'ehsan.zulkifli', 'ehsan@universitya.edu.my', 'letmein', 'u8.svg', 0, 'Bachelor of Civil Engineering, Year 3', 'Ehsan Zulkifli', '+6017-445-9920', '890730-08-6123', '5 Lorong Batu Feringghi, Penang', '2026-08-21 02:24:15'),
(9, 'nadia.ariff', 'nadia@universitya.edu.my', 'Nadia@2020', 'u9.svg', 0, 'Bachelor of Mass Communication, Year 2', 'Nadia Ariff', '+6014-220-7781', '960118-10-4472', '92 Persiaran Damai, Kota Kinabalu', '2026-08-26 02:24:15'),
(10, 'kelvin.tan', 'kelvin@universitya.edu.my', 'kelvin99', 'u10.svg', 0, 'Diploma in Mechanical Engineering, Year 1', 'Kelvin Tan', '+6016-338-1204', '870922-14-3390', '31 Jalan Sri Hartamas, Kuala Lumpur', '2026-08-29 02:24:15'),
(11, 'amirul.hakim', 'amir@universitya.edu.my', 'password123', 'u11.svg', 0, 'Bachelor of Quantity Surveying, Year 3', 'Amirul Hakim', '+6019-771-6650', '910405-11-2287', '7 Taman Melawati, Selangor', '2026-09-03 02:24:15'),
(12, 'yee.ling', 'yee@universitya.edu.my', 'iloveyou', 'u12.svg', 0, 'Diploma in Accounting, Year 2', 'Yee Ling Chong', '+6012-909-4413', '940627-07-8815', '44 Jalan Gasing, Petaling Jaya', '2026-09-06 02:24:15'),
(13, 'finn.harrison', 'finn@universitya.edu.my', 'harbour2018', 'u13.svg', 0, 'Bachelor of Information Systems, Year 2', 'Finn Harrison', '+61-2-5550-8842', '880311-14-7726', '12 Bondi Road, Sydney', '2026-09-09 02:24:15'),
(14, 'lea.fontaine', 'lea@universitya.edu.my', 'Soleil!92', 'u14.svg', 0, 'Bachelor of International Business, Year 3', 'Lea Fontaine', '+33-1-5550-2214', '920809-09-3364', '8 Rue des Marins, Nice', '2026-09-12 02:24:15'),
(15, 'nova.frost', 'nova@universitya.edu.my', '123456', 'u15.svg', 0, 'Diploma in Graphic Design, Year 1', 'Nova Frost', '+1-415-555-0133', '812345-14-7781', 'Seal Rock, San Francisco', '2026-09-16 02:24:15'),
(16, 'patrick.marlowe', 'pike@universitya.edu.my', 'sunshine7', 'u16.svg', 0, 'Bachelor of Sports Science, Year 2', 'Patrick Marlowe', '+61-7-5550-1192', '880213-05-3321', 'Cairns Esplanade, Queensland', '2026-09-14 02:24:15'),
(17, 'wren.taylor', 'wren@universitya.edu.my', 'Ocean#2021', 'u17.svg', 0, 'Diploma in Culinary Arts, Year 1', 'Wren Taylor', '+44-117-555-0147', '901122-14-6630', 'Clifton, Bristol', '2026-09-12 02:24:15'),
(18, 'blaise.otieno', 'blaise@universitya.edu.my', 'welcome1', 'u18.svg', 0, 'Bachelor of Economics, Year 3', 'Blaise Otieno', '+254-70-555-0129', '850630-10-1183', 'Diani Beach, Mombasa', '2026-09-10 02:24:15'),
(19, 'malia.keahi', 'moana@universitya.edu.my', 'monkey123', 'u19.svg', 0, 'Diploma in Early Childhood Education, Year 2', 'Malia Keahi', '+1-808-555-0118', '930407-07-2290', 'Hanauma Bay, Oahu', '2026-09-08 02:24:15'),
(20, 'haru.takeda', 'haru@universitya.edu.my', 'shutter88', 'u20.svg', 0, 'Bachelor of Mechatronics Engineering, Year 2', 'Haru Takeda', '+81-98-555-0176', '870919-14-4452', 'Kerama Islands, Okinawa', '2026-09-06 02:24:15'),
(21, 'noor.haidari', 'noor@universitya.edu.my', 'blue4ever', 'u21.svg', 0, 'Bachelor of Pharmacy, Year 4', 'Noor Haidari', '+92-21-555-0164', '950228-10-9987', 'Clifton Beach, Karachi', '2026-09-04 02:24:15'),
(22, 'elle.dufresne', 'elle@universitya.edu.my', 'Diving!23', 'u22.svg', 0, 'Bachelor of Fine Arts, Year 1', 'Elle Dufresne', '+33-4-555-0188', '890815-09-2214', 'Calanques, Marseille', '2026-09-02 02:24:15'),
(23, 'kai.soon', 'kai2@universitya.edu.my', 'seaside01', 'u23.svg', 0, 'Diploma in Logistics Management, Year 2', 'Kai Soon', '+65-8555-0142', '920503-08-5567', 'Sentosa, Singapore', '2026-08-31 02:24:15'),
(24, 'ana.belmonte', 'ana@universitya.edu.my', 'coral2020', 'u24.svg', 0, 'Bachelor of Architecture, Year 3', 'Ana Belmonte', '+34-9-555-0157', '910110-14-3345', 'Tarifa, Cadiz', '2026-08-29 02:24:15'),
(25, 'ali.rahman', 'tester1@example.com', 'test1234', NULL, 0, '', 'Ali Rahman', ' 60123456789', '', '', '2026-10-06 02:24:25'),
(26, 'daniel.wong', 'farmbot1@example.com', 'farmbot', NULL, 0, '', '', '', '', '', '2026-10-06 02:34:24'),
(27, 'raymond.villanueva', 'ray@universitya.edu.my', 'abc12345', NULL, 0, 'Bachelor of Hospitality Management, Year 2', 'Raymond Villanueva', '+63-917-234-5567', '880214-25-1123', '14 Rizal Ave, Cebu City', '2026-10-05 06:48:52'),
(28, 'lin.weixuan', 'lin@universitya.edu.my', 'trustno1', NULL, 0, 'Diploma in Information Technology, Year 2', 'Lin Wei Xuan', '+65-9123-4567', '920630-10-2245', '88 Marina Bay Dr, Singapore', '2026-10-04 06:48:52'),
(29, 'theo.novak', 'theo@universitya.edu.my', 'Summer#19', NULL, 0, 'Bachelor of Mechanical Engineering, Year 3', 'Theo Novak', '+385-91-234-567', '870912-19-3367', '5 Riva Splita, Split', '2026-10-03 06:48:52'),
(30, 'priya.nair', 'priya@universitya.edu.my', 'waves1234', NULL, 0, 'Diploma in Nursing, Year 1', 'Priya Nair', '+91-98765-43210', '950227-27-4489', '12 Marine Drive, Kochi', '2026-10-02 06:48:52'),
(31, 'max.ferreira', 'max@universitya.edu.my', 'Pa55word', NULL, 0, 'Bachelor of Civil Engineering, Year 4', 'Max Ferreira', '+55-21-96555-1234', '910804-33-5501', '200 Av. Atlantica, Rio de Janeiro', '2026-10-01 06:48:52'),
(32, 'june.okafor', 'june@universitya.edu.my', 'dolphin77', NULL, 0, 'Diploma in Business Studies, Year 2', 'June Okafor', '+44-7700-900321', '930515-99-6623', '9 Harbourside, Brighton', '2026-09-30 06:48:52'),
(33, 'arif.hidayat', 'arif@universitya.edu.my', 'kayak2019', NULL, 0, 'Bachelor of Islamic Finance, Year 3', 'Arif Hidayat', '+62-812-3456-789', '890930-71-7745', '21 Jalan Pantai, Bali', '2026-09-29 06:48:52'),
(34, 'isabella.rossi', 'bella@universitya.edu.my', 'Nemo@321', NULL, 0, 'Diploma in Fashion Design, Year 1', 'Isabella Rossi', '+39-333-123-4567', '960118-58-8867', '3 Via del Mare, Cinque Terre', '2026-09-28 06:48:52'),
(35, 'hector.alvarado', 'hector@universitya.edu.my', 'tides999', NULL, 0, 'Bachelor of Biotechnology, Year 2', 'Hector Alvarado', '+52-55-1234-5678', '900723-09-9989', '77 Costera Miguel, Acapulco', '2026-09-27 06:48:52'),
(36, 'mei.fujimoto', 'mei@universitya.edu.my', 'marina88', NULL, 0, 'Diploma in Culinary Arts, Year 2', 'Mei Fujimoto', '+81-90-1234-5678', '940409-13-1021', '4-2 Enoshima, Fujisawa', '2026-09-26 06:48:52'),
(37, 'oskar.lindqvist', 'oskar@universitya.edu.my', 'saltlife1', NULL, 0, 'Bachelor of Computer Science, Year 3', 'Oskar Lindqvist', '+46-70-123-4567', '861203-00-2143', '15 Strandvagen, Gothenburg', '2026-09-25 06:48:52'),
(38, 'tariq.osman', 'tariq@universitya.edu.my', 'Reef!2022', NULL, 0, 'Diploma in Accounting, Year 1', 'Tariq Osman', '+20-100-123-4567', '920506-20-3265', '6 Corniche Rd, Alexandria', '2026-09-24 06:48:52'),
(39, 'nia.campbell', 'nia@universitya.edu.my', 'anchor123', NULL, 0, 'Bachelor of Veterinary Science, Year 2', 'Nia Campbell', '+64-21-123-4567', '950811-31-4387', '31 Marine Parade, Mount Maunganui', '2026-09-23 06:48:52'),
(40, 'benji.kruger', 'benji@universitya.edu.my', 'skyline7', NULL, 0, 'Diploma in Information Technology, Year 1', 'Benji Kruger', '+27-82-123-4567', '890127-44-5409', '2 Beach Rd, Cape Town', '2026-09-22 06:48:52'),
(41, 'soojin.park', 'soojin@universitya.edu.my', 'breeze44', NULL, 0, 'Bachelor of Multimedia Design, Year 3', 'Soo-jin Park', '+82-10-1234-5678', '970322-55-6521', '19 Haeundae-ro, Busan', '2026-09-21 06:48:52'),
(42, 'dario.esposito', 'dario@universitya.edu.my', 'Lagoon#8', NULL, 0, 'Diploma in Culinary Arts, Year 3', 'Dario Esposito', '+39-347-765-4321', '910615-66-7643', '41 Via Posillipo, Naples', '2026-09-20 06:48:52');

-- Class applications. ahmad.faiz's is first, so the writeups can rely on it.
INSERT INTO applications (user_id, course_title, file_path, status, admin_response, created_at) VALUES
(2, 'Introduction to Networks', 'intake1/seed_dan.jpg',   'pending',  NULL,                                              DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'Introduction to Networks', 'intake1/seed_sarah.jpg', 'pending',  NULL,                                              DATE_SUB(NOW(), INTERVAL 4 DAY)),
(4, 'Introduction to Networks', 'intake1/seed_jack.jpg',  'approved', 'Panel note: accepted into Introduction to Networks.', DATE_SUB(NOW(), INTERVAL 6 DAY));

INSERT INTO class_sessions (title, intake, starts_at, location, seats, taken, notes) VALUES
('Introduction to Networks',       1, DATE_ADD(NOW(), INTERVAL  6 DAY), 'Lecture Hall A, Faculty of Computing',     40, 38, 'Bring your laptop; campus Wi-Fi registration is required before class.'),
('Database Systems Lab',           2, DATE_ADD(NOW(), INTERVAL 13 DAY), 'Computer Lab 3, FSKTM',                    30, 30, 'USB drive recommended. No food or drinks in the lab.'),
('Academic Writing Workshop',      3, DATE_ADD(NOW(), INTERVAL 27 DAY), 'Seminar Room 2, Library Block',            25, 10, 'Bring a draft of your current assignment.'),
('Industrial Training Briefing',   4, DATE_ADD(NOW(), INTERVAL 41 DAY), 'Auditorium, Student Affairs Building',    100, 42, 'Attendance is compulsory for final-year students.');
