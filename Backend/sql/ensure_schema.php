<?php
// Best-effort schema creation / migration.
// This is meant for local XAMPP development so the app can start even if the DB is partially created.

function ensureSchema(PDO $pdo) {
    $ensureColumns = function (string $table, array $wanted) use ($pdo) {
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            $existing = array_map(fn($c) => $c['Field'], $cols);

            foreach ($wanted as $name => $definition) {
                if (!in_array($name, $existing, true)) {
                    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$name}` {$definition}");
                }
            }
        } catch (Throwable $e) {
            // Table doesn't exist yet (or no permissions). We'll rely on CREATE TABLE IF NOT EXISTS.
        }
    };

    // Ensure base tables exist.
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
      id CHAR(24) NOT NULL,
      name VARCHAR(120) NOT NULL,
      email VARCHAR(190) NOT NULL,
      password_hash VARCHAR(255) NOT NULL,
      role VARCHAR(20) NOT NULL,
      approved TINYINT(1) NOT NULL DEFAULT 0,
      blocked TINYINT(1) NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY uq_users_email (email)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS student_profiles (
      user_id CHAR(24) NOT NULL,
      university VARCHAR(255) NULL,
      degree VARCHAR(255) NULL,
      graduation_year INT NULL,
      phone VARCHAR(50) NULL,
      github_url VARCHAR(500) NULL,
      linkedin_url VARCHAR(500) NULL,
      avatar_url VARCHAR(500) NULL,
      resume_url VARCHAR(500) NULL,
      skills_json JSON NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY (user_id)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS recruiter_profiles (
      user_id CHAR(24) NOT NULL,
      company VARCHAR(255) NULL,
      company_description TEXT NULL,
      website VARCHAR(500) NULL,
      logo_url VARCHAR(500) NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY (user_id)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS jobs (
      id CHAR(24) NOT NULL,
      recruiter_id CHAR(24) NOT NULL,
      title VARCHAR(255) NOT NULL,
      company VARCHAR(255) NOT NULL,
      location VARCHAR(255) NOT NULL,
      type VARCHAR(50) NOT NULL,
      category VARCHAR(100) NOT NULL,
      salary_min INT NULL,
      salary_max INT NULL,
      description TEXT NOT NULL,
      openings INT NOT NULL DEFAULT 1,
      application_start DATE NULL,
      application_end DATE NULL,
      contact_email VARCHAR(190) NULL,
      contact_website VARCHAR(500) NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'active',
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS job_requirements (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      job_id CHAR(24) NOT NULL,
      requirement VARCHAR(255) NOT NULL,
      PRIMARY KEY (id),
      KEY idx_job_req_job (job_id)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS applications (
      id CHAR(24) NOT NULL,
      job_id CHAR(24) NOT NULL,
      student_id CHAR(24) NOT NULL,
      cover_letter TEXT NOT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'pending',
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY uq_app_job_student (job_id, student_id),
      KEY idx_app_job (job_id),
      KEY idx_app_student (student_id)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
      id CHAR(24) NOT NULL,
      user_id CHAR(24) NOT NULL,
      type VARCHAR(50) NOT NULL,
      message VARCHAR(500) NOT NULL,
      is_read TINYINT(1) NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL,
      PRIMARY KEY (id),
      KEY idx_notif_user (user_id),
      KEY idx_notif_unread (user_id, is_read)
    ) ENGINE=InnoDB");

    // Ensure columns exist on older schemas.
    $ensureColumns('users', [
        'approved' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'blocked' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ]);

    $ensureColumns('student_profiles', [
        'university' => 'VARCHAR(255) NULL',
        'degree' => 'VARCHAR(255) NULL',
        'graduation_year' => 'INT NULL',
        'phone' => 'VARCHAR(50) NULL',
        'github_url' => 'VARCHAR(500) NULL',
        'linkedin_url' => 'VARCHAR(500) NULL',
        'avatar_url' => 'VARCHAR(500) NULL',
        'resume_url' => 'VARCHAR(500) NULL',
        'skills_json' => 'JSON NULL',
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ]);

    $ensureColumns('recruiter_profiles', [
        'company' => 'VARCHAR(255) NULL',
        'company_description' => 'TEXT NULL',
        'website' => 'VARCHAR(500) NULL',
        'logo_url' => 'VARCHAR(500) NULL',
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ]);

    $ensureColumns('jobs', [
        'salary_min' => 'INT NULL',
        'salary_max' => 'INT NULL',
        'openings' => 'INT NOT NULL DEFAULT 1',
        'application_start' => 'DATE NULL',
        'application_end' => 'DATE NULL',
        'contact_email' => 'VARCHAR(190) NULL',
        'contact_website' => 'VARCHAR(500) NULL',
        'status' => 'VARCHAR(20) NOT NULL DEFAULT \'active\'',
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ]);

    $ensureColumns('notifications', [
        'is_read' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'created_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ]);
}
