-- ============================================================
-- PostgreSQL Database Schema for Medical Cabinet
-- Corrected from JMerise MLD with proper relationships
-- ============================================================

-- ----------------------------
-- Table: ADMIN
-- ----------------------------
CREATE TABLE IF NOT EXISTS ADMIN (
    admin_id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------
-- Table: PATIENT
-- ----------------------------
CREATE TABLE IF NOT EXISTS PATIENT (
    patient_id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------
-- Table: DOCTOR
-- ----------------------------
CREATE TABLE IF NOT EXISTS DOCTOR (
    doctor_id SERIAL PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    specialty VARCHAR(255) NOT NULL,
    description TEXT,
    email VARCHAR(255) UNIQUE NOT NULL DEFAULT 'doctor@cabinet.com', -- Default to avoid errors on existing data if any
    username VARCHAR(100) UNIQUE NOT NULL DEFAULT 'doctor',          -- Default to avoid errors on existing data if any
    password_hash VARCHAR(255) NOT NULL DEFAULT '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Default password
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------
-- Table: APPOINTMENT
-- CORRECTED: FKs now point FROM appointment TO patient/doctor
-- ----------------------------
CREATE TABLE IF NOT EXISTS APPOINTMENT (
    appointment_id SERIAL PRIMARY KEY,
    patient_id INTEGER NOT NULL,
    doctor_id INTEGER NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason TEXT NOT NULL,
    is_first_appointment BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT APPOINTMENT_patient_FK FOREIGN KEY (patient_id) 
        REFERENCES PATIENT(patient_id) ON DELETE CASCADE,
    CONSTRAINT APPOINTMENT_doctor_FK FOREIGN KEY (doctor_id) 
        REFERENCES DOCTOR(doctor_id) ON DELETE CASCADE
);

-- ============================================================
-- INDEXES for Performance
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_patient_email ON PATIENT(email);
CREATE INDEX IF NOT EXISTS idx_patient_username ON PATIENT(username);
CREATE INDEX IF NOT EXISTS idx_admin_username ON ADMIN(username);
CREATE INDEX IF NOT EXISTS idx_appointment_patient ON APPOINTMENT(patient_id);
CREATE INDEX IF NOT EXISTS idx_appointment_doctor ON APPOINTMENT(doctor_id);
CREATE INDEX IF NOT EXISTS idx_appointment_date ON APPOINTMENT(appointment_date);
CREATE INDEX IF NOT EXISTS idx_appointment_status ON APPOINTMENT(status);

-- ============================================================
-- INITIAL DATA
-- ============================================================

-- Insert default admin user (username: admin, password: admin123)
-- Password hash for 'admin123'
INSERT INTO ADMIN (admin_id, username, email, password_hash) 
VALUES (
    1, 
    'admin', 
    'admin@cabinet.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
) ON CONFLICT (admin_id) DO NOTHING;

-- Insert sample doctors
-- Insert sample doctors (Password: doc123)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- Insert sample doctors (Password: doctor123)
-- Insert sample doctors (Passwords are 'doctor1', 'doctor2', 'doctor3')
-- Hashes generated via PHP password_hash()
INSERT INTO DOCTOR (doctor_id, first_name, last_name, specialty, description, email, username, password_hash) VALUES
(1, 'Marie', 'Dubois', 'Médecin généraliste', 'Spécialiste en médecine générale avec 15 ans d''expérience', 'marie.dubois@cabinet.com', 'dr.dubois', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'Jean', 'Martin', 'Pédiatre', 'Expert en pédiatrie, spécialisé dans le suivi des enfants', 'jean.martin@cabinet.com', 'dr.martin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(3, 'Sophie', 'Bernard', 'Cardiologue', 'Cardiologue expérimentée, consultations et examens cardiovasculaires', 'sophie.bernard@cabinet.com', 'dr.bernard', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON CONFLICT (doctor_id) DO UPDATE SET 
    email = EXCLUDED.email, 
    username = EXCLUDED.username, 
    password_hash = EXCLUDED.password_hash;

-- Insert sample patient for testing (username: patient1, password: test123)
INSERT INTO PATIENT (patient_id, email, username, password_hash, first_name, last_name, phone_number) 
VALUES (
    1,
    'patient@test.com',
    'patient1',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Pierre',
    'Dupont',
    '0612345678'
) ON CONFLICT (patient_id) DO NOTHING;

-- Insert sample appointments
INSERT INTO APPOINTMENT (patient_id, doctor_id, appointment_date, appointment_time, reason, is_first_appointment, status) VALUES
(1, 1, CURRENT_DATE + INTERVAL '3 days', '09:30:00', 'Consultation de suivi', false, 'confirmed'),
(1, 2, CURRENT_DATE + INTERVAL '7 days', '14:00:00', 'Première consultation', true, 'pending'),
(1, 3, CURRENT_DATE + INTERVAL '10 days', '10:00:00', 'Bilan cardiaque', true, 'confirmed'),
(1, 1, CURRENT_DATE - INTERVAL '1 month', '11:00:00', 'Grippe saisonnière', false, 'confirmed'),
(1, 3, CURRENT_DATE + INTERVAL '5 days', '16:00:00', 'Annulation imprévue', false, 'cancelled')
ON CONFLICT (appointment_id) DO NOTHING;

-- ============================================================
-- SEQUENCE ADJUSTMENTS (ensure auto-increment continues correctly)
-- ============================================================
SELECT setval('admin_admin_id_seq', (SELECT MAX(admin_id) FROM ADMIN), true);
SELECT setval('patient_patient_id_seq', (SELECT MAX(patient_id) FROM PATIENT), true);
SELECT setval('doctor_doctor_id_seq', (SELECT MAX(doctor_id) FROM DOCTOR), true);
SELECT setval('appointment_appointment_id_seq', (SELECT MAX(appointment_id) FROM APPOINTMENT), true);

-- ============================================================
-- PERMISSIONS
-- ============================================================
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO postgres;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO postgres;
