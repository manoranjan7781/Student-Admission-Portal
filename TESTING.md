# TESTING.md

**How "Observed" was produced:** automated `curl` and headless-Chromium (Puppeteer) runs on a Linux sandbox - PHP 8.3 built-in server, Tomcat 10.1.55, MariaDB 10.11 (MySQL-compatible), JDK 17. The Servlet module was compiled with `javac` and packaged as a WAR by hand (see "Not verified"). **Fill the last column yourself** when you test on your PC.

Legend: PASS = observed as expected.

## A. Front end (Q1)

| # | Test | Expected | Observed (sandbox) | My result |
|---|---|---|---|---|
| A1 | Open `frontend/index.html` | Styled form with 7 fields | PASS (`01-registration-page.png`) | |
| A2 | Submit with `Al`, `1`, `bad@`, no course, `abc`/`abd` | Per-field red messages + summary, nothing sent | PASS (`02-client-validation-errors.png`) | |
| A3 | Type a weak then strong password | Strength meter changes colour/label | PASS (meter "Very weak" seen) | |
| A4 | Phone width 390 px | Single-column layout | PASS (`02b-...`) | |
| A5 | Valid data + photo, submit | Goes to PHP login page with success message | PASS (`03-registration-success.png`) | |
| A6 | Tamil-letter name accepted by JS and PHP regex | Valid | PASS (regex test only) | |

## B. PHP + MySQL (Q2)

| # | Test | Expected | Observed | My result |
|---|---|---|---|---|
| B1 | `index.php`, `view_students.php`, `add_student.php` while logged out | Redirect to login | PASS (302) | |
| B2 | `download.php?id=1` logged out | 401 | PASS | |
| B3 | Browse `/php-module/uploads/`, `config/config.local.php`, `includes/auth.php` | 403 (router; `.htaccess` on Apache) | PASS on built-in server; Apache `.htaccess` not run | |
| B4 | Register without CSRF token | 400 | PASS | |
| B5 | Register: bad email, weak password, mismatch, text file as photo | Four server-side error messages | PASS | |
| B6 | Register with 3 MB image | "too large (3.0 MB). Maximum is 2.0 MB" | PASS | |
| B7 | Valid registration | Student + user rows, bcrypt `$2y$` hash, file row, file on disk | PASS | |
| B8 | Duplicate roll number | "already registered" | PASS | |
| B9 | Wrong password / unknown user | Same generic message | PASS (`10b-...`) | |
| B10 | Session id before/after login | Changes | PASS | |
| B11 | Admin adds student with photo + PDF + PNG | 3 rows in `student_files` with correct MIME, size, random name, path `s<id>/<hash>.<ext>` | PASS | |
| B12 | Text file renamed `.pdf` as ID proof | Rejected, **no** student row created | PASS | |
| B13 | Edit course + replace photo | Row updated; old photo row and file removed, new one added | PASS | |
| B14 | Edit to another student's roll number | Error message | PASS | |
| B15 | Search `priya`; search `'; DROP TABLE students;--` | Match found; "No students found", table intact | PASS (after fixing a PDO placeholder bug found by this test) | |
| B16 | Name stored as `<img onerror=...>` | Shown escaped | PASS | |
| B17 | Delete file, then delete student | Rows (student, user, files) and disk files gone | PASS | |
| B18 | `GET delete_student.php`; POST delete without token | 405; 400 | PASS | |
| B19 | Student opens own profile / other profile / list / edit | 200 / 403 / 403 / 403 | PASS | |
| B20 | Student downloads another student's file | 403 | PASS | |
| B21 | `view_student.php?id=abc`, `id=999`, `download.php?id=999`, `id=1/../../x` | 400, 404, 404, 400 | PASS | |
| B22 | Planted DB row with `file_path=../../../../../etc/passwd` | 404, no content leaked | PASS | |
| B23 | Table renamed to force DB error | Friendly 500, no SQL/stack trace | PASS | |
| B24 | Download headers | `Content-Type`, `nosniff`, CSP, safe `Content-Disposition` | PASS | |

## C. Servlet + JDBC (Q3)

| # | Test | Expected | Observed | My result |
|---|---|---|---|---|
| C1 | `/students`, `/student?id=1`, `/index.jsp`, `/students.jsp` logged out | Redirect to `/login?unauthorized=1` | PASS | |
| C2 | `/file?id=1` logged out | 401 | PASS | |
| C3 | `/WEB-INF/web.xml` | 404 | PASS | |
| C4 | Wrong password; login without CSRF token | 401 with message; 400 | PASS | |
| C5 | Login with PHP-created admin and student hashes | 302 to `/students` | PASS (bcrypt `$2y$` compatible) | |
| C6 | Session id changes; cookie HttpOnly | Yes | PASS | |
| C7 | Admin list page | One card per student, `<img src=".../file?id=N">`, "No documents uploaded" where none | PASS (`09-...`) | |
| C8 | Profile page | Details, metadata table, image preview, links | PASS (`11-...`) | |
| C9 | `student?id=abc` / none / `-5` / `99999` | 400 / 400 / 400 / 404 | PASS | |
| C10 | Stream the PDF | Same bytes (`cmp`), `application/pdf`, length, inline, nosniff | PASS | |
| C11 | `file?id=abc` / `999999` | 400 / 404 | PASS | |
| C12 | Student: own profile / other profile / own photo / other's PDF | 200 / 403 / 200 / 403 | PASS | |
| C13 | Student opens `/students` | Redirect to own profile | PASS | |
| C14 | `GET /logout`; POST without token; POST with token | Nothing; 400; session destroyed, redirect | PASS | |
| C15 | After logout `/students` and `/file` | Redirect to login; 401 | PASS | |
| C16 | Traversal row / missing file | 404 "missing on the server" | PASS | |
| C17 | DB failure | Friendly 500, error only in log | PASS | |

## D. Not verified in the sandbox (please test yourself)

* `mvn clean package` - Maven Central was not reachable, so dependencies could not be downloaded; the `pom.xml` uses standard coordinates, and `javac` compilation + a hand-built WAR were tested instead.
* MySQL Connector/J 8.4 and a real MySQL 8 server (MariaDB + MariaDB Connector/J were used).
* Windows, XAMPP/Apache `.htaccess` rules, Chrome/Edge/Firefox on your PC.
* GitHub push and the Google Form submission (not done by me).

## E. Bugs found and fixed during testing

1. Search page crashed: a named PDO placeholder (`:q`) was used four times - real prepared statements need unique names. Fixed (`:q1`-`:q4`).
2. Added a global exception handler so uncaught errors never display stack traces (found while testing item 1).
