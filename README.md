# YouTube Channel Sync App

Framework-free PHP technical exam project for signing in, saving YouTube channels, and syncing uploaded videos.

## Entry Point

Open the app at:

`http://localhost/technical-exam/`

The public entry file is:

`public/index.php`

## Run It Quickly

1. Clone or copy the repository into your local web root, for example:
   `C:\xampp\htdocs\technical-exam`
2. Create the database and tables by importing:
   `database/schema.sql`
3. Start Apache and MySQL.
4. Open:
   `http://localhost/technical-exam/`
5. Click `Enter Demo Workspace`.

No Google OAuth setup or YouTube API setup is required for evaluation. The repository already includes a tracked `config/config.local.php` that enables local demo mode with ready-to-use database settings.

## Important Notes

- This repository is evaluator-ready in local demo mode.
- `config/config.local.php` is included and already configured for local use.
- Default local database settings assume XAMPP:
  - host: `127.0.0.1`
  - port: `3306`
  - database: `youtube_sync_app`
  - user: `root`
  - password: empty string
- Demo mode replaces external Google OAuth and YouTube API calls with bundled local data so the application can be tested immediately after cloning.
- To test the sync form manually in demo mode, use either of these sample Channel IDs:
  - `UCDEMOCHANNEL000000000001`
  - `UCDEMOCHANNEL000000000002`

## How The System Works

1. The user opens the landing page and enters the local demo workspace.
2. The app creates or updates a local demo user in the `users` table and signs that user into the session.
3. Demo mode seeds sample channels and videos into the database so the evaluator can view working dashboard and channel pages immediately.
4. When the evaluator submits a sample Channel ID, the sync flow runs through the same save logic used by the real app:
   - validate the Channel ID
   - load channel details
   - load uploaded videos
   - save or update records in `channels`
   - save or update records in `videos`
5. The dashboard lists saved channels and total synced videos.
6. The channel page shows the selected channel with paginated uploaded videos.

## Project Structure

- `config/` application config and PDO bootstrap
- `auth/` login, callback, logout, and demo login flow
- `api/` YouTube integration and demo data layer
- `actions/` form actions such as channel sync
- `pages/` dashboard and channel views
- `database/` schema file
- `public/` entry page, assets, and public routes

## Real-Service Mode

The original Google OAuth and YouTube API flow is still supported by the codebase. To use real external services instead of demo mode, disable demo mode in `config/config.local.php` and provide real Google OAuth and YouTube API credentials.
