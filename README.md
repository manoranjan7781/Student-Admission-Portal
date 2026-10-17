# Student Admission Portal

**U21CS501 Web Technology - Assignment II (2026-27)** - KPR Institute of Engineering and Technology

A student admission portal in three parts that share ONE MySQL database:

1. **Front end** - responsive HTML5/CSS3 registration form with JavaScript validation.
2. **PHP module** - CRUD for students, secure file uploads, login and roles.
3. **Servlet module** - Java Servlets + JDBC + JSP that display profiles and stream the uploaded files to logged-in users.

---

## 1. Assignment question mapping

| Question | Requirement | Where it is implemented |
|---|---|---|
| **Q1** (8) | HTML form: name, roll no., email, course, password, confirm, file upload | `frontend/index.html` |
| | Responsive CSS3 (layout, colours, error styles) | `frontend/style.css` |
| | JS validation: required, email format, password strength, match | `frontend/script.js` |
| **Q2** (16) | Add / View / Edit / Delete students in MySQL | `php-module/add_student.php`, `view_students.php`, `view_student.php`, `edit_student.php`, `delete_student.php` |
| | Upload profile image + ID proof + certificate | `php-module/upload.php` (used by add/edit/register) |
| | Metadata (path, type, date, ...) in MySQL | table `student_files` (`database/schema.sql`) |
| | Show uploaded files on the profile | `view_student.php` + `download.php` |
| | Size/type validation, no overwriting | `validate_upload()` / `store_upload()` in `upload.php` |
| **Q3** (16) | JDBC to MySQL, fetch students + file paths | `servlet-module/.../dao/StudentDAO.java` |
| | Servlet streams images/documents | `servlet/FileServlet.java` (`/file?id=N`) |
| | JSP profiles with `<img>` pointing to the Servlet; loops + conditions | `webapp/students.jsp`, `student-profile.jsp` |
| | Session handling, only logged-in users | `filter/AuthFilter.java`, `LoginServlet`, `LogoutServlet` |
| | WEB-INF configuration | `webapp/WEB-INF/web.xml`, `pom.xml` |

Submission items: source files (done), DB schema (`database/schema.sql`), screenshots (`screenshots/`), inline comments (every source file), conclusion (section 14), GitHub link (section 12).

## 2. Features

* Live validation with a progress checklist, password-strength meter, show/hide password, accessible error messages; the server repeats every check.
* Public registration creates the student **and** a login account (password stored as a bcrypt hash).
* Admin: add, search, page through, view, edit, delete students; replace profile image; delete individual files.
* Two roles: **admin** (everything) and **student** (only their own profile and files). Enforced in PHP *and* in Java.
* Uploads: size limit, real MIME detection (not the file extension), allow-list, random file names, one folder per student, deleted from disk when the student is deleted.
* Servlet portal: login/logout, session-ID rotation, authentication filter, authorised file streaming, friendly error pages.

## 3. Technologies

HTML5, CSS3, vanilla JavaScript - PHP 8.1+ (PDO) - MySQL 8 (MariaDB 10.5+ also works) - Java 17, Servlet 6.0 / JSP 3.1 (Jakarta EE 10), JDBC, Maven - Apache Tomcat 10.1 - jBCrypt (for PHP-compatible password checks).

## 4. Folder structure

```
Student-Admission-Portal/
├── frontend/            index.html, style.css, script.js, assets/
├── php-module/
│   ├── config/          database.php, config.example.php  (config.local.php = your secrets, git-ignored)
│   ├── includes/        auth.php, functions.php, header.php, footer.php, student_form.php
│   ├── bin/             create_admin.php   (command-line only)
│   ├── assets/          style.css (copy of frontend/style.css)
│   ├── uploads/         uploaded files (git-ignored, web access denied)
│   └── index, login, logout, register, csrf_token, add_student, view_students,
│       view_student, edit_student, delete_student, upload (library), download, router
├── servlet-module/
│   ├── pom.xml
│   └── src/main/
│       ├── java/com/studentportal/  config/ model/ dao/ servlet/ filter/ util/
│       ├── resources/application.properties.example
│       └── webapp/  index.jsp login.jsp students.jsp student-profile.jsp error.jsp
│                    WEB-INF/web.xml  WEB-INF/jspf/  assets/
├── database/            schema.sql, sample_data.sql
├── screenshots/         real browser screenshots + README.md
├── TESTING.md
└── README.md
```

## 5. Prerequisites (Windows)

| Tool | Version | Notes |
|---|---|---|
| XAMPP (Apache + PHP + MySQL/MariaDB) | PHP 8.1+ | or install PHP and MySQL separately |
| JDK | 17 | `java -version`, `javac -version` |
| Maven | 3.8+ | `mvn -version` |
| Apache Tomcat | **10.1.x** | NOT 9 - it needs the `jakarta.*` packages |
| Git | any | for GitHub |

Put the project at `C:\xampp\htdocs\Student-Admission-Portal` (simplest), or anywhere and use the PHP built-in server (see 8).

## 6. Database setup

1. Start MySQL (XAMPP Control Panel -> MySQL -> Start).
2. Import the schema (**this recreates the three tables and erases their data**):
   ```
   mysql -u root -p < database\schema.sql
   mysql -u root -p student_portal < database\sample_data.sql     (optional demo rows)
   ```
   or use phpMyAdmin -> Import.
3. Create a dedicated database user (do not run the apps as root):
   ```sql
   CREATE USER 'portal'@'localhost' IDENTIFIED BY 'choose_a_password';
   GRANT ALL ON student_portal.* TO 'portal'@'localhost';
   ```

## 7. PHP setup

1. Copy `php-module/config/config.example.php` to `php-module/config/config.local.php` and enter the DB user/password. Set `upload_dir` to an absolute path if you like (the Java module must use the same folder).
2. Make sure `extension=fileinfo` and `extension=pdo_mysql` are enabled in `php.ini` (XAMPP: they are by default).
3. `upload_max_filesize` and `post_max_size` in `php.ini` must be at least 5M / 10M (XAMPP default is larger). If too small, the app shows a clear message.

## 8. Create the first administrator

No password is stored in the code. Run in a terminal in the project folder:

```
php php-module\bin\create_admin.php
```

Type a username (default `admin`) and a password (8+ chars with upper, lower, digit, symbol). The script stores a bcrypt hash. Running it again for the same username resets that admin's password.

## 9. Run the PHP module

**With XAMPP/Apache:** open `http://localhost/Student-Admission-Portal/frontend/index.html` (registration) and `http://localhost/Student-Admission-Portal/php-module/login.php` (admin).

**Without Apache (built-in server):** from the project root

```
php -S localhost:8000 -t . php-module/router.php
```
then `http://localhost:8000/frontend/index.html` and `http://localhost:8000/php-module/login.php`. (`router.php` blocks direct browsing of `uploads/`, `config/`, `includes/`.)

> The registration page must be opened through `http://...`, never by double-clicking the file - it fetches a CSRF token from the PHP server.

## 10. Run the Java/Tomcat module

1. `copy servlet-module\src\main\resources\application.properties.example servlet-module\src\main\resources\application.properties` and fill in the DB user/password and **the same `upload.dir`** as PHP (use `/` slashes).
2. Build: `cd servlet-module` then `mvn clean package` -> `target\student-portal.war`.
3. Copy the WAR to `<TOMCAT>\webapps\` and run `<TOMCAT>\bin\startup.bat`.
4. Open `http://localhost:8080/student-portal/login` and log in with the admin (or any student's email) created above.

Environment variables `DB_URL`, `DB_USER`, `DB_PASSWORD`, `UPLOAD_DIR` override the properties file.

## 11. URLs

| Module | URL |
|---|---|
| Registration form | `/frontend/index.html` |
| PHP login / dashboard / students | `/php-module/login.php`, `index.php`, `view_students.php` |
| Servlet login | `http://localhost:8080/student-portal/login` |
| Servlet list / profile / file | `/students`, `/student?id=N`, `/file?id=N` |

## 12. GitHub upload

```
cd Student-Admission-Portal
git init
git add .
git status                      # check: no config.local.php, no application.properties, no uploads
git commit -m "Web Technology Assignment II - Student Admission Portal"
git branch -M main
git remote add origin https://github.com/<your-username>/Student-Admission-Portal.git
git push -u origin main
```
Create the (public) repository on github.com first, without a README. Paste the repository link into the submission form.

## 13. Testing

See **TESTING.md** (test cases, expected results, results already observed, and a column for your own results).

Quick manual check: register a student -> log in to PHP as admin -> add a student with ID proof -> edit it -> open the Servlet portal -> see the same student with the image -> log out -> try `/students` again (you must be sent to the login page).

## 14. Security features and known limits

**Implemented:** prepared statements everywhere (PDO and JDBC `PreparedStatement`); bcrypt password hashes shared by PHP and Java; CSRF tokens on every POST form (login, logout, add, edit, delete, register); output escaping (`htmlspecialchars` / `HtmlUtil.escape`); session-ID regeneration on login and full invalidation on logout; HttpOnly + SameSite cookies; role-based access (a student cannot open another student's profile or files); file endpoints check authentication and ownership and accept only a database id; uploads validated by size, detected MIME type and allow-list, saved under random names outside direct web access; path-traversal protection with `realpath`/`normalize + startsWith`; generic error pages (details only in server logs); secrets in git-ignored files.

**Not implemented (be ready to mention):** HTTPS (needed in production; then set cookie `secure`), login rate-limiting/lockout, CAPTCHA and e-mail verification on public registration, a JDBC connection pool, anti-virus scanning of uploads, audit logging. The public registration form is protected by a CSRF token and a honeypot field only.

**Design notes:** the JSP pages use scriptlets plus an escaping helper instead of JSTL, so the loops and `if` statements are visible and no extra tag-library dependency is needed.

## 15. Common errors and fixes

| Problem | Fix |
|---|---|
| "Cannot reach the PHP server" on the form | Open the form via `http://localhost...`, make sure Apache/PHP server is running |
| "database is not available" | Check `config.local.php`, MySQL running, user has rights |
| Upload says "larger than the server allows" | Raise `upload_max_filesize` / `post_max_size` in php.ini, restart Apache |
| `ClassNotFoundException: javax.servlet...` / 404 for everything | You are on Tomcat 9; use Tomcat 10.1 |
| `JDBC driver not found` | Build with Maven so `mysql-connector-j` is inside the WAR |
| `Communications link failure` | MySQL not running or wrong `db.url` |
| Servlet shows "file is missing" | `upload.dir` differs from PHP's `upload_dir` |
| Login fails in Java but works in PHP | Same database? Same user? Check `db.url` points to `student_portal` |
| 403 on a page | You are logged in as a student; admin-only page |
| Port 8080 busy | Change the Tomcat connector port or stop the other program |

## 16. Screenshot checklist

See `screenshots/README.md` for exact steps and file names.

## 17. Conclusion

This project joins a validated HTML/CSS/JavaScript registration form, a PHP/MySQL admin panel and a Java Servlet/JSP portal on one shared database. Uploaded files are verified on the server, stored under random names with metadata in MySQL, and delivered only through authorised endpoints. Prepared statements, bcrypt hashing, CSRF tokens, output escaping and session control show how the same security rules apply in both PHP and Java. The work gave practical experience with full-stack design, JDBC/PDO and secure file handling.
