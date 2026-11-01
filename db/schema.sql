-- Jobhunt database (v3): companies, categories, jobs, applications, users, saved jobs, admins
-- Import with: mysql -u root -p < db/schema.sql
--
-- Demo admin login:      admin@jobhunt.test / admin123
-- Demo job seeker:       demo@jobhunt.test / demo123
-- Demo employer (Northwind Digital): hr@northwind.test / hr12345

CREATE DATABASE IF NOT EXISTS jobhunt CHARACTER SET utf8mb4;
USE jobhunt;

DROP TABLE IF EXISTS saved_jobs;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS job_listings;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS admins;

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    industry VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    website VARCHAR(200),
    about TEXT,
    size_label VARCHAR(40) NOT NULL DEFAULT '11 to 50 people',
    color VARCHAR(7) NOT NULL DEFAULT '#1F5340',
    shape TINYINT NOT NULL DEFAULT 0
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(60) NOT NULL UNIQUE,
    icon VARCHAR(30) NOT NULL
);

CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    location VARCHAR(100) NOT NULL,
    work_mode ENUM('On-site', 'Hybrid', 'Remote') NOT NULL DEFAULT 'On-site',
    job_type ENUM('Full-time', 'Part-time', 'Contract', 'Internship') NOT NULL DEFAULT 'Full-time',
    level ENUM('Entry', 'Mid', 'Senior') NOT NULL DEFAULT 'Entry',
    salary_min INT NOT NULL DEFAULT 0,
    salary_max INT NOT NULL DEFAULT 0,
    summary TEXT NOT NULL,
    responsibilities TEXT NOT NULL,
    requirements TEXT NOT NULL,
    skills VARCHAR(255) NOT NULL DEFAULT '',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    deadline DATE,
    posted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_posted (posted_at),
    INDEX idx_category (category_id)
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('seeker', 'employer') NOT NULL DEFAULT 'seeker',
    company_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
);

CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(40),
    portfolio_url VARCHAR(250),
    cover_note TEXT,
    status ENUM('New', 'Reviewed', 'Shortlisted', 'Rejected') NOT NULL DEFAULT 'New',
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY one_application_per_job (job_id, email)
);

CREATE TABLE saved_jobs (
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    saved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, job_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

INSERT INTO admins (email, password_hash) VALUES
('admin@jobhunt.test', '$2y$10$mGAN.gxmWCg2Qes.OrCYC.6rsm3VSSimXJ26Z9d09cjuK2b5HMrym');

INSERT INTO categories (name, slug, icon) VALUES
('Engineering and IT', 'engineering', 'code'),
('Design and Creative', 'design', 'pen'),
('Marketing and Sales', 'marketing', 'megaphone'),
('Finance and Accounting', 'finance', 'chart'),
('Healthcare', 'healthcare', 'cross'),
('Education and Training', 'education', 'book'),
('Customer Support', 'support', 'headset'),
('Operations and Logistics', 'operations', 'box'),
('Hospitality and Retail', 'hospitality', 'bag');

-- All companies below are made up for this demo.
INSERT INTO companies (name, slug, industry, location, website, about, size_label, color, shape) VALUES
('Northwind Digital', 'northwind-digital', 'Software', 'Austin, TX', 'https://example.com/northwind', 'Northwind Digital builds booking and inventory tools for small shops across North America. The team ships weekly and cares about simple, fast screens.', '51 to 200 people', '#1F6F5C', 0),
('Cedar Health', 'cedar-health', 'Healthcare', 'Denver, CO', 'https://example.com/cedar', 'Cedar Health runs a network of family clinics. We focus on short waiting times and clear communication with patients.', '201 to 500 people', '#2F5D9E', 1),
('Summit Logistics', 'summit-logistics', 'Logistics', 'Dallas, TX', 'https://example.com/summit', 'Summit Logistics moves parcels for online sellers with warehouses in Texas and Florida and a support team that works across time zones.', '201 to 500 people', '#C2571A', 2),
('Brightpath Learning', 'brightpath-learning', 'Education', 'Raleigh, NC', 'https://example.com/brightpath', 'Brightpath Learning offers online English and maths classes for school students, taught live by trained tutors.', '51 to 200 people', '#7A3E8E', 3),
('Orchard Foods', 'orchard-foods', 'Food and Beverage', 'Portland, OR', 'https://example.com/orchard', 'Orchard Foods makes sauces and snacks from local ingredients and sells them in shops and online.', '11 to 50 people', '#2E7D32', 0),
('Orbit Pay', 'orbit-pay', 'Fintech', 'Austin, TX', 'https://example.com/orbitpay', 'Orbit Pay helps small businesses take card and mobile payments. Engineers here own features from idea to release.', '51 to 200 people', '#5B4BB7', 1),
('Lumen Studio', 'lumen-studio', 'Design Agency', 'Denver, CO', 'https://example.com/lumen', 'Lumen Studio is a small design team that works on apps, websites and brand identities for local and regional clients.', '11 to 50 people', '#A3304F', 2),
('Harbor Stay Group', 'harbor-stay-group', 'Hospitality', 'Miami, FL', 'https://example.com/harborstay', 'Harbor Stay Group manages boutique hotels in Miami and Orlando with a strong focus on guest service.', '51 to 200 people', '#0E7C86', 3),
('Clearview Accounting', 'clearview-accounting', 'Finance', 'Austin, TX', 'https://example.com/clearview', 'Clearview Accounting handles bookkeeping, payroll and tax filing for growing companies.', '11 to 50 people', '#3C4A5C', 0),
('Maple Retail', 'maple-retail', 'Retail', 'Portland, OR', 'https://example.com/maple', 'Maple Retail runs home and lifestyle stores in Oregon and Washington.', '201 to 500 people', '#8A6D1D', 1);

INSERT INTO jobs (company_id, category_id, title, location, work_mode, job_type, level, salary_min, salary_max, summary, responsibilities, requirements, skills, is_featured, deadline, posted_at) VALUES
(1, 1, 'Frontend Developer', 'Austin, TX', 'Hybrid', 'Full-time', 'Mid', 63000, 91000,
 'Build the screens that shop owners use every day to manage bookings and stock. You will work with two designers and three backend developers.',
 'Turn Figma designs into fast, accessible pages\nBuild reusable components with React\nFix layout and performance problems reported by users\nReview teammates code and share what you learn',
 '2 or more years building websites with HTML, CSS and JavaScript\nExperience with React or a similar library\nComfortable using Git in a team\nCare about small details like spacing and loading states',
 'HTML,CSS,JavaScript,React,Git', 1, DATE_ADD(CURDATE(), INTERVAL 21 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 1, 'Backend Developer (PHP)', 'Remote', 'Remote', 'Full-time', 'Mid', 70000, 105000,
 'Design and maintain the PHP and MySQL services behind our booking platform. You will own features from database to API.',
 'Write clean PHP code and REST endpoints\nDesign tables and tune slow MySQL queries\nWrite tests for the code you ship\nHelp the team fix production issues',
 '2 or more years of PHP experience\nGood knowledge of MySQL and SQL joins\nExperience with REST APIs and authentication\nFamiliar with Git and code reviews',
 'PHP,MySQL,REST APIs,Git', 1, DATE_ADD(CURDATE(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(6, 1, 'Junior Full Stack Developer', 'Austin, TX', 'Hybrid', 'Full-time', 'Entry', 49000, 70000,
 'A first or second job for a graduate who enjoys both front end and back end work. You will build internal tools used by our support and finance teams.',
 'Build small features across the stack with a mentor\nWrite forms, tables and dashboards with JavaScript\nCreate and update MySQL tables\nDocument what you build so others can use it',
 'Degree or diploma in Computer Science or a related field\nProjects using HTML, CSS, JavaScript and a database\nWilling to learn Node.js and PHP\nClear written English',
 'JavaScript,Node.js,MySQL,HTML,CSS', 1, DATE_ADD(CURDATE(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(7, 2, 'UX Researcher', 'Denver, CO', 'Hybrid', 'Full-time', 'Mid', 63000, 91000,
 'Plan and run research that helps our designers make better decisions. You will talk to real users every week.',
 'Plan interviews and usability tests\nAnalyse what users say and do, then share clear findings\nWork with designers on prototypes\nMeasure whether design changes helped',
 'Experience with user research methods\nAble to explain findings to non-researchers\nFamiliar with Figma prototypes\nInterest in cultural differences in design is a plus',
 'User research,Interviews,Usability testing,Figma', 1, DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
(7, 2, 'UI Designer', 'Denver, CO', 'On-site', 'Full-time', 'Entry', 45000, 63000,
 'Design clean screens and small design systems for client apps and websites.',
 'Design screens in Figma from rough ideas\nBuild and maintain component libraries\nPrepare files for developers\nTake feedback and improve your work',
 'A portfolio with 2 or more projects\nStrong sense of type, colour and spacing\nKnow Figma well\nAble to work with deadlines',
 'Figma,Design systems,Typography,Prototyping', 0, DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 1, 'Web Development Intern', 'Remote', 'Remote', 'Internship', 'Entry', 11000, 17000,
 'A 6 month internship for students who want real experience on a live product.',
 'Fix front end bugs with guidance\nHelp test new features\nWrite short documentation\nJoin weekly team meetings and demos',
 'Studying Computer Science or IT\nBasic HTML, CSS and JavaScript\nWilling to ask questions and learn fast',
 'HTML,CSS,JavaScript,Git', 0, DATE_ADD(CURDATE(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
(6, 1, 'Database Administrator', 'Austin, TX', 'On-site', 'Full-time', 'Senior', 84000, 119000,
 'Keep our MySQL databases fast, safe and backed up. Payments data needs a careful owner.',
 'Monitor database health and speed\nPlan backups and test restores\nManage user access and security\nHelp developers write better queries',
 '5 or more years working with MySQL\nExperience with backup and recovery\nKnowledge of database security\nCalm under pressure',
 'MySQL,Backup,Performance tuning,Security', 0, DATE_ADD(CURDATE(), INTERVAL 16 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
(5, 3, 'Digital Marketing Executive', 'Portland, OR', 'On-site', 'Full-time', 'Entry', 39000, 53000,
 'Grow our online shop with social media, email and search. You will see the sales results of your work.',
 'Plan and post on social media\nWrite product descriptions and emails\nTrack visits and sales in analytics tools\nWork with the product team on campaigns',
 'Good writing in English and Spanish\nBasic knowledge of SEO and social media\nComfortable with numbers\nCreative and organised',
 'SEO,Social media,Copywriting,Analytics', 0, DATE_ADD(CURDATE(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 5, 'Clinic Operations Coordinator', 'Denver, CO', 'On-site', 'Full-time', 'Mid', 49000, 67000,
 'Keep one of our clinics running smoothly, from appointments to supplies.',
 'Manage appointment schedules\nCheck patient records are complete and private\nOrder and track clinic supplies\nSupport doctors and nurses during busy hours',
 '2 years in a clinic, hospital or office role\nGood with people and phone calls\nCareful with sensitive information\nComfortable using MS Office',
 'Scheduling,Patient records,Communication,MS Office', 0, DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY)),
(2, 5, 'Patient Care Assistant', 'Denver, CO', 'On-site', 'Part-time', 'Entry', 25000, 34000,
 'Welcome patients, help with forms and guide them through their visit.',
 'Greet patients and answer questions\nHelp with intake forms\nKeep the waiting area tidy\nSupport the front desk team',
 'Friendly and patient\nGood spoken English and Spanish\nWilling to work weekends',
 'Front desk,Patient intake,Communication', 0, DATE_ADD(CURDATE(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY)),
(4, 6, 'English Tutor (Online)', 'Remote', 'Remote', 'Part-time', 'Entry', 21000, 42000,
 'Teach small online classes for school students and give clear feedback to parents.',
 'Run live classes on Zoom\nMark placement tests and write short reports\nTalk with parents about progress\nPrepare simple lesson notes',
 'Strong English and a friendly voice\nExperience teaching or tutoring is a plus\nStable internet and a quiet space',
 'Teaching,Assessment,Zoom,Communication', 0, DATE_ADD(CURDATE(), INTERVAL 22 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 6, 'Curriculum Content Writer', 'Raleigh, NC', 'Hybrid', 'Contract', 'Mid', 49000, 70000,
 'Write and check lesson materials for English and maths classes.',
 'Write worksheets and lesson notes\nProofread content from other writers\nResearch topics and check facts\nUpdate materials after teacher feedback',
 'Excellent English writing\nEye for detail\nExperience in education is a plus',
 'Writing,Proofreading,Research', 0, DATE_ADD(CURDATE(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY)),
(6, 7, 'Customer Support Specialist', 'Austin, TX', 'Hybrid', 'Full-time', 'Entry', 39000, 53000,
 'Help small business owners solve payment problems by chat, email and phone.',
 'Answer customer questions quickly and kindly\nLog issues in our ticket system\nShare common problems with the product team\nKeep customer details private',
 'Clear spoken and written English\nFast and accurate typing\nPatient with upset customers',
 'Customer service,Typing,Ticketing,Communication', 0, DATE_ADD(CURDATE(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 7, 'Bilingual Support Agent (Bengali and English)', 'Remote', 'Remote', 'Full-time', 'Entry', 42000, 59000,
 'Support Bengali speaking sellers and buyers by phone and chat.',
 'Speak with customers in Bengali and English\nHelp track and fix delivery problems\nRecord each case clearly\nStay calm on busy days',
 'Fluent in Bengali and English\nGood listening and typing skills\nComfortable working from home',
 'Interpretation,Communication,Data entry', 1, DATE_ADD(CURDATE(), INTERVAL 19 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 8, 'Warehouse Coordinator', 'Dallas, TX', 'On-site', 'Full-time', 'Mid', 45000, 63000,
 'Coordinate daily parcel flow in our Dallas warehouse.',
 'Plan daily pick and pack work\nTrack stock with our inventory system\nLead a team of 10 staff\nReport delays and fix problems',
 '2 years in a warehouse or logistics role\nComfortable with spreadsheets\nGood at leading a team',
 'Inventory,Scheduling,Leadership,Excel', 0, DATE_ADD(CURDATE(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
(9, 4, 'Accounts Executive', 'Austin, TX', 'On-site', 'Full-time', 'Mid', 53000, 73000,
 'Handle bookkeeping and monthly reports for a group of client companies.',
 'Record daily transactions\nPrepare monthly reports\nHelp with payroll and tax filing\nTalk with clients about missing documents',
 'Diploma or degree in Accounting\n2 years of experience\nKnow accounting software and Excel',
 'Bookkeeping,Excel,Payroll,Tax', 0, DATE_ADD(CURDATE(), INTERVAL 17 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
(9, 8, 'Data Entry and Proofreading Associate', 'Remote', 'Remote', 'Part-time', 'Entry', 25000, 36000,
 'Enter client documents into our system and check them for mistakes.',
 'Type information from scanned documents\nCheck work for spelling and number errors\nKeep records private and organised\nMeet daily targets',
 'Fast and accurate typing\nGood English\nUses Google Sheets and MS Office well',
 'Data entry,Proofreading,Google Workspace,Typing', 0, DATE_ADD(CURDATE(), INTERVAL 24 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(8, 9, 'Hotel Front Office Executive', 'Miami, FL', 'On-site', 'Full-time', 'Entry', 34000, 46000,
 'Welcome guests, manage check in and check out, and answer booking questions.',
 'Check guests in and out\nAnswer phone and email bookings\nHandle payments and receipts\nWork with housekeeping on room status',
 'Friendly and well presented\nGood English and Spanish\nWilling to work shifts',
 'Front desk,Bookings,Communication,Payments', 0, DATE_ADD(CURDATE(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
(10, 9, 'Store Supervisor', 'Portland, OR', 'On-site', 'Full-time', 'Mid', 42000, 59000,
 'Lead the team at one of our Portland stores and keep shelves, sales and service on track.',
 'Schedule store staff\nCheck stock and sales each day\nHelp customers with questions\nTrain new team members',
 '2 years in retail\nGood at leading people\nOrganised and calm',
 'Retail,Leadership,Stock control,Customer service', 0, DATE_ADD(CURDATE(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY));

INSERT INTO users (name, email, password_hash, role, company_id) VALUES
('Demo Job Seeker', 'demo@jobhunt.test', '$2y$10$hIyV.1aO4goJm5vvmdwygO1lKItZFqyMyjfdzmX9lg6PkE15ulvhW', 'seeker', NULL),
('Northwind Digital HR', 'hr@northwind.test', '$2y$10$.9lWuupCh7E2/Ql/C2xLJ.I52BkUuItrZDkB89vzYRSMstf/4D.kG', 'employer', (SELECT id FROM companies WHERE slug = 'northwind-digital'));
