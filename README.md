# Football Management System

This project is structured for deployment with `index.php` at the root.

## Role pages

- Public: browse players, teams, coaches
- Player: view personal and top-player stats
- Coach: assign jersey numbers and assign players to teams
- Admin: add/update/delete players, coaches, and teams

## Expanded role responsibilities

- Admin: manage users, competitions (leagues/tournaments), approvals, matches/results, and reports
- Coach: create/manage own team, assign players and jerseys, set lineup/formation, update availability, view team stats
- Player: view own stats, team details, update profile, and check match schedules
- Fan/Public user: view matches/teams/players/standings and follow favorite teams

## Folder structure

```
.
  index.php
  assets/
    css/
    js/
    img/
  auth/
    login.php
    register.php
    logout.php
  app/
    .htaccess
    config/
      db.php
      bootstrap.php
    lib/
      auth.php
      helpers.php
    views/
      partials/
        header.php
        footer.php
  scripts/
    schema.sql
```

## Deploy to mi-linux

1. Upload this project folder into `public_html`.
2. Create `.env` from `.env.example`.
3. Set `APP_URL` in `.env` to your deployment URL.
4. Run `scripts/schema.sql` in MySQL.
5. Update DB credentials in `.env`.

## Notes

- `app/.htaccess` denies direct browser access to app files on Apache.
- User roles: `public`, `player`, `coach`, `admin`.
