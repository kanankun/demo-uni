-- Assignments. Replaces the file-upload logic that used to live in
-- upload.php (an admin "posting an announcement" by attaching a photo -
-- a photo-sharing leftover, not a university feature). A student portal
-- already has an obvious, real use for "upload a file": coursework
-- submission. Lecturers post an assignment with a due date; students
-- upload one file for it; staff can see who has and hasn't submitted.
CREATE TABLE assignments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(150) NOT NULL,
    instructions TEXT NOT NULL,
    due_at       DATE NOT NULL,
    created_by   INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE assignment_submissions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    user_id       INT NOT NULL,
    file_path     VARCHAR(255) NOT NULL,
    submitted_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
    UNIQUE KEY one_submission_per_student (assignment_id, user_id)
);

INSERT INTO assignments (title, instructions, due_at, created_by, created_at) VALUES
('Database Systems Lab - ER diagram',
 'Submit the entity-relationship diagram for the mini project described in Week 6''s lab sheet. One file per student, PDF or image, labelled with your matric number.',
 DATE_ADD(CURDATE(), INTERVAL 5 DAY), 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),

('Academic Writing Workshop - draft outline',
 'Upload a one-page outline of your essay: thesis statement, three supporting points, and your sources so far. This is a draft - it will not be graded, only checked for progress.',
 DATE_ADD(CURDATE(), INTERVAL 10 DAY), 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),

('Introduction to Networks - subnetting exercise',
 'Complete the subnetting worksheet from Chapter 4 and upload your working as a single PDF. Show your calculations, not just the final answers - partial credit depends on it.',
 DATE_SUB(CURDATE(), INTERVAL 1 DAY), 1, DATE_SUB(NOW(), INTERVAL 10 DAY));
