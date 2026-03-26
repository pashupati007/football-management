CREATE DATABASE IF NOT EXISTS football_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE football_management;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('public', 'player', 'coach', 'admin') NOT NULL DEFAULT 'public',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS coaches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    experience_years INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_coaches_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    home_ground VARCHAR(150) NULL,
    manager_coach_id INT UNSIGNED NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_teams_name (name),
    KEY idx_teams_manager_coach_id (manager_coach_id),
    KEY idx_teams_approval_status (approval_status),
    CONSTRAINT fk_teams_manager_coach FOREIGN KEY (manager_coach_id)
        REFERENCES coaches(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS players (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    position VARCHAR(80) NOT NULL,
    jersey_number INT UNSIGNED NULL,
    team_id INT UNSIGNED NULL,
    coach_id INT UNSIGNED NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    availability ENUM('available', 'injured', 'suspended') NOT NULL DEFAULT 'available',
    bio VARCHAR(255) NULL,
    profile_image VARCHAR(255) NULL,
    matches_played INT UNSIGNED NOT NULL DEFAULT 0,
    goals INT UNSIGNED NOT NULL DEFAULT 0,
    assists INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_players_email (email),
    UNIQUE KEY uq_players_jersey_number (jersey_number),
    KEY idx_players_team_id (team_id),
    KEY idx_players_coach_id (coach_id),
    KEY idx_players_approval_status (approval_status),
    KEY idx_players_availability (availability),
    CONSTRAINT fk_players_team FOREIGN KEY (team_id)
        REFERENCES teams(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_players_coach FOREIGN KEY (coach_id)
        REFERENCES coaches(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS competitions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('league', 'tournament') NOT NULL,
    season VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_competitions_name_season (name, season)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id INT UNSIGNED NULL,
    home_team_id INT UNSIGNED NOT NULL,
    away_team_id INT UNSIGNED NOT NULL,
    match_date DATETIME NOT NULL,
    venue VARCHAR(150) NULL,
    status ENUM('scheduled', 'completed') NOT NULL DEFAULT 'scheduled',
    home_score INT NULL,
    away_score INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_matches_competition_id (competition_id),
    KEY idx_matches_home_team_id (home_team_id),
    KEY idx_matches_away_team_id (away_team_id),
    KEY idx_matches_status (status),
    CONSTRAINT fk_matches_competition FOREIGN KEY (competition_id)
        REFERENCES competitions(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_matches_home_team FOREIGN KEY (home_team_id)
        REFERENCES teams(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_matches_away_team FOREIGN KEY (away_team_id)
        REFERENCES teams(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lineups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NOT NULL,
    formation VARCHAR(40) NOT NULL,
    notes VARCHAR(255) NULL,
    created_by_coach_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lineups_team_id (team_id),
    KEY idx_lineups_created_by_coach_id (created_by_coach_id),
    CONSTRAINT fk_lineups_team FOREIGN KEY (team_id)
        REFERENCES teams(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_lineups_created_by_coach FOREIGN KEY (created_by_coach_id)
        REFERENCES coaches(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lineup_players (
    lineup_id INT UNSIGNED NOT NULL,
    player_id INT UNSIGNED NOT NULL,
    position_slot VARCHAR(50) NOT NULL,
    PRIMARY KEY (lineup_id, player_id),
    KEY idx_lineup_players_player_id (player_id),
    CONSTRAINT fk_lineup_players_lineup FOREIGN KEY (lineup_id)
        REFERENCES lineups(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_lineup_players_player FOREIGN KEY (player_id)
        REFERENCES players(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favorite_teams (
    user_id INT UNSIGNED NOT NULL,
    team_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, team_id),
    KEY idx_favorite_teams_team_id (team_id),
    CONSTRAINT fk_favorite_teams_user FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_favorite_teams_team FOREIGN KEY (team_id)
        REFERENCES teams(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    published_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_news_is_published (is_published),
    KEY idx_news_published_at (published_at),
    KEY idx_news_created_by (created_by),
    CONSTRAINT fk_news_created_by FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- Backfill schema changes for existing databases where `players` may predate `profile_image`.
SET @has_profile_image := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'players'
      AND COLUMN_NAME = 'profile_image'
);

SET @add_profile_image_sql := IF(
    @has_profile_image = 0,
    'ALTER TABLE players ADD COLUMN profile_image VARCHAR(255) NULL AFTER bio',
    'SELECT ''players.profile_image already exists'''
);

PREPARE add_profile_image_stmt FROM @add_profile_image_sql;
EXECUTE add_profile_image_stmt;
DEALLOCATE PREPARE add_profile_image_stmt;
