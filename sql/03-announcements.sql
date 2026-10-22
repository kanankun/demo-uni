-- Real announcements, decoupled from the student photo feed.
--
-- `uploads` is the student photo/gallery feature (feed_card.php, tag.php,
-- members.php, saved.php, view.php - comments and likes included). It was
-- never meant to double as the registrar's notice board, and listing every
-- student's photo post as a "university announcement" is exactly backwards:
-- stale photo-caption titles, no way to tell who could actually post one,
-- and a flood of entries nobody from the registrar's office wrote.
--
-- Announcements get their own table instead: short, text-only, admin-posted.
CREATE TABLE announcements (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    title      VARCHAR(150) NOT NULL,
    body       TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO announcements (user_id, title, body, created_at) VALUES
(1, 'Semester 2 course registration opens 2 March',
 'Course registration for Semester 2 opens on 2 March and closes on 16 March. Register through the COURSES tab - late registration after the deadline requires a Faculty override form from your department office.',
 DATE_SUB(NOW(), INTERVAL 1 DAY)),

(1, 'Mid-semester exam timetable released',
 'The mid-semester exam timetable is now posted under the TIMETABLE tab. Seat and hall numbers will be published one week before the first paper. Bring your student ID - no ID, no entry to the exam hall.',
 DATE_SUB(NOW(), INTERVAL 3 DAY)),

(1, 'Library extended hours during finals week',
 'The Main Library will stay open from 8am to midnight for the two weeks before finals, including weekends. Extra study pods are available on Level 3 - book a slot at the library counter.',
 DATE_SUB(NOW(), INTERVAL 6 DAY)),

(1, 'Scheduled Wi-Fi maintenance - Block C and Block D',
 'IT Services will be upgrading network switches in Block C and Block D this Friday from 11pm to 3am. Expect intermittent Wi-Fi during that window. Wired lab connections are not affected.',
 DATE_SUB(NOW(), INTERVAL 9 DAY)),

(1, 'Fee payment deadline reminder',
 'Semester fees are due by the 20th of this month. A late payment surcharge applies automatically after the deadline. Check your outstanding balance under the FEES tab and pay online or at the Bursary counter.',
 DATE_SUB(NOW(), INTERVAL 14 DAY)),

(1, 'Convocation registration open for graduating students',
 'Students graduating this year can register for the convocation ceremony through the Registrar''s Office. Gown fitting and rehearsal dates will be shared by email once registration closes.',
 DATE_SUB(NOW(), INTERVAL 20 DAY));
