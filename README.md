 #Sweet Cake Shop Assignment

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

PROJECT DETAILS.
Project Title : Sweetcake shop
Selected Technologies
Examples:
-PHP + MySQL
-Java

GitHub Link: https://github.com/ANNIE-455/Sweet-Cake-Shop



TABLE OF CONTENTS 
STUDENT DETAILS.	1
PROJECT DETAILS.	1
GitHub Link: https://github.com/ANNIE-455/Sweet-Cake-Shop	1
Week 1: Local Environment Setup	5
Installation and Testing of Local Development Environment.	5
Student Reflection	6
Week 2:Wireframes and GUI Design	7
Figure 6 Hand drawn wireframe detailing the planned layout and user interface for the SweetCake login page	7
Figure 7 Browser showing the login page with username and password fields to authenticate registered users	7
Student Reflection	8
Week 3: JavaScript and PHP Basics	9
Frontend Interaction and Backend Foundations	9
Week 4 Task Schedule	10
Task 1 – Understanding Server-Side Programming	10
Task 2 – HTML Forms and PHP Integration	11
Task 3 – Simple Authentication System	12
	12
Figure 16 Basic Login Page	12
	13
Task 4 – Backend Folder Organization	14
Task 5 – Introduction to Modern Backend	14
Week 5 Lesson – Advanced Web Design and Development	15
Task 1-Database creation	15
Task 2- Creating tables	15
Task 3- CRAD operations	15
	15


Figure 1  Installation of xampp	3
Figure 2  Apache and MysQL running	3
Figure 3  Localhost testpage	4
Figure 4  Hello World Test	4
Figure 5  Database connection test	4
Figure 6  Hand drawn wireframe detailing the planned layout and user interface for the SweetCake login page 	5
Figure 7  Browser showing the login page with username and password fields to authenticate registered users	6
Figure 8  Mobile mockup view	7
Figure 9  Javascript form validation	8




Week 1: Local Environment Setup
Installation and Testing of Local Development Environment.

Figure 1 Installation of xampp

Figure 2 Apache and MysQL running


Figure 3 Localhost testpage

Figure 4 Hello World Test


Figure 5 Database connection test

Student Reflection
I mastered setting up a local full-stack development environment using the XAMPP stack. I successfully managed Apache and MySQL core services. My main technical challenge was resolving an initial port conflict for Apache, which I fixed by modifying the service configurations.
Week 2:Wireframes and GUI Design

Figure 6 Hand drawn wireframe detailing the planned layout and user interface for the SweetCake login page 

Figure 7 Browser showing the login page with username and password fields to authenticate registered users


Figure 8 Mobile mockup view

Student Reflection
This week helped me understand how the frontend and backend work together. JavaScript was used on the frontend to validate user input and provide immediate feedback, improving the user experience before data is sent to the server. PHP handled the backend processing of form data and dynamic user input. 

Week 3: JavaScript and PHP Basics
Frontend Interaction and Backend Foundations

Figure 9 Javascript form validation

Figure 10 php sytax 

Figure 11 Dynamic use input dashboard
Student reflection
The figures demonstrate JavaScript DOM manipulation preventing invalid inputs before reaching the server, reducing unnecessary HTTP requests. PHP was used to securely process the incoming POST request .The mysqli connection bridges the frontend inputs to the MySQL database, establishing the foundational architecture for the stateless registration system.

Week 4 Task Schedule
Task 1 – Understanding Server-Side Programming

Figure 12 Simple processing page

Figure 13 Dynamic welcome page
Task 2 – HTML Forms and PHP Integration

Figure 14 Login form

Figure 15 Registration form
Task 3 – Simple Authentication System

Figure 16 Basic Login Page

Figure 17 Username/password validation

Figure 18session-based welcome page



Task 4 – Backend Folder Organization

Figure 19 Project structure
Task 5 – Introduction to Modern Backend 

Figure 20 php modern alternative
Student reflection
PHP handles the secure transfer of registration data. By bypassing session storage entirely to meet project requirements, the system strictly relies on processing direct payloads.Security was prioritized by using real_escape_string`to protect against basic SQL injection before executing the database insertion.
Week 5 Lesson – Advanced Web Design and Development
Task 1-Database creation

Figure 21 Cake Database
Task 2- Creating tables

Figure 22 Cake table
Task 3- CRAD operations

Figure 23 Add records(INSERT)




Figure 24 Delete records(DELETE)
Student reflection
The MySQL database ensures permanent data persistence. The database schema includes an auto-incrementing primary key and timestamp to track registration order. The final integration immediately fetches and displays real-time registration data via a dynamically generated  table directly after form submission, fully completing the stateless flow.

