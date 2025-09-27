# 🗳️ Voting System (Web2, PHP + MySQL)

A simple **polling & voting system** built with PHP and MySQL.  
Users can register, log in, create polls, vote, and view results.  
Designed as a lightweight project for learning web app development with a focus on backend logic.

---

## 🚀 Features
- User registration & login (session-based authentication).
- Create polls with multiple options.
- Cast one vote per user per poll.
- View live poll results.
- Basic dashboard with poll management.
- API endpoints for poll creation, voting, and results.

---

## 📂 Project Structure
Voting_System_web2/
│── api/ # API endpoints (create, vote, results, etc.)

│── config/ # Database configuration

│── includes/ # Common functions / helpers

│── pages/ # Frontend pages (dashboard, create poll, etc.)

│── test_*.php # Test scripts (remove in production)

│── database.sql # Database schema (to import in MySQL)

│── README.md # Documentation


---

## 🛠️ Setup Instructions

### 1. Prerequisites
- PHP 8.x or newer  
- MySQL 5.7+  
- Apache/Nginx (XAMPP, WAMP, or Docker)  

### 2. Clone the Repo
```bash
git clone https://github.com/rajat-k-singh/Voting_System_web2.git
cd Voting_System_web2
