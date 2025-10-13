# Comic Newsletter

<img width="1899" height="925" alt="image" src="https://github.com/user-attachments/assets/60f84582-f9bd-4325-a640-7f02940b7295" />

**Comic Newsletter** is a web application that delivers daily comics directly to your inbox. Users can subscribe using their email addresses, and the system ensures secure OTP-based verification. Admins can manage comic content, and newsletters are sent automatically at scheduled intervals.

## 🚀 Tech Stack

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge\&logo=php\&logoColor=white) ![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-06B6D4?style=for-the-badge\&logo=tailwindcss\&logoColor=white) ![Cron Job](https://img.shields.io/badge/Cron%20Job-000000?style=for-the-badge\&logo=linux\&logoColor=white)

## 🔧 Features

* **Email Subscription:** Users can subscribe using their email addresses.
* **OTP Verification:** Ensures secure subscription process.
* **Admin Panel:** Manage comic content and newsletter settings.
* **Automated Newsletters:** Comics delivered automatically at scheduled intervals.
* **Responsive Design:** Built with Tailwind CSS for a modern and responsive interface.

## 📁 Project Structure

* `admin.php`: Admin panel for managing comics and settings.
* `config.php`: Configuration file for database and email settings.
* `functions.php`: Contains helper functions for various operations.
* `index.php`: Main page displaying the comic content.
* `login.php`: User login page.
* `signup.php`: User registration page.
* `database.sql`: SQL file for setting up the database schema.

## 📬 Live Demo

Experience the application live at: [dailycomics.infinityfreeapp.com](https://dailycomics.infinityfreeapp.com)

## 🛠️ Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/SimpleCyber/Comic-Newsletter.git
   ```

2. Set up the database using `database.sql`.

3. Configure email settings in `config.php`.

4. Upload the files to your server.

5. Set up a cron job to run the newsletter script at desired intervals.

## Contributing

Feel free to fork the repository, submit issues, and create pull requests. Contributions are welcome!

