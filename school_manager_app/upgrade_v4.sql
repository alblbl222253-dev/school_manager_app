-- ============================================================
-- ترقية v4 لنظام مدار من قاعدة v2/v3 الحالية.
-- يفضّل أخذ Backup من phpMyAdmin أولًا.
-- متوافق مع MySQL 8+ / MariaDB الحديثة التي تدعم ADD COLUMN IF NOT EXISTS.
-- ============================================================
USE school_manager;

-- إصلاح بنية جدول المستخدمين عند الترقية من v2/v3.
-- database.sql ينشئ هذه الحقول في التثبيت الجديد، أما قاعدة البيانات القديمة فتحتاج ALTER صريحًا.
ALTER TABLE users
  MODIFY COLUMN role ENUM('admin','manager','accountant','teacher','staff') NOT NULL DEFAULT 'staff',
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL AFTER is_active;


ALTER TABLE classes
  ADD COLUMN IF NOT EXISTS stage VARCHAR(80) NOT NULL DEFAULT 'المرحلة الابتدائية',
  ADD COLUMN IF NOT EXISTS grade_level VARCHAR(80) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS section VARCHAR(20) NOT NULL DEFAULT 'أ',
  ADD COLUMN IF NOT EXISTS academic_year VARCHAR(40) NOT NULL DEFAULT '2026 - 2027',
  ADD COLUMN IF NOT EXISTS capacity SMALLINT UNSIGNED NOT NULL DEFAULT 30;
UPDATE classes SET stage=COALESCE(NULLIF(stage,''),grade), grade_level=COALESCE(NULLIF(grade_level,''),grade);

ALTER TABLE subjects
  ADD COLUMN IF NOT EXISTS stage VARCHAR(80) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS weekly_periods TINYINT UNSIGNED NOT NULL DEFAULT 2;

ALTER TABLE teachers
  ADD COLUMN IF NOT EXISTS employee_code VARCHAR(40) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS hire_date DATE DEFAULT NULL;

ALTER TABLE students
  ADD COLUMN IF NOT EXISTS national_id VARCHAR(40) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS guardian_relation VARCHAR(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS admission_date DATE DEFAULT NULL;

ALTER TABLE payments
  ADD COLUMN IF NOT EXISTS receipt_no VARCHAR(50) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ip_address VARCHAR(45) NOT NULL,
 username VARCHAR(60) DEFAULT NULL,
 attempted_at DATETIME NOT NULL,
 INDEX idx_login_attempts_ip_time(ip_address,attempted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_subjects (
 teacher_id INT UNSIGNED NOT NULL,
 subject_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(teacher_id,subject_id),
 CONSTRAINT fk_ts_teacher_v4 FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
 CONSTRAINT fk_ts_subject_v4 FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS class_subjects (
 class_id INT UNSIGNED NOT NULL,
 subject_id INT UNSIGNED NOT NULL,
 teacher_id INT UNSIGNED DEFAULT NULL,
 PRIMARY KEY(class_id,subject_id),
 CONSTRAINT fk_cs_class_v4 FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE CASCADE,
 CONSTRAINT fk_cs_subject_v4 FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
 CONSTRAINT fk_cs_teacher_v4 FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO permissions(permission_key,label,description,module_name,sort_order)
VALUES ('academic.view','عرض الهيكل الأكاديمي','المراحل والصفوف والشعب والمواد','الهيكل الأكاديمي',15)
ON DUPLICATE KEY UPDATE label=VALUES(label);

INSERT IGNORE INTO role_permissions(role_key,permission_id)
SELECT 'staff',id FROM permissions WHERE permission_key='academic.view';
INSERT IGNORE INTO role_permissions(role_key,permission_id)
SELECT 'teacher',id FROM permissions WHERE permission_key='academic.view';
INSERT IGNORE INTO role_permissions(role_key,permission_id)
SELECT 'accountant',id FROM permissions WHERE permission_key='academic.view';

INSERT IGNORE INTO teacher_subjects(teacher_id,subject_id)
SELECT t.id,s.id FROM teachers t JOIN subjects s ON s.name=t.subject;

INSERT IGNORE INTO class_subjects(class_id,subject_id,teacher_id)
SELECT c.id,s.id,(SELECT MIN(ts.teacher_id) FROM teacher_subjects ts WHERE ts.subject_id=s.id)
FROM classes c CROSS JOIN subjects s;

DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 30 DAY);
