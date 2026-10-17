# Screenshots

The files in this folder were captured from the **running applications** with a headless Chromium browser (PHP built-in server on port 8000, Tomcat 10.1 on port 8080, MariaDB). The sample profile photos / documents are synthetic test files (a coloured initial, a "sample ID card"), not real people.

**Recommended:** repeat the steps below on YOUR computer (XAMPP + Tomcat) and replace these files with your own captures (Windows: `Win + Shift + S`; include the address bar so the URL is visible).

| # | What to capture | File in this folder | How to reproduce |
|---|---|---|---|
| 1 | Registration page | `01-registration-page.png`, `02b-registration-mobile-responsive.png` | open `frontend/index.html` via http; press F12 -> device toolbar for mobile view |
| 2 | Client-side validation errors | `02-client-validation-errors.png` | type `Al`, `1`, `bad@`, `abc` / `abd`, press Submit |
| 3 | Successful registration | `02c-registration-filled-valid.png`, `03-registration-success.png` | fill valid data, submit -> login page with green message |
| 4 | PHP student listing | `04-php-student-listing.png`, `04b-php-search.png`, `04a-php-admin-dashboard.png` | log in as admin -> Students |
| 5 | Add / edit student | `05a-php-add-student-form.png`, `06-php-edit-student.png`, `07b-php-after-edit.png` | Add student / Edit |
| 6 | Upload validation + metadata | `06b-php-upload-rejected.png`, `07-php-student-profile-with-files.png` | upload a text file renamed `.png` (rejected); table lists original name, stored name, MIME, size, date |
| 7 | Student profile | `07-...`, `08-php-student-own-profile.png` | open a student; log in as a student |
| 8 | Servlet student list | `09-servlet-student-listing.png` | `http://localhost:8080/student-portal/students` |
| 9 | Servlet profile + streamed image | `11-servlet-student-profile.png`, `12-servlet-streamed-image.png` | open a profile; open the image URL `/file?id=N` |
| 10 | Login / logout / unauthorized | `10b-php-login-failed.png`, `10c-php-student-forbidden-other-profile.png`, `10d-php-unauthenticated-redirect.png`, `10e-servlet-unauthorized-redirect.png`, `10f-servlet-after-logout.png`, `10g-servlet-access-after-logout.png`, `12b-servlet-invalid-id-404.png` | wrong password; student opens another's profile; open pages while logged out |
