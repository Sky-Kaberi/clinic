-- Production hardening migration for existing ClinicMS schema.

ALTER TABLE patients
  MODIFY first_name VARCHAR(80) NOT NULL,
  MODIFY mobile VARCHAR(20) NOT NULL,
  ADD CONSTRAINT uq_patients_mobile UNIQUE (mobile),
  ADD CONSTRAINT uq_patients_email UNIQUE (email),
  ADD INDEX idx_patients_name (first_name, last_name);

ALTER TABLE doctors
  ADD COLUMN email VARCHAR(120) NULL AFTER name,
  ADD CONSTRAINT uq_doctors_email UNIQUE (email),
  ADD INDEX idx_doctors_active (is_active, name);

ALTER TABLE users
  ADD COLUMN username VARCHAR(60) NOT NULL AFTER name,
  ADD CONSTRAINT uq_users_username UNIQUE (username),
  ADD INDEX idx_users_role_active (role, is_active);

UPDATE users
SET username = LOWER(REPLACE(SUBSTRING_INDEX(email, '@', 1), ' ', ''))
WHERE username IS NULL OR username = '';

ALTER TABLE appointments
  MODIFY status ENUM('booked','walk_in','follow_up','rescheduled','completed','cancelled') NOT NULL DEFAULT 'booked',
  ADD INDEX idx_appointments_patient_date (patient_id, appointment_date),
  ADD INDEX idx_appointments_doctor_date (doctor_id, appointment_date),
  ADD UNIQUE KEY uq_appointment_patient_doctor_slot (patient_id, doctor_id, appointment_date, slot_time),
  ADD UNIQUE KEY uq_appointment_doctor_slot (doctor_id, appointment_date, slot_time);

ALTER TABLE diagnostic_bookings
  MODIFY status ENUM('booked','sample_collected','processing','verified','approved','released','cancelled') NOT NULL DEFAULT 'booked',
  ADD INDEX idx_diag_patient_status (patient_id, status),
  ADD INDEX idx_diag_schedule (sample_schedule_at);

ALTER TABLE samples
  MODIFY status ENUM('collected','processing','processed','reported','released') NOT NULL DEFAULT 'collected',
  ADD INDEX idx_samples_booking (booking_id),
  ADD INDEX idx_samples_status (status);

ALTER TABLE reports
  MODIFY status ENUM('draft','verified','approved','released') NOT NULL DEFAULT 'draft',
  ADD COLUMN released_by INT NULL AFTER approved_by,
  ADD COLUMN released_at DATETIME NULL AFTER approved_at,
  ADD CONSTRAINT fk_reports_released_by FOREIGN KEY (released_by) REFERENCES users(id),
  ADD INDEX idx_reports_status (status);

ALTER TABLE bills
  ADD COLUMN refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER paid_amount,
  ADD CONSTRAINT chk_bill_amounts CHECK (total_amount >= 0 AND paid_amount >= 0 AND refund_amount >= 0 AND paid_amount <= total_amount),
  ADD INDEX idx_bills_patient_status (patient_id, status);

ALTER TABLE payments
  ADD INDEX idx_payments_bill_created (bill_id, created_at);

ALTER TABLE visits
  ADD INDEX idx_visits_patient_created (patient_id, created_at),
  ADD INDEX idx_visits_doctor_created (doctor_id, created_at);

ALTER TABLE audit_logs
  ADD INDEX idx_audit_module_created (module, created_at),
  ADD INDEX idx_audit_user_created (user_id, created_at);
