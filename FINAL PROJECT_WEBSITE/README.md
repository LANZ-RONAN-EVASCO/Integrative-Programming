# Barangay Landayan Information and Public Announcement System

Plain PHP 8.1+, MySQL (PDO), HTML/CSS/JS. No frameworks. Runs on XAMPP.

## 1. First-time setup (XAMPP on Windows)

1. Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Copy the `barangay-landayan` folder into `C:\xampp\htdocs\`.
3. Open http://localhost/phpmyadmin, click **Import**, choose `database.sql`, then **Import**.
   One file creates everything (announcements, events, reports, login tracking, sample content).
4. Open http://localhost/barangay-landayan/setup.php. Copy the temporary password it shows, then **delete `setup.php`**.
5. Log in at http://localhost/barangay-landayan/admin/login.php (username `admin`) and choose a new password.

**Already running an earlier version?** Copy the new files over your folder (keep your `uploads` folder), then in phpMyAdmin select the `barangay_landayan` database, click **Import** and run `database-update.sql`. It only adds what is missing and is safe to run twice. You do not need `setup.php` again.

## 2. Preview the website inside VS Code

PHP pages must be run by a server, so the "Live Preview" extension will not work (it shows static HTML only). Use the **Simple Browser** that is built into VS Code.

**Option A: XAMPP (recommended)**

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. In VS Code choose **File > Open Folder** and select `C:\xampp\htdocs\barangay-landayan`. Every file you save is then live.
3. Press `Ctrl+Shift+P`, type **Simple Browser: Show**, press Enter.
4. Paste `http://localhost/barangay-landayan/` and press Enter. The site opens in a VS Code tab. Drag the tab to the right side to see code and site together.
5. After you save a file, click the reload button in the Simple Browser toolbar.

**Option B: PHP's built-in server (Apache not needed, MySQL still from XAMPP)**

1. Start only **MySQL** in XAMPP.
2. In VS Code open the project folder, then **Terminal > Run Task > Start PHP server (port 8080)**.
   (Or run `C:\xampp\php\php.exe -S localhost:8080` in the VS Code terminal.)
3. `Ctrl+Shift+P`, **Simple Browser: Show**, then `http://localhost:8080/`.
4. Stop it with `Ctrl+C` in the task's terminal.

Pressing **F5** also works: pick "Open site in Chrome" and it opens the site in Chrome (start Apache or the PHP server first).

Use Chrome or Edge, not the Simple Browser, to test the **camera** on the Report page, because the camera needs a full browser.

**Test on your phone:** connect the phone to the same Wi-Fi and open `http://YOUR-PC-IP/barangay-landayan/` (find the IP with `ipconfig`). Private addresses are treated as development automatically.

## 3. What the system includes

**Public website:** Home (urgent banner, latest announcements, upcoming events) | About (mission, vision, officials) | Announcements (search, category filter, sort, details with image and PDF) | Events (upcoming and past, details with poster) | Services (requirements) | Report to the Barangay (photos, tracking code) | Contact form | mobile menu.

**Admin panel** (`/admin/login.php`):

| Module | Administrator | Editor |
|---|---|---|
| Dashboard with live counts (every card is a link) | yes | yes |
| Announcements: create, edit, publish, archive, schedule, preview drafts | yes | yes |
| Events: create, edit | yes | yes |
| Services: create, edit, hide or show | yes | yes |
| Resident reports: view, change status, add notes | yes | yes |
| Messages from the Contact page: read, mark read, reply by email | yes | yes |
| Delete announcements, events, services, reports, messages | yes | no |
| Officials, Categories, Barangay information | yes | no |
| Users (add, edit, reset password, deactivate) and Activity log | yes | no |
| Change own password | yes | yes |

Editors only see the menu items they can use, and the server checks the permission on every page.

**Security:** CSRF tokens on every form, prepared statements, escaped output, hashed passwords, forced password change for new and reset accounts, login lockout after repeated wrong passwords (works behind a Cloudflare tunnel too), session timeout, role and permission checks on the server (re-read from the database on every request), upload type checks with random file names, and `.htaccess` blocking `config/`, `includes/` and PHP files in `uploads/`.

## 4. Quick test (about 10 minutes)

1. Open the public site and click every menu link.
2. **Contact**: send a message. In the admin panel open **Messages** (badge in the menu) and read it.
3. **Report**: send a report with a photo, then open **Reports** and change its status.
4. **Announcements**: add one with an image and publish it. Check Home and Announcements. Then add one with a *future* date: it must NOT appear publicly until then (use Preview).
5. **Events**: add one with a future date and one with a past date. Check the Upcoming and Past tabs and the Home page.
6. **Barangay information**: change the office hours and confirm the footer and Contact page change.
7. **Officials** and **Services**: add one of each (with a photo / requirements) and check About and Services.
8. **Users > Add user** (role Editor). Log in as that user in a private window: no Officials, Users, Categories or Activity log in the menu, and no Delete buttons.
9. **Activity log** lists everything you just did.

## 5. Settings

- Database and environment are read from `config/database.php`. XAMPP defaults work as they are.
- **Shared hosting** (for example InfinityFree) cannot set environment variables. Copy `config/config.local.php.example` to `config/config.local.php`, fill in the database details, and set `'APP_ENV' => 'production'`. That file is never committed or served.
- Time zone defaults to `Asia/Manila` for both PHP and MySQL (`APP_TIMEZONE`).
- **Cloudflare quick tunnel** for demos: `cloudflared tunnel --url http://localhost`. The site detects `trycloudflare.com` as a demo address automatically.

## 6. Troubleshooting

- "MySQL is not running" or "database does not exist": start MySQL in XAMPP and import `database.sql`.
- Styles missing or links go to the wrong place: the folder must be directly inside `htdocs`, or set `APP_BASE_URL`.
- Image upload fails: in `C:\xampp\php\php.ini` make sure `extension=fileinfo` is enabled, then restart Apache.
- Locked out after wrong passwords: wait 15 minutes, or ask another administrator to reset the password.
- Forgot the only administrator password: in phpMyAdmin delete that row in `users`, then run `setup.php` again (restore it from the zip).

## 7. Before going online

Delete `setup.php`, use a strong MySQL password (not the empty XAMPP `root`), set `APP_ENV` to `production`, turn on HTTPS, and keep `uploads/` writable.
