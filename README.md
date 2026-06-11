Sweet Cake Shop Assignment

[View the full Advanced Web Assignment Document on Google Docs](https://docs.google.com/document/d/1Vjwzuczp8DW7YPc0OVp3XM24nr1lMRDE/edit?usp=sharing&ouid=113571141949667734077&rtpof=true&sd=true)

[View the full logbook document on google docs](https://docs.google.com/document/d/15rkFBhV7H0BP9oo9uHbZ9RynIeh9fWba/edit?usp=sharing&ouid=113571141949667734077&rtpof=true&sd=true)

STUDENT DETAILS.
Student Name: Gitau Annie Wangeci
Admission Number: BSCCS/2024/55251
Course/Class: Computer Science
Unit Code: BIT3208
Unit Name: Advanced Web Design and Development
Lecturer Name: Micheal Nyoro
Semester/Academic Year: Sem 2, Year 3

GitHub Link: https://github.com/ANNIE-455/Sweet-Cake-Shop

 ```text
BIT3208_Project/
├── Week1/
│   ├── XAMPP Installation Screenshots/
│   ├── Apache and MySQL Testing Files/
│   ├── test.php
│   └── Week1db.sql
├── Week2/
│   ├── index.html
│   ├── style.css
│   ├── auth.php
│   ├── Images/
│   └── Week2db.sql
├── Week3/
│   ├── index.js
│   ├── Password Strength Checker Implementation/
│   ├── Form Validation Scripts/
│   └── Week3db.sql
├── Week4/
│   ├── PHP Authentication/
│   ├── User Registration Module/
│   ├── User Login Module/
│   ├── Password Hashing Implementation/
│   └── Week4db.sql
├── Week5/
│   ├── Create Operations/
│   ├── Read Operations/
│   ├── Update Operations/
│   ├── Delete Operations/
│   ├── CRUD Screenshots/
│   └── Week5db.sql
└── Documentation/
    ├── README.md
    ├── System Design.pdf
    └── Screenshots/
        └── Database Schema.pdf


 Sweet Cake Shop Management System

Project Description

Sweet Cake Shop is a web-based cake ordering and customer management system developed using HTML, CSS, JavaScript, PHP, and MySQL. The application provides customers with an interactive platform for browsing cake products, viewing reviews, reading blogs, and creating user accounts through a secure authentication system.

The system also incorporates password strength checking, form validation, session-based authentication, and CRUD operations for managing customer records and products.

 Objectives

- Develop a responsive cake shop website.
- Implement user registration and login functionality.
- Validate user inputs using JavaScript.
- Secure passwords using hashing techniques.
- Store user information in MySQL.
-Perform CRUD operations using PHP and MySQL.
-Enhance user experience through responsive design.


Technology Stack

Frontend:
- HTML5
- CSS3
-JavaScript

Backend:
-PHP

Database:
-MySQL

Development Environment:
-XAMPP

Features
User Authentication

 -User Registration
 -User Login
 -Password Hashing
 -Password Verification
 -Session Management

Password Security
-Real-time Password Strength Checker
-Email Validation
-Password Length Validation
-Uppercase Character Detection
-Number Detection
-Special Character Detection


Database Features

-User Registration Storage
-Login Authentication
-CRUD Operations
-Data Retrieval
-Data Updates
-Data Deletion

Project Structure

BIT3208_Project/
│
├── Week1/
├── Week2/
├── Week3/
├── Week4/
├── Week5/
└── Documentation/


Weekly Development Summary

Week 1: Environment Setup

Activities:

 Installed XAMPP
 Configured Apache Server
 Configured MySQL Server
 Tested PHP Execution
*Created Initial Database

Deliverables:
 test.php
 Week1db.sql

 Week 2: Website Layout and Integration

Activities:

Created HTML Structure
Designed User Interface
Added Navigation Menu
Implemented CSS Styling
Integrated PHP Forms

Deliverables:

index.html
style.css
auth.php

Week 3: Form Validation and Password Strength Checker

Activities:

JavaScript Validation
Email Validation
Password Length Validation
Password Strength Meter

Deliverables:

index.js
Validation Screenshots
Week3db.sql


Week 4: PHP Backend Development

Activities:

User Registration
Login Authentication
Password Hashing
Password Verification
Database Connectivity

Deliverables:

auth.php
Week4db.sql

Week 5: CRUD Operations

Activities:

Create Records
Read Records
Update Records
Delete Records

Deliverables:

CRUD Modules
Week5db.sql


Database Structure

Database Name:
cake_db

Table: users

| Field    | Type         | Description     |
| -------- | ------------ | --------------- |
| id       | INT          | Primary Key     |
| username | VARCHAR(100) | User Name       |
| email    | VARCHAR(100) | User Email      |
| password | VARCHAR(255) | Hashed Password |

SQL:

CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(100) NOT NULL,
email VARCHAR(100) NOT NULL,
password VARCHAR(255) NOT NULL
);


Authentication Workflow

1. User enters registration details.
2. Password is hashed using PASSWORD_DEFAULT.
3. User information is stored in MySQL.
4. User logs in.
5. Password is verified using password_verify().
6. Access is granted if credentials are correct.



Session Management

The system can be extended using PHP sessions.

Example:

session_start();

$_SESSION['username'] = $row['username'];


User Registration

Endpoint:
POST /auth.php

Parameters:

username
email
password
signup

Response:

Account created successfully



User Login

Endpoint:
POST /auth.php

Parameters:

username
password
login

Response:

Welcome back user


Installation Guide

Prerequisites

XAMPP
PHP
MySQL
Web Browser
Code Editor (VS Code)


Installation Steps

1. Install XAMPP.
2. Start Apache and MySQL.
3. Copy project folder into:

htdocs/

4. Create database:

cake_db

5. Import SQL file.

6. Open browser and visit:

http://localhost/sweetCake/index.html#home

7. Register an account.

8. Login to access the system.


Future Improvements

-Product Ordering System
-Shopping Cart
-Payment Gateway Integration
-Admin Dashboard
-Order Tracking
-Email Notifications
-Customer Profiles


Course Unit: BIT3208 Web Development Project

