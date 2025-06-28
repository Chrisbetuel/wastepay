# 💸 WastePay – Debt Management System

**Developer Guide & Project Overview**

WastePay is a web-based debt management system designed to streamline debt tracking and payment processes. It provides a user-friendly interface for users to register, view their outstanding debts, and make payments seamlessly via ZenoPay integration.

---

## 📘 Project Developer Guide

### 🧱 Architecture Overview

This project follows a **3-tier architecture**:

- **Frontend**:  
  - HTML, CSS, JavaScript (static or template-based)  
  - No logic modifications required.

- **Backend**:  
  - Django + Django REST Framework  
  - Hosted on [Render](https://render.com)

- **Database**:  
  - PostgreSQL (Render PostgreSQL)

---

## ⚠️ Important Rules

- 🚫 **DO NOT** edit or modify the frontend codebase.
- The frontend is tightly coupled with deployed backend APIs.
- 🛠️ Any issues must be reported immediately.

> 📧 **If something breaks — stop immediately and contact the maintainer.**

---

## ✅ What You Should Do

### 1. Running the Frontend

- Open `loginn.php` in your browser via Apache server/XAMPP.
- No need to run Django.
- Make sure your device is connected to the internet to access the backend APIs.

---

### 2. Testing the System

- Register a new user using the frontend.
- Log in and try.
---

## 🔐 Authentication

- JWT-based authentication is used across the platform.
- Ensure users are logged in before accessing any protected route.

---

## 📦 Technologies Used

| Layer      | Technology                            |
|------------|----------------------------------------|
| Frontend   | HTML, CSS, JavaScript                  |
| Backend    | Django, Django REST Framework          |
| Payments   | ZenoPay API (Mobile Money – Tanzania) |
| Database   | PostgreSQL (Render)                    |
| Auth       | JWT Tokens                             |

---

## 🛠 Example API Endpoints

| Feature              | Endpoint                         |
|----------------------|----------------------------------|
| Register             | `POST /api/register/`            |
| Login                | `POST /api/login/`               |
| View Debts           | `GET /api/debts/`                |
| Make Payment         | `POST /api/payments/`            |
| Notifications        | `GET /api/notifications/`        |

---

## 🧑‍💼 Admin Management Panel

To manage all backend data including users, debts, payments, and notifications:

- 🌐 Visit: [\[https://zenopay-g25p.onrender.com\](https://zenopay-g25p.onrender.com)](https://zenopay-g25p.onrender.com/admin/login/?next=/admin/)
- 🔐 **Username**: `admin`  
- 🔑 **Password**: `muhunzii`

---

## 🤝 Maintainer

For feedback, issues, or support:

**Isihaka Muhunzi**  
📧 Email: isihakaabdallah25@gmail.com  
🌍 Based in Tanzania

---

> 📌 This guide is intended for developers and testers only. Follow the steps carefully, and report any issue directly for backend-level assistance.
