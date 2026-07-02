# 📚 Personal Resource Hub

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![Version](https://img.shields.io/badge/Version-v1.0-blue)
![License](https://img.shields.io/badge/License-MIT-green)

> A personal web application to organize digital learning resources built with **PHP Native**, **MySQL**, and **Vanilla JavaScript**.

🔗 **Live Demo:** https://personalhub-feri.freepage.cc/login.php

---

# 🎯 About

Personal Resource Hub is a web application that helps users organize digital resources from various platforms such as:

- 📺 YouTube
- 🎵 Spotify
- 📄 Articles
- 💻 GitHub
- 📷 Instagram
- 📚 Documentation

This project was built from scratch as my first complete PHP Native portfolio project, from database design to deployment.

---

# ✨ Features

## 🔐 Authentication

- Register & Login
- Email Verification (SMTP)
- Forgot Password
- Reset Password
- User Whitelist
- Pending Approval Page

## 📚 Resource Management

- CRUD Resource
- Favorite / Pin Resource
- Rating (1–10)
- Many-to-Many Tagging
- Search Resource
- Filter by Status
- Filter by Type
- Filter by Tag

## 📊 Dashboard

- Total Resources
- Reading Progress
- Favorite Resources
- Latest Resources
- User Activity Log

## 🌙 User Experience

- Dark / Light Mode
- Responsive Design
- Sidebar Collapse
- Tooltip Information

## 🛡 Security

- CSRF Protection
- Prepared Statements
- Password Hashing (Bcrypt)
- URL Validation
- Protected Configuration Files (.htaccess)

---

# 🛠 Tech Stack

| Layer | Technology |
|--------|------------|
| Backend | PHP 8 Native |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3, JavaScript |
| Mail | PHPMailer |
| Hosting | InfinityFree |
| Version Control | Git & GitHub |

---

# 🗄 Database Structure

| Table | Description |
|--------|-------------|
| users | User Account |
| resources | Resource Collection |
| tags | Tag List |
| resource_tag | Many-to-Many Relationship |
| user_logs | User Activity History |

---

# 🚀 Installation

## Clone Repository

```bash
git clone https://github.com/feriferdian/personal-resource-hub.git
```

## Import Database

- Create database
- Import `resource_hub_fixed.sql`

## Configure Environment

Rename:

```
env.example.php
```

to

```
env.php
```

Edit:

```php
return [
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_NAME' => 'resource_hub',
];
```

Run:

```
http://localhost/personal-resource-hub/login.php
```

---

# 📷 Screenshots

| Dashboard | Resource |
|-----------|----------|
| docs/screenshots/dashboard.png | docs/screenshots/detail.png |

| Settings | Activity |
|----------|----------|
| docs/screenshots/settings.png | docs/screenshots/activity.png |

---

# 📅 Timeline

| Item | Value |
|------|-------|
| Started | 13 June 2026 |
| Finished | 2 July 2026 |
| Duration | ~3 Weeks |

---

# 📖 What I Learned

- PHP Native
- MySQL Relationship
- CRUD Development
- Authentication System
- Email Verification
- CSRF Protection
- Password Hashing
- Responsive UI
- Deployment
- Debugging

---

# 👨‍💻 Developer

**Feri**

- GitHub
- LinkedIn
- Instagram

---

# 📄 License

MIT License

---

# 🙏 Thank You

Thank you for visiting this project.

If you have suggestions or improvements, feel free to open an Issue or Pull Request.