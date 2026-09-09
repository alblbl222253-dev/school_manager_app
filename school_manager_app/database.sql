-- ============================================================
-- نظام مدار لإدارة المدرسة | قاعدة بيانات احترافية v4
-- متوافقة مع XAMPP / MySQL 8+ / MariaDB الحديثة.
-- جميع الأسماء والبيانات أدناه بيانات تجريبية للتشغيل والاختبار وليست بيانات حقيقية.
-- ============================================================
CREATE DATABASE IF NOT EXISTS school_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_manager;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS school_settings (
 id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
 school_name VARCHAR(160) NOT NULL DEFAULT 'مدرسة مدار النموذجية',
 school_name_en VARCHAR(160) DEFAULT NULL,
 school_phone VARCHAR(40) DEFAULT NULL,
 school_email VARCHAR(150) DEFAULT NULL,
 school_website VARCHAR(180) DEFAULT NULL,
 school_address VARCHAR(255) DEFAULT NULL,
 school_address_en VARCHAR(255) DEFAULT NULL,
 school_logo VARCHAR(255) DEFAULT NULL,
 school_manager VARCHAR(120) DEFAULT NULL,
 academic_year VARCHAR(40) DEFAULT '2026 - 2027',
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(120) NOT NULL,
 username VARCHAR(60) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','manager','accountant','teacher','staff') NOT NULL DEFAULT 'staff',
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 last_login_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_users_role_active(role,is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ip_address VARCHAR(45) NOT NULL,
 username VARCHAR(60) DEFAULT NULL,
 attempted_at DATETIME NOT NULL,
 INDEX idx_login_attempts_ip_time(ip_address,attempted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 permission_key VARCHAR(100) NOT NULL UNIQUE,
 label VARCHAR(150) NOT NULL,
 description VARCHAR(255) DEFAULT NULL,
 module_name VARCHAR(80) NOT NULL,
 sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
 role_key VARCHAR(40) NOT NULL,
 permission_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(role_key,permission_id),
 CONSTRAINT fk_rp_permission FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_permissions (
 user_id INT UNSIGNED NOT NULL,
 permission_id INT UNSIGNED NOT NULL,
 is_allowed TINYINT(1) NOT NULL DEFAULT 1,
 PRIMARY KEY(user_id,permission_id),
 CONSTRAINT fk_up_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_up_permission FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED DEFAULT NULL,
 action_name VARCHAR(120) NOT NULL,
 entity_type VARCHAR(80) NOT NULL,
 entity_id INT UNSIGNED DEFAULT NULL,
 details TEXT DEFAULT NULL,
 ip_address VARCHAR(45) DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_audit_user_created(user_id,created_at),
 INDEX idx_audit_entity(entity_type,entity_id),
 CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS classes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 stage VARCHAR(80) NOT NULL DEFAULT 'المرحلة الابتدائية',
 grade VARCHAR(80) NOT NULL,
 grade_level VARCHAR(80) NOT NULL,
 section VARCHAR(20) NOT NULL DEFAULT 'أ',
 room VARCHAR(40) DEFAULT NULL,
 academic_year VARCHAR(40) DEFAULT '2026 - 2027',
 capacity SMALLINT UNSIGNED NOT NULL DEFAULT 30,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_class_structure(name,academic_year),
 INDEX idx_class_stage_grade(stage,grade_level,section)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subjects (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 code VARCHAR(30) DEFAULT NULL UNIQUE,
 stage VARCHAR(80) DEFAULT NULL,
 description VARCHAR(255) DEFAULT NULL,
 weekly_periods TINYINT UNSIGNED NOT NULL DEFAULT 2,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_subject_stage(stage)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teachers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(120) NOT NULL,
 employee_code VARCHAR(40) DEFAULT NULL UNIQUE,
 subject VARCHAR(100) NOT NULL,
 phone VARCHAR(30) DEFAULT NULL,
 email VARCHAR(150) DEFAULT NULL,
 hire_date DATE DEFAULT NULL,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_subjects (
 teacher_id INT UNSIGNED NOT NULL,
 subject_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(teacher_id,subject_id),
 CONSTRAINT fk_ts_teacher FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
 CONSTRAINT fk_ts_subject FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS class_subjects (
 class_id INT UNSIGNED NOT NULL,
 subject_id INT UNSIGNED NOT NULL,
 teacher_id INT UNSIGNED DEFAULT NULL,
 PRIMARY KEY(class_id,subject_id),
 CONSTRAINT fk_cs_class FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE CASCADE,
 CONSTRAINT fk_cs_subject FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
 CONSTRAINT fk_cs_teacher FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_code VARCHAR(30) NOT NULL UNIQUE,
 full_name VARCHAR(120) NOT NULL,
 gender ENUM('male','female') NOT NULL,
 birth_date DATE DEFAULT NULL,
 national_id VARCHAR(40) DEFAULT NULL,
 phone VARCHAR(30) DEFAULT NULL,
 guardian_name VARCHAR(120) DEFAULT NULL,
 guardian_phone VARCHAR(30) DEFAULT NULL,
 guardian_relation VARCHAR(50) DEFAULT NULL,
 address VARCHAR(255) DEFAULT NULL,
 class_id INT UNSIGNED DEFAULT NULL,
 admission_date DATE DEFAULT NULL,
 status ENUM('active','inactive','graduated','withdrawn') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_student_class_status(class_id,status),
 INDEX idx_student_name(full_name),
 CONSTRAINT fk_student_class FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id INT UNSIGNED NOT NULL,
 attendance_date DATE NOT NULL,
 state ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present',
 note VARCHAR(255) DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_student_day(student_id,attendance_date),
 INDEX idx_attendance_day_state(attendance_date,state),
 CONSTRAINT fk_attendance_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id INT UNSIGNED NOT NULL,
 amount DECIMAL(12,2) NOT NULL,
 payment_date DATE NOT NULL,
 receipt_no VARCHAR(50) DEFAULT NULL UNIQUE,
 description VARCHAR(255) DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_payment_date(payment_date),
 CONSTRAINT fk_payment_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS installments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id INT UNSIGNED NOT NULL,
 title VARCHAR(150) NOT NULL DEFAULT 'قسط دراسي',
 total_amount DECIMAL(12,2) NOT NULL,
 paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
 due_date DATE NOT NULL,
 status ENUM('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending',
 note VARCHAR(255) DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_installment_due_status(due_date,status),
 CONSTRAINT fk_installment_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reports (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(180) NOT NULL,
 report_type ENUM('custom','students','teachers','financial') NOT NULL DEFAULT 'custom',
 content MEDIUMTEXT DEFAULT NULL,
 created_by INT UNSIGNED DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_report_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(180) NOT NULL,
 message VARCHAR(255) NOT NULL,
 category ENUM('payment','system','academic') NOT NULL DEFAULT 'system',
 link_url VARCHAR(255) DEFAULT NULL,
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_notifications_read_created(is_read,created_at)
) ENGINE=InnoDB;

INSERT INTO school_settings(id,school_name,school_name_en,school_phone,school_email,school_address,school_address_en,school_manager,academic_year)
VALUES(1,'مدرسة مدار النموذجية','Madar Model School','+967 770 104 005','info@madar.local','صنعاء، اليمن','Sana''a, Yemen','مدير النظام','2026 - 2027')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO users(full_name,username,password_hash,role,is_active)
VALUES
('مدير النظام','admin','$2y$12$SYwC5KzZrLoB3XYWFqUwdujbaTtvlbF1ciQRhUTyfahlMOXJeHMzu','admin',1),
('مدير المدرسة','manager1','$2y$12$SYwC5KzZrLoB3XYWFqUwdujbaTtvlbF1ciQRhUTyfahlMOXJeHMzu','manager',1),
('المحاسب','accountant1','$2y$12$SYwC5KzZrLoB3XYWFqUwdujbaTtvlbF1ciQRhUTyfahlMOXJeHMzu','accountant',1),
('معلم تجريبي','teacher1','$2y$12$SYwC5KzZrLoB3XYWFqUwdujbaTtvlbF1ciQRhUTyfahlMOXJeHMzu','teacher',1)
ON DUPLICATE KEY UPDATE username=VALUES(username);

INSERT INTO permissions(permission_key,label,description,module_name,sort_order) VALUES
('dashboard.view','عرض لوحة التحكم','الإحصاءات والملخص اليومي','لوحة التحكم',10),
('academic.view','عرض الهيكل الأكاديمي','المراحل والصفوف والشعب والمواد','الهيكل الأكاديمي',15),
('students.view','عرض الطلاب','البحث والاطلاع على الطلاب','الطلاب',20),
('students.manage','إدارة الطلاب','إضافة وتعديل وحذف الطلاب','الطلاب',30),
('teachers.view','عرض المعلمين','الاطلاع على الكادر التعليمي','المعلمون',40),
('teachers.manage','إدارة المعلمين','إضافة وتعديل المعلمين','المعلمون',50),
('classes.view','عرض الفصول','الاطلاع على الشعب والفصول','الفصول',60),
('classes.manage','إدارة الفصول','إضافة وتعديل الفصول','الفصول',70),
('subjects.view','عرض المواد','الاطلاع على المواد','المواد',80),
('subjects.manage','إدارة المواد','إضافة وتعديل المواد','المواد',90),
('attendance.view','عرض الحضور','الاطلاع على الحضور','الحضور',100),
('attendance.manage','تسجيل الحضور','إضافة وتعديل الحضور','الحضور',110),
('payments.view','عرض المدفوعات','الاطلاع على السجل المالي','المالية',120),
('payments.manage','إدارة المدفوعات','تسجيل المدفوعات والأقساط','المالية',130),
('reports.view','عرض التقارير','عرض التقارير','التقارير',140),
('reports.create','إنشاء التقارير','إنشاء وحفظ تقرير مخصص','التقارير',150),
('reports.print','طباعة التقارير','طباعة وتصدير التقارير','التقارير',160),
('settings.manage','إعدادات المدرسة','إدارة الهوية والإعدادات','النظام',170),
('users.manage','المستخدمون والصلاحيات','إدارة الحسابات والصلاحيات','النظام',180),
('audit.view','سجل التدقيق','مراجعة العمليات الحساسة','النظام',190)
ON DUPLICATE KEY UPDATE label=VALUES(label),description=VALUES(description),module_name=VALUES(module_name),sort_order=VALUES(sort_order);
INSERT IGNORE INTO role_permissions(role_key,permission_id) SELECT 'staff',id FROM permissions WHERE permission_key IN ('dashboard.view','academic.view','students.view','teachers.view','classes.view','subjects.view','attendance.view','reports.view');
INSERT IGNORE INTO role_permissions(role_key,permission_id) SELECT 'teacher',id FROM permissions WHERE permission_key IN ('dashboard.view','academic.view','students.view','classes.view','subjects.view','attendance.view','attendance.manage','reports.view');
INSERT IGNORE INTO role_permissions(role_key,permission_id) SELECT 'accountant',id FROM permissions WHERE permission_key IN ('dashboard.view','academic.view','students.view','payments.view','payments.manage','reports.view','reports.print');

INSERT INTO subjects(name,code,stage,description,weekly_periods) VALUES
('اللغة العربية','AR-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2),
('الرياضيات','MATH-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2),
('العلوم','SCI-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2),
('اللغة الإنجليزية','ENG-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2),
('التربية الإسلامية','ISL-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2),
('الدراسات الاجتماعية','SOC-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2),
('الحاسوب وتقنية المعلومات','ICT-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2),
('التربية الوطنية','NAT-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2),
('التربية الفنية','ART-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',1),
('التربية البدنية','PE-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',1),
('الفيزياء','PHY-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2),
('الكيمياء','CHEM-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2),
('الأحياء','BIO-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2),
('اللغة الفرنسية','FR-01','جميع المراحل','مادة دراسية تجريبية قابلة للربط بالشعب والمعلمين',2)
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO teachers(full_name,employee_code,subject,phone,email,status) VALUES
('أحمد محمد القاسمي','T-001','الرياضيات','+967 77100001','teacher1@madar.local','active'),
('سارة علي الحضرمي','T-002','اللغة العربية','+967 77100002','teacher2@madar.local','active'),
('خالد عبدالله الصبري','T-003','العلوم','+967 77100003','teacher3@madar.local','active'),
('مريم صالح','T-004','اللغة الإنجليزية','+967 77100004','teacher4@madar.local','active'),
('عبدالرحمن يحيى','T-005','التربية الإسلامية','+967 77100005','teacher5@madar.local','active'),
('نورة أحمد','T-006','الدراسات الاجتماعية','+967 77100006','teacher6@madar.local','active'),
('مازن علي','T-007','الحاسوب وتقنية المعلومات','+967 77100007','teacher7@madar.local','active'),
('فاطمة محمد','T-008','التربية الفنية','+967 77100008','teacher8@madar.local','active'),
('حسن صالح','T-009','التربية البدنية','+967 77100009','teacher9@madar.local','active'),
('سمير أحمد','T-010','الفيزياء','+967 77100010','teacher10@madar.local','active'),
('ليلى عبدالسلام','T-011','الكيمياء','+967 77100011','teacher11@madar.local','active'),
('عمر خالد','T-012','الأحياء','+967 77100012','teacher12@madar.local','active'),
('ريم عبدالله','T-013','التربية الوطنية','+967 77100013','teacher13@madar.local','active'),
('يوسف حسن','T-014','اللغة الفرنسية','+967 77100014','teacher14@madar.local','active')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name);

INSERT INTO classes(name,stage,grade,grade_level,section,room,academic_year,capacity) VALUES
('تمهيدي 1 أ','رياض الأطفال','تمهيدي 1','تمهيدي 1','أ','101','2026 - 2027',30),
('تمهيدي 1 ب','رياض الأطفال','تمهيدي 1','تمهيدي 1','ب','102','2026 - 2027',30),
('تمهيدي 2 أ','رياض الأطفال','تمهيدي 2','تمهيدي 2','أ','103','2026 - 2027',30),
('تمهيدي 2 ب','رياض الأطفال','تمهيدي 2','تمهيدي 2','ب','104','2026 - 2027',30),
('الصف الأول أ','المرحلة الابتدائية','الصف الأول','الصف الأول','أ','105','2026 - 2027',30),
('الصف الأول ب','المرحلة الابتدائية','الصف الأول','الصف الأول','ب','106','2026 - 2027',30),
('الصف الثاني أ','المرحلة الابتدائية','الصف الثاني','الصف الثاني','أ','107','2026 - 2027',30),
('الصف الثاني ب','المرحلة الابتدائية','الصف الثاني','الصف الثاني','ب','108','2026 - 2027',30),
('الصف الثالث أ','المرحلة الابتدائية','الصف الثالث','الصف الثالث','أ','109','2026 - 2027',30),
('الصف الثالث ب','المرحلة الابتدائية','الصف الثالث','الصف الثالث','ب','110','2026 - 2027',30),
('الصف الرابع أ','المرحلة الابتدائية','الصف الرابع','الصف الرابع','أ','111','2026 - 2027',30),
('الصف الرابع ب','المرحلة الابتدائية','الصف الرابع','الصف الرابع','ب','112','2026 - 2027',30),
('الصف الخامس أ','المرحلة الابتدائية','الصف الخامس','الصف الخامس','أ','113','2026 - 2027',30),
('الصف الخامس ب','المرحلة الابتدائية','الصف الخامس','الصف الخامس','ب','114','2026 - 2027',30),
('الصف السادس أ','المرحلة الابتدائية','الصف السادس','الصف السادس','أ','115','2026 - 2027',30),
('الصف السادس ب','المرحلة الابتدائية','الصف السادس','الصف السادس','ب','116','2026 - 2027',30),
('الصف السابع أ','المرحلة المتوسطة','الصف السابع','الصف السابع','أ','117','2026 - 2027',30),
('الصف السابع ب','المرحلة المتوسطة','الصف السابع','الصف السابع','ب','118','2026 - 2027',30),
('الصف الثامن أ','المرحلة المتوسطة','الصف الثامن','الصف الثامن','أ','119','2026 - 2027',30),
('الصف الثامن ب','المرحلة المتوسطة','الصف الثامن','الصف الثامن','ب','120','2026 - 2027',30),
('الصف التاسع أ','المرحلة المتوسطة','الصف التاسع','الصف التاسع','أ','121','2026 - 2027',30),
('الصف التاسع ب','المرحلة المتوسطة','الصف التاسع','الصف التاسع','ب','122','2026 - 2027',30),
('الصف العاشر أ','المرحلة الثانوية','الصف العاشر','الصف العاشر','أ','123','2026 - 2027',30),
('الصف العاشر ب','المرحلة الثانوية','الصف العاشر','الصف العاشر','ب','124','2026 - 2027',30),
('الصف الحادي عشر أ','المرحلة الثانوية','الصف الحادي عشر','الصف الحادي عشر','أ','125','2026 - 2027',30),
('الصف الحادي عشر ب','المرحلة الثانوية','الصف الحادي عشر','الصف الحادي عشر','ب','126','2026 - 2027',30),
('الصف الثاني عشر أ','المرحلة الثانوية','الصف الثاني عشر','الصف الثاني عشر','أ','127','2026 - 2027',30),
('الصف الثاني عشر ب','المرحلة الثانوية','الصف الثاني عشر','الصف الثاني عشر','ب','128','2026 - 2027',30)
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO students(student_code,full_name,gender,birth_date,guardian_name,guardian_phone,class_id,admission_date,status) VALUES
('ST-0001','محمد محمد','male','2010-001-01','محمد محمد','+967 77200001',(SELECT id FROM classes WHERE name='تمهيدي 1 أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0002','سارة علي','female','2011-002-02','علي سارة','+967 77200002',(SELECT id FROM classes WHERE name='تمهيدي 1 أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0003','عمر خالد','male','2012-003-03','خالد عمر','+967 77200003',(SELECT id FROM classes WHERE name='تمهيدي 1 أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0004','نور سالم','female','2013-004-04','سالم نور','+967 77200004',(SELECT id FROM classes WHERE name='تمهيدي 1 أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0005','عبدالله أحمد','male','2014-005-05','أحمد عبدالله','+967 77200005',(SELECT id FROM classes WHERE name='تمهيدي 1 أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0006','ياسر حسن','male','2015-006-06','حسن ياسر','+967 77200006',(SELECT id FROM classes WHERE name='تمهيدي 1 ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0007','هبة صالح','female','2016-007-07','صالح هبة','+967 77200007',(SELECT id FROM classes WHERE name='تمهيدي 1 ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0008','وليد يحيى','male','2017-008-08','يحيى وليد','+967 77200008',(SELECT id FROM classes WHERE name='تمهيدي 1 ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0009','شهد عبدالرحمن','female','2010-009-09','عبدالرحمن شهد','+967 77200009',(SELECT id FROM classes WHERE name='تمهيدي 1 ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0010','إياد ناصر','male','2011-001-10','ناصر إياد','+967 77200010',(SELECT id FROM classes WHERE name='تمهيدي 1 ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0011','محمد محمد','male','2012-002-11','محمد محمد','+967 77200011',(SELECT id FROM classes WHERE name='تمهيدي 2 أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0012','سارة علي','female','2013-003-12','علي سارة','+967 77200012',(SELECT id FROM classes WHERE name='تمهيدي 2 أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0013','عمر خالد','male','2014-004-13','خالد عمر','+967 77200013',(SELECT id FROM classes WHERE name='تمهيدي 2 أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0014','نور سالم','female','2015-005-14','سالم نور','+967 77200014',(SELECT id FROM classes WHERE name='تمهيدي 2 أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0015','عبدالله أحمد','male','2016-006-15','أحمد عبدالله','+967 77200015',(SELECT id FROM classes WHERE name='تمهيدي 2 أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0016','ياسر حسن','male','2017-007-16','حسن ياسر','+967 77200016',(SELECT id FROM classes WHERE name='تمهيدي 2 ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0017','هبة صالح','female','2010-008-17','صالح هبة','+967 77200017',(SELECT id FROM classes WHERE name='تمهيدي 2 ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0018','وليد يحيى','male','2011-009-18','يحيى وليد','+967 77200018',(SELECT id FROM classes WHERE name='تمهيدي 2 ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0019','شهد عبدالرحمن','female','2012-001-19','عبدالرحمن شهد','+967 77200019',(SELECT id FROM classes WHERE name='تمهيدي 2 ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0020','إياد ناصر','male','2013-002-20','ناصر إياد','+967 77200020',(SELECT id FROM classes WHERE name='تمهيدي 2 ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0021','محمد محمد','male','2014-003-01','محمد محمد','+967 77200021',(SELECT id FROM classes WHERE name='الصف الأول أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0022','سارة علي','female','2015-004-02','علي سارة','+967 77200022',(SELECT id FROM classes WHERE name='الصف الأول أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0023','عمر خالد','male','2016-005-03','خالد عمر','+967 77200023',(SELECT id FROM classes WHERE name='الصف الأول أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0024','نور سالم','female','2017-006-04','سالم نور','+967 77200024',(SELECT id FROM classes WHERE name='الصف الأول أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0025','عبدالله أحمد','male','2010-007-05','أحمد عبدالله','+967 77200025',(SELECT id FROM classes WHERE name='الصف الأول أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0026','ياسر حسن','male','2011-008-06','حسن ياسر','+967 77200026',(SELECT id FROM classes WHERE name='الصف الأول ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0027','هبة صالح','female','2012-009-07','صالح هبة','+967 77200027',(SELECT id FROM classes WHERE name='الصف الأول ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0028','وليد يحيى','male','2013-001-08','يحيى وليد','+967 77200028',(SELECT id FROM classes WHERE name='الصف الأول ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0029','شهد عبدالرحمن','female','2014-002-09','عبدالرحمن شهد','+967 77200029',(SELECT id FROM classes WHERE name='الصف الأول ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0030','إياد ناصر','male','2015-003-10','ناصر إياد','+967 77200030',(SELECT id FROM classes WHERE name='الصف الأول ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0031','محمد محمد','male','2016-004-11','محمد محمد','+967 77200031',(SELECT id FROM classes WHERE name='الصف الثاني أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0032','سارة علي','female','2017-005-12','علي سارة','+967 77200032',(SELECT id FROM classes WHERE name='الصف الثاني أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0033','عمر خالد','male','2010-006-13','خالد عمر','+967 77200033',(SELECT id FROM classes WHERE name='الصف الثاني أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0034','نور سالم','female','2011-007-14','سالم نور','+967 77200034',(SELECT id FROM classes WHERE name='الصف الثاني أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0035','عبدالله أحمد','male','2012-008-15','أحمد عبدالله','+967 77200035',(SELECT id FROM classes WHERE name='الصف الثاني أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0036','ياسر حسن','male','2013-009-16','حسن ياسر','+967 77200036',(SELECT id FROM classes WHERE name='الصف الثاني ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0037','هبة صالح','female','2014-001-17','صالح هبة','+967 77200037',(SELECT id FROM classes WHERE name='الصف الثاني ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0038','وليد يحيى','male','2015-002-18','يحيى وليد','+967 77200038',(SELECT id FROM classes WHERE name='الصف الثاني ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0039','شهد عبدالرحمن','female','2016-003-19','عبدالرحمن شهد','+967 77200039',(SELECT id FROM classes WHERE name='الصف الثاني ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0040','إياد ناصر','male','2017-004-20','ناصر إياد','+967 77200040',(SELECT id FROM classes WHERE name='الصف الثاني ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0041','محمد محمد','male','2010-005-01','محمد محمد','+967 77200041',(SELECT id FROM classes WHERE name='الصف الثالث أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0042','سارة علي','female','2011-006-02','علي سارة','+967 77200042',(SELECT id FROM classes WHERE name='الصف الثالث أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0043','عمر خالد','male','2012-007-03','خالد عمر','+967 77200043',(SELECT id FROM classes WHERE name='الصف الثالث أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0044','نور سالم','female','2013-008-04','سالم نور','+967 77200044',(SELECT id FROM classes WHERE name='الصف الثالث أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0045','عبدالله أحمد','male','2014-009-05','أحمد عبدالله','+967 77200045',(SELECT id FROM classes WHERE name='الصف الثالث أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0046','ياسر حسن','male','2015-001-06','حسن ياسر','+967 77200046',(SELECT id FROM classes WHERE name='الصف الثالث ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0047','هبة صالح','female','2016-002-07','صالح هبة','+967 77200047',(SELECT id FROM classes WHERE name='الصف الثالث ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0048','وليد يحيى','male','2017-003-08','يحيى وليد','+967 77200048',(SELECT id FROM classes WHERE name='الصف الثالث ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0049','شهد عبدالرحمن','female','2010-004-09','عبدالرحمن شهد','+967 77200049',(SELECT id FROM classes WHERE name='الصف الثالث ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0050','إياد ناصر','male','2011-005-10','ناصر إياد','+967 77200050',(SELECT id FROM classes WHERE name='الصف الثالث ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0051','محمد محمد','male','2012-006-11','محمد محمد','+967 77200051',(SELECT id FROM classes WHERE name='الصف الرابع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0052','سارة علي','female','2013-007-12','علي سارة','+967 77200052',(SELECT id FROM classes WHERE name='الصف الرابع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0053','عمر خالد','male','2014-008-13','خالد عمر','+967 77200053',(SELECT id FROM classes WHERE name='الصف الرابع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0054','نور سالم','female','2015-009-14','سالم نور','+967 77200054',(SELECT id FROM classes WHERE name='الصف الرابع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0055','عبدالله أحمد','male','2016-001-15','أحمد عبدالله','+967 77200055',(SELECT id FROM classes WHERE name='الصف الرابع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0056','ياسر حسن','male','2017-002-16','حسن ياسر','+967 77200056',(SELECT id FROM classes WHERE name='الصف الرابع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0057','هبة صالح','female','2010-003-17','صالح هبة','+967 77200057',(SELECT id FROM classes WHERE name='الصف الرابع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0058','وليد يحيى','male','2011-004-18','يحيى وليد','+967 77200058',(SELECT id FROM classes WHERE name='الصف الرابع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0059','شهد عبدالرحمن','female','2012-005-19','عبدالرحمن شهد','+967 77200059',(SELECT id FROM classes WHERE name='الصف الرابع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0060','إياد ناصر','male','2013-006-20','ناصر إياد','+967 77200060',(SELECT id FROM classes WHERE name='الصف الرابع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0061','محمد محمد','male','2014-007-01','محمد محمد','+967 77200061',(SELECT id FROM classes WHERE name='الصف الخامس أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0062','سارة علي','female','2015-008-02','علي سارة','+967 77200062',(SELECT id FROM classes WHERE name='الصف الخامس أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0063','عمر خالد','male','2016-009-03','خالد عمر','+967 77200063',(SELECT id FROM classes WHERE name='الصف الخامس أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0064','نور سالم','female','2017-001-04','سالم نور','+967 77200064',(SELECT id FROM classes WHERE name='الصف الخامس أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0065','عبدالله أحمد','male','2010-002-05','أحمد عبدالله','+967 77200065',(SELECT id FROM classes WHERE name='الصف الخامس أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0066','ياسر حسن','male','2011-003-06','حسن ياسر','+967 77200066',(SELECT id FROM classes WHERE name='الصف الخامس ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0067','هبة صالح','female','2012-004-07','صالح هبة','+967 77200067',(SELECT id FROM classes WHERE name='الصف الخامس ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0068','وليد يحيى','male','2013-005-08','يحيى وليد','+967 77200068',(SELECT id FROM classes WHERE name='الصف الخامس ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0069','شهد عبدالرحمن','female','2014-006-09','عبدالرحمن شهد','+967 77200069',(SELECT id FROM classes WHERE name='الصف الخامس ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0070','إياد ناصر','male','2015-007-10','ناصر إياد','+967 77200070',(SELECT id FROM classes WHERE name='الصف الخامس ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0071','محمد محمد','male','2016-008-11','محمد محمد','+967 77200071',(SELECT id FROM classes WHERE name='الصف السادس أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0072','سارة علي','female','2017-009-12','علي سارة','+967 77200072',(SELECT id FROM classes WHERE name='الصف السادس أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0073','عمر خالد','male','2010-001-13','خالد عمر','+967 77200073',(SELECT id FROM classes WHERE name='الصف السادس أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0074','نور سالم','female','2011-002-14','سالم نور','+967 77200074',(SELECT id FROM classes WHERE name='الصف السادس أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0075','عبدالله أحمد','male','2012-003-15','أحمد عبدالله','+967 77200075',(SELECT id FROM classes WHERE name='الصف السادس أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0076','ياسر حسن','male','2013-004-16','حسن ياسر','+967 77200076',(SELECT id FROM classes WHERE name='الصف السادس ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0077','هبة صالح','female','2014-005-17','صالح هبة','+967 77200077',(SELECT id FROM classes WHERE name='الصف السادس ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0078','وليد يحيى','male','2015-006-18','يحيى وليد','+967 77200078',(SELECT id FROM classes WHERE name='الصف السادس ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0079','شهد عبدالرحمن','female','2016-007-19','عبدالرحمن شهد','+967 77200079',(SELECT id FROM classes WHERE name='الصف السادس ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0080','إياد ناصر','male','2017-008-20','ناصر إياد','+967 77200080',(SELECT id FROM classes WHERE name='الصف السادس ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0081','محمد محمد','male','2010-009-01','محمد محمد','+967 77200081',(SELECT id FROM classes WHERE name='الصف السابع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0082','سارة علي','female','2011-001-02','علي سارة','+967 77200082',(SELECT id FROM classes WHERE name='الصف السابع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0083','عمر خالد','male','2012-002-03','خالد عمر','+967 77200083',(SELECT id FROM classes WHERE name='الصف السابع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0084','نور سالم','female','2013-003-04','سالم نور','+967 77200084',(SELECT id FROM classes WHERE name='الصف السابع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0085','عبدالله أحمد','male','2014-004-05','أحمد عبدالله','+967 77200085',(SELECT id FROM classes WHERE name='الصف السابع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0086','ياسر حسن','male','2015-005-06','حسن ياسر','+967 77200086',(SELECT id FROM classes WHERE name='الصف السابع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0087','هبة صالح','female','2016-006-07','صالح هبة','+967 77200087',(SELECT id FROM classes WHERE name='الصف السابع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0088','وليد يحيى','male','2017-007-08','يحيى وليد','+967 77200088',(SELECT id FROM classes WHERE name='الصف السابع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0089','شهد عبدالرحمن','female','2010-008-09','عبدالرحمن شهد','+967 77200089',(SELECT id FROM classes WHERE name='الصف السابع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0090','إياد ناصر','male','2011-009-10','ناصر إياد','+967 77200090',(SELECT id FROM classes WHERE name='الصف السابع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0091','محمد محمد','male','2012-001-11','محمد محمد','+967 77200091',(SELECT id FROM classes WHERE name='الصف الثامن أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0092','سارة علي','female','2013-002-12','علي سارة','+967 77200092',(SELECT id FROM classes WHERE name='الصف الثامن أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0093','عمر خالد','male','2014-003-13','خالد عمر','+967 77200093',(SELECT id FROM classes WHERE name='الصف الثامن أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0094','نور سالم','female','2015-004-14','سالم نور','+967 77200094',(SELECT id FROM classes WHERE name='الصف الثامن أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0095','عبدالله أحمد','male','2016-005-15','أحمد عبدالله','+967 77200095',(SELECT id FROM classes WHERE name='الصف الثامن أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0096','ياسر حسن','male','2017-006-16','حسن ياسر','+967 77200096',(SELECT id FROM classes WHERE name='الصف الثامن ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0097','هبة صالح','female','2010-007-17','صالح هبة','+967 77200097',(SELECT id FROM classes WHERE name='الصف الثامن ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0098','وليد يحيى','male','2011-008-18','يحيى وليد','+967 77200098',(SELECT id FROM classes WHERE name='الصف الثامن ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0099','شهد عبدالرحمن','female','2012-009-19','عبدالرحمن شهد','+967 77200099',(SELECT id FROM classes WHERE name='الصف الثامن ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0100','إياد ناصر','male','2013-001-20','ناصر إياد','+967 77200100',(SELECT id FROM classes WHERE name='الصف الثامن ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0101','محمد محمد','male','2014-002-01','محمد محمد','+967 77200101',(SELECT id FROM classes WHERE name='الصف التاسع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0102','سارة علي','female','2015-003-02','علي سارة','+967 77200102',(SELECT id FROM classes WHERE name='الصف التاسع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0103','عمر خالد','male','2016-004-03','خالد عمر','+967 77200103',(SELECT id FROM classes WHERE name='الصف التاسع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0104','نور سالم','female','2017-005-04','سالم نور','+967 77200104',(SELECT id FROM classes WHERE name='الصف التاسع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0105','عبدالله أحمد','male','2010-006-05','أحمد عبدالله','+967 77200105',(SELECT id FROM classes WHERE name='الصف التاسع أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0106','ياسر حسن','male','2011-007-06','حسن ياسر','+967 77200106',(SELECT id FROM classes WHERE name='الصف التاسع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0107','هبة صالح','female','2012-008-07','صالح هبة','+967 77200107',(SELECT id FROM classes WHERE name='الصف التاسع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0108','وليد يحيى','male','2013-009-08','يحيى وليد','+967 77200108',(SELECT id FROM classes WHERE name='الصف التاسع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0109','شهد عبدالرحمن','female','2014-001-09','عبدالرحمن شهد','+967 77200109',(SELECT id FROM classes WHERE name='الصف التاسع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0110','إياد ناصر','male','2015-002-10','ناصر إياد','+967 77200110',(SELECT id FROM classes WHERE name='الصف التاسع ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0111','محمد محمد','male','2016-003-11','محمد محمد','+967 77200111',(SELECT id FROM classes WHERE name='الصف العاشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0112','سارة علي','female','2017-004-12','علي سارة','+967 77200112',(SELECT id FROM classes WHERE name='الصف العاشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0113','عمر خالد','male','2010-005-13','خالد عمر','+967 77200113',(SELECT id FROM classes WHERE name='الصف العاشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0114','نور سالم','female','2011-006-14','سالم نور','+967 77200114',(SELECT id FROM classes WHERE name='الصف العاشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0115','عبدالله أحمد','male','2012-007-15','أحمد عبدالله','+967 77200115',(SELECT id FROM classes WHERE name='الصف العاشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0116','ياسر حسن','male','2013-008-16','حسن ياسر','+967 77200116',(SELECT id FROM classes WHERE name='الصف العاشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0117','هبة صالح','female','2014-009-17','صالح هبة','+967 77200117',(SELECT id FROM classes WHERE name='الصف العاشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0118','وليد يحيى','male','2015-001-18','يحيى وليد','+967 77200118',(SELECT id FROM classes WHERE name='الصف العاشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0119','شهد عبدالرحمن','female','2016-002-19','عبدالرحمن شهد','+967 77200119',(SELECT id FROM classes WHERE name='الصف العاشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0120','إياد ناصر','male','2017-003-20','ناصر إياد','+967 77200120',(SELECT id FROM classes WHERE name='الصف العاشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0121','محمد محمد','male','2010-004-01','محمد محمد','+967 77200121',(SELECT id FROM classes WHERE name='الصف الحادي عشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0122','سارة علي','female','2011-005-02','علي سارة','+967 77200122',(SELECT id FROM classes WHERE name='الصف الحادي عشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0123','عمر خالد','male','2012-006-03','خالد عمر','+967 77200123',(SELECT id FROM classes WHERE name='الصف الحادي عشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0124','نور سالم','female','2013-007-04','سالم نور','+967 77200124',(SELECT id FROM classes WHERE name='الصف الحادي عشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0125','عبدالله أحمد','male','2014-008-05','أحمد عبدالله','+967 77200125',(SELECT id FROM classes WHERE name='الصف الحادي عشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0126','ياسر حسن','male','2015-009-06','حسن ياسر','+967 77200126',(SELECT id FROM classes WHERE name='الصف الحادي عشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0127','هبة صالح','female','2016-001-07','صالح هبة','+967 77200127',(SELECT id FROM classes WHERE name='الصف الحادي عشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0128','وليد يحيى','male','2017-002-08','يحيى وليد','+967 77200128',(SELECT id FROM classes WHERE name='الصف الحادي عشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0129','شهد عبدالرحمن','female','2010-003-09','عبدالرحمن شهد','+967 77200129',(SELECT id FROM classes WHERE name='الصف الحادي عشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0130','إياد ناصر','male','2011-004-10','ناصر إياد','+967 77200130',(SELECT id FROM classes WHERE name='الصف الحادي عشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0131','محمد محمد','male','2012-005-11','محمد محمد','+967 77200131',(SELECT id FROM classes WHERE name='الصف الثاني عشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0132','سارة علي','female','2013-006-12','علي سارة','+967 77200132',(SELECT id FROM classes WHERE name='الصف الثاني عشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0133','عمر خالد','male','2014-007-13','خالد عمر','+967 77200133',(SELECT id FROM classes WHERE name='الصف الثاني عشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0134','نور سالم','female','2015-008-14','سالم نور','+967 77200134',(SELECT id FROM classes WHERE name='الصف الثاني عشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0135','عبدالله أحمد','male','2016-009-15','أحمد عبدالله','+967 77200135',(SELECT id FROM classes WHERE name='الصف الثاني عشر أ' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0136','ياسر حسن','male','2017-001-16','حسن ياسر','+967 77200136',(SELECT id FROM classes WHERE name='الصف الثاني عشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0137','هبة صالح','female','2010-002-17','صالح هبة','+967 77200137',(SELECT id FROM classes WHERE name='الصف الثاني عشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0138','وليد يحيى','male','2011-003-18','يحيى وليد','+967 77200138',(SELECT id FROM classes WHERE name='الصف الثاني عشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0139','شهد عبدالرحمن','female','2012-004-19','عبدالرحمن شهد','+967 77200139',(SELECT id FROM classes WHERE name='الصف الثاني عشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active'),
('ST-0140','إياد ناصر','male','2013-005-20','ناصر إياد','+967 77200140',(SELECT id FROM classes WHERE name='الصف الثاني عشر ب' AND academic_year='2026 - 2027'),'2026-09-01','active')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name),class_id=VALUES(class_id);

INSERT IGNORE INTO teacher_subjects(teacher_id,subject_id)
SELECT t.id,s.id FROM teachers t JOIN subjects s ON s.name=t.subject;

INSERT IGNORE INTO class_subjects(class_id,subject_id,teacher_id)
SELECT c.id,s.id,(SELECT MIN(ts.teacher_id) FROM teacher_subjects ts WHERE ts.subject_id=s.id)
FROM classes c CROSS JOIN subjects s;

INSERT INTO installments(student_id,title,total_amount,paid_amount,due_date,status,note)
SELECT id,'القسط الأول',50000,15000,DATE_SUB(CURDATE(),INTERVAL 5 DAY),'overdue','بيانات تجريبية لاختبار التنبيهات'
FROM students WHERE student_code='ST-0001' AND NOT EXISTS(SELECT 1 FROM installments);

INSERT INTO payments(student_id,amount,payment_date,receipt_no,description)
SELECT id,15000,CURDATE(),'RCPT-DEMO-001','دفعة تجريبية للقسط الأول' FROM students WHERE student_code='ST-0001' AND NOT EXISTS(SELECT 1 FROM payments);

SET FOREIGN_KEY_CHECKS=1;
