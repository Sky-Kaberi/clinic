CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uhid VARCHAR(30) NOT NULL UNIQUE,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  dob DATE NULL,
  gender ENUM('Male','Female','Other') NOT NULL,
  mobile VARCHAR(20) NOT NULL,
  email VARCHAR(120) NULL,
  address VARCHAR(255) NULL,
  emergency_contact VARCHAR(20) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS doctors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  specialization VARCHAR(100) NULL,
  consultation_fee DECIMAL(10,2) DEFAULT 0,
  schedule_json JSON NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  appointment_date DATE NOT NULL,
  slot_time TIME NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'booked',
  token_no INT NULL,
  notes VARCHAR(255) NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  FOREIGN KEY (doctor_id) REFERENCES doctors(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  appointment_id INT NULL,
  symptoms TEXT,
  vitals TEXT,
  diagnosis TEXT,
  advice TEXT,
  follow_up_date DATE NULL,
  recommended_tests TEXT,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  FOREIGN KEY (doctor_id) REFERENCES doctors(id),
  FOREIGN KEY (appointment_id) REFERENCES appointments(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS prescriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  visit_id INT NOT NULL,
  medicines TEXT,
  notes TEXT,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (visit_id) REFERENCES visits(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS test_master (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  category ENUM('pathology','imaging','ecg','package') NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  preparation_note VARCHAR(255) NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS diagnostic_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  booking_type ENUM('pathology','imaging','ecg','package') NOT NULL,
  priority ENUM('normal','priority') DEFAULT 'normal',
  fasting_note VARCHAR(255) NULL,
  sample_schedule_at DATETIME NULL,
  status VARCHAR(30) DEFAULT 'booked',
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS diagnostic_booking_tests (
  booking_id INT NOT NULL,
  test_id INT NOT NULL,
  PRIMARY KEY (booking_id, test_id),
  FOREIGN KEY (booking_id) REFERENCES diagnostic_bookings(id),
  FOREIGN KEY (test_id) REFERENCES test_master(id)
);

CREATE TABLE IF NOT EXISTS samples (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  barcode VARCHAR(50) UNIQUE,
  status VARCHAR(30) DEFAULT 'collected',
  collected_by INT NULL,
  collected_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES diagnostic_bookings(id),
  FOREIGN KEY (collected_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sample_id INT NOT NULL UNIQUE,
  result_text TEXT,
  status ENUM('draft','verified','approved') DEFAULT 'draft',
  entered_by INT,
  approved_by INT NULL,
  approved_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sample_id) REFERENCES samples(id),
  FOREIGN KEY (entered_by) REFERENCES users(id),
  FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS radiology_workflow (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  technician_id INT NULL,
  report_text TEXT,
  status ENUM('draft','verified','approved') DEFAULT 'draft',
  approved_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES diagnostic_bookings(id),
  FOREIGN KEY (technician_id) REFERENCES users(id),
  FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS bills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  bill_type ENUM('consultation','diagnostics','combined','package') NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('unpaid','partial','paid','refunded') NOT NULL DEFAULT 'unpaid',
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bill_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_mode ENUM('Cash','Card','UPI','Net Banking','Wallet') NOT NULL,
  reference_no VARCHAR(100) NULL,
  received_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (bill_id) REFERENCES bills(id),
  FOREIGN KEY (received_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_name VARCHAR(120) NOT NULL,
  category ENUM('Consumable','Reagent') NOT NULL,
  unit VARCHAR(20) NOT NULL,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
  reorder_level DECIMAL(10,2) NOT NULL DEFAULT 0,
  vendor_name VARCHAR(120) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inventory_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inventory_id INT NOT NULL,
  txn_type ENUM('purchase','consumption') NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  notes VARCHAR(255) NULL,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (inventory_id) REFERENCES inventory(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS communication_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NULL,
  channel ENUM('SMS','Email','WhatsApp') NOT NULL,
  template_name VARCHAR(100),
  message_body TEXT,
  delivery_status VARCHAR(30) DEFAULT 'queued',
  sent_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  FOREIGN KEY (sent_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(50) NOT NULL,
  module VARCHAR(50) NOT NULL,
  entity_type VARCHAR(50) NULL,
  entity_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO users(name, email, password_hash, role) VALUES
('Super Admin', 'admin@clinic.local', '$2y$12$895eYIu.u.Tpn21njuc69uXV6hnjsCetdxgEhIM321g8a3H04R8Wm', 'Super Admin'),
('Dr. Raj Sharma', 'doctor@clinic.local', '$2y$12$SDFqubTon5KV/wgUprapB.rLPv/rbvMOjMdClxhj83dqEDJXuY.Na', 'Doctor'),
('Cashier User', 'cashier@clinic.local', '$2y$12$6PyxrH2PafceFQr8DhAuqu62zoUuWWez0nwh09UTx3DPoaOWIVtGO', 'Cashier')
ON DUPLICATE KEY UPDATE email=email;

INSERT INTO doctors(name,specialization,consultation_fee) VALUES
('Dr. Raj Sharma','General Medicine',500),
('Dr. Anita Rao','Cardiology',800)
ON DUPLICATE KEY UPDATE name=name;

INSERT INTO test_master(code,name,category,price,preparation_note) VALUES
('CBC','Complete Blood Count','pathology',300,'No preparation needed'),
('LFT','Liver Function Test','pathology',700,'8 hours fasting preferred'),
('XRAYCHEST','X-Ray Chest','imaging',600,'Remove metal objects'),
('ECG01','ECG','ecg',400,'Rest for 10 mins before test'),
('WELLPKG','Wellness Package','package',2500,'12 hours fasting')
ON DUPLICATE KEY UPDATE code=code;
