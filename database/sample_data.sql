-- Optional demo students (NO passwords, NO files, NO real personal data).
-- Import AFTER schema.sql:  mysql -u root -p student_portal < database/sample_data.sql
-- Create the admin login with:  php php-module/bin/create_admin.php
USE student_portal;
INSERT INTO students (name, roll_number, email, course) VALUES
 ('Demo Student One',   '21CS001', 'demo.one@example.com',   'B.E. Computer Science and Engineering'),
 ('Demo Student Two',   '21EC002', 'demo.two@example.com',   'B.E. Electronics and Communication Engineering'),
 ('Demo Student Three', '21IT003', 'demo.three@example.com', 'B.Tech Information Technology');
