-- Hotfix v4.1: إصلاح خطأ Unknown column 'is_active' عند تسجيل الدخول
-- شغّل هذا الملف مرة واحدة على قاعدة school_manager من phpMyAdmin.
USE school_manager;

ALTER TABLE users
  MODIFY COLUMN role ENUM('admin','manager','accountant','teacher','staff') NOT NULL DEFAULT 'staff',
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL AFTER is_active;

UPDATE users SET is_active = 1 WHERE is_active IS NULL;
