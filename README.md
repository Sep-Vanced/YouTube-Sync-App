# YouTube Channel Sync App
Simple PHP web app that uses Google OAuth 2.0 for login and the YouTube Data API v3 to save channels and sync up to 100 videos per channel.
## Project Structure
`	ext
/config
  config.php
  config.sample.php
  config.local.example.php
  db.php
/auth
  login.php
  callback.php
  logout.php
/api
  youtube.php
/pages
  dashboard.php
  channel.php
/actions
  sync_channel.php
/database
  schema.sql
/public
  index.php
  /assets
    styles.css
    script.js
`
## Requirements
- PHP 8.1 or newer
- MySQL 8+ or MariaDB with InnoDB support
- XAMPP, Laragon, or another local PHP/MySQL stack
- A Google OAuth 2.0 Client ID and Secret
- A YouTube Data API v3 key
- PHP pdo_mysql and curl extensions enabled
## Setup
1. Place the project in your web root, for example:
`	ext
C:\xampp\htdocs\technical-exam
`
2. Import the database schema into MySQL:
`sql
SOURCE database/schema.sql;
`
3. Copy config/config.local.example.php to config/config.local.php.
4. Open config/config.local.php and update:
- pp_url
- google.client_id
- google.client_secret
- google.redirect_uri
- youtube.api_key
5. If your database settings are different, update config/config.php.
6. Start Apache and MySQL in XAMPP.
7. In Google Cloud Console:
- Enable **YouTube Data API v3**
- Create OAuth credentials for a **Web Application**
- Add this redirect URI:
`	ext
http://localhost/technical-exam/auth/callback.php
`
8. Open the app:
`	ext
http://localhost/technical-exam/
`
## Configuration Templates
This repository includes safe configuration templates:
- config/config.php
- config/config.sample.php
- config/config.local.example.php
Real secrets should stay in config/config.local.php, which is ignored by git.
## How It Works
1. The user opens the login page and signs in with Google OAuth 2.0.
2. After a successful callback, the app stores or updates the user in the users table.
3. Authenticated users can add a YouTube Channel ID on the dashboard.
4. The app validates the Channel ID and fetches channel details from the channels endpoint using snippet,contentDetails.
5. It reads the uploads playlist ID from contentDetails.relatedPlaylists.uploads.
6. It fetches up to 100 uploaded videos from the playlistItems endpoint using 
extPageToken.
7. Channel data is stored in channels, and videos are stored in ideos.
8. Duplicate channels and videos are prevented with unique indexes and prepared statements.
9. The channel page shows only videos for the selected channel, 20 per page.
## Notes
- This project is intentionally framework-free and beginner-friendly.
- All database queries use prepared statements through PDO.
- Output is escaped with htmlspecialchars() to reduce XSS risk.
- API keys and OAuth secrets stay server-side and should not be committed to a public repository.
- Saved channels are shared globally in this simple version because the required schema does not include a user-to-channel pivot table.