# Jobhunt

A job board built with PHP and MySQL. Job seekers can search and filter jobs, save the ones they like, read about companies and apply online. Employers can post jobs, and an admin can manage jobs and review applications.

## Screenshots

![Home](screenshots/home.png)

![Find jobs](screenshots/jobs.png)

![Job page](screenshots/job.png)

## What it does

- Search by keyword, category, location, work mode, job type and experience level
- Sort by newest or salary, with pagination and removable filter chips
- Accounts for job seekers and employers (register, log in, log out) with bcrypt password hashing
- Job seekers: save jobs with one click (stored in the database, so they stay after logout) and follow each application in My applications
- Employers: a dashboard to post jobs under their own company, see applications for their own jobs only, and update each status
- Job detail page with responsibilities, requirements, skills, company profile and similar jobs
- Apply form for logged in job seekers, with server side validation and a duplicate check (one application per person per job)
- Company pages with their open roles
- Admin area: dashboard counts, feature or delete jobs, and move applications through New, Reviewed, Shortlisted and Rejected

## Built with

- PHP 8 (no framework)
- MySQL with PDO and prepared statements
- Alpine.js (menu and apply form), Axios (save job and apply requests) and Chart.js (admin and employer charts), all included in `assets/vendor` so no internet is needed
- HTML and CSS written by hand, Google Fonts (Space Grotesk and Karla)

## Run it locally with XAMPP

1. Copy this folder to `C:\xampp\htdocs\jobhunt`
2. Start Apache and MySQL in the XAMPP Control Panel
3. Open http://localhost/phpmyadmin, click Import, choose `db/schema.sql`, click Import
4. Open http://localhost/jobhunt/

Demo logins:

- Job seeker: `demo@jobhunt.test` / `demo123`
- Employer (Northwind Digital): `hr@northwind.test` / `hr12345`
- Admin (http://localhost/jobhunt/admin/login.php): `admin@jobhunt.test` / `admin123`

You can also create your own job seeker or employer account on the Sign up page.

## Real logos

Company logos are drawn automatically from the company name. To use a real logo, put an image named after the company slug in `assets/img/companies/` (for example `northwind-digital.png`).

## Notes

The companies and jobs are fictional demo data. Salaries are yearly, in US dollars. Passwords are stored with bcrypt. All database queries use prepared statements. Employers can only change or delete things that belong to their own company, and the server checks this on every request.
