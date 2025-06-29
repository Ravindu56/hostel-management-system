-- Create Database
CREATE DATABASE hostel_management_system;
USE hostel_management_system;

-- Authentication Table for unified login system
CREATE TABLE user_auth (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    salt VARCHAR(32) NOT NULL,
    hash_algorithm VARCHAR(20) DEFAULT 'bcrypt',
    user_role ENUM('student', 'warden', 'admin') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    failed_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL
);

-- Admin Table with enhanced name and contact attributes
CREATE TABLE Admin_Table (
    Admin_ID VARCHAR(10) PRIMARY KEY,
    user_id INT UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    address_line1 VARCHAR(100),
    address_line2 VARCHAR(100),
    city VARCHAR(50),
    state VARCHAR(50),
    postal_code VARCHAR(10),
    role VARCHAR(50) DEFAULT 'System Administrator',
    department VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_auth(user_id) ON DELETE CASCADE
);

-- Warden Table with enhanced attributes
CREATE TABLE Warden_Table (
    Warden_ID VARCHAR(10) PRIMARY KEY,
    user_id INT UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    alternate_mobile VARCHAR(15),
    address_line1 VARCHAR(100),
    address_line2 VARCHAR(100),
    city VARCHAR(50),
    state VARCHAR(50),
    postal_code VARCHAR(10),
    role VARCHAR(50) DEFAULT 'Hostel Warden',
    shift_timing VARCHAR(50),
    emergency_contact VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_auth(user_id) ON DELETE CASCADE
);

-- Room Table with enhanced room management
CREATE TABLE Room_Table (
    Room_ID VARCHAR(10) PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    room_type ENUM('Single', 'Double', 'Triple') NOT NULL,
    ac_type ENUM('AC', 'NonAC') NOT NULL,
    capacity INT NOT NULL,
    occupied_count INT DEFAULT 0,
    floor_number INT,
    wing VARCHAR(10),
    monthly_rent DECIMAL(10,2),
    security_deposit DECIMAL(10,2),
    amenities TEXT,
    room_status ENUM('available', 'occupied', 'maintenance', 'reserved') DEFAULT 'available',
    warden_id VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (warden_id) REFERENCES Warden_Table(Warden_ID),
    CHECK (occupied_count <= capacity)
);

-- Student Table with comprehensive personal information
CREATE TABLE Student_Table (
    Student_ID VARCHAR(10) PRIMARY KEY,
    user_id INT UNIQUE,
    student_roll_number VARCHAR(20) UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    full_name VARCHAR(100) GENERATED ALWAYS AS (CONCAT(first_name, ' ', last_name)) STORED,
    email VARCHAR(100) UNIQUE,
    mobile_number VARCHAR(15) NOT NULL,
    parent_mobile VARCHAR(15),
    emergency_contact VARCHAR(15),
    permanent_address_line1 VARCHAR(100),
    permanent_address_line2 VARCHAR(100),
    permanent_city VARCHAR(50),
    permanent_state VARCHAR(50),
    permanent_postal_code VARCHAR(10),
    local_guardian_name VARCHAR(100),
    local_guardian_mobile VARCHAR(15),
    local_guardian_address TEXT,
    course VARCHAR(100),
    year_of_study INT,
    department VARCHAR(100),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    blood_group VARCHAR(5),
    room_id VARCHAR(10),
    duration_of_stay INT,
    admission_date DATE,
    checkout_date DATE,
    student_status ENUM('active', 'inactive', 'graduated', 'transferred') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_auth(user_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES Room_Table(Room_ID)
);

-- Payment Table with detailed financial tracking
CREATE TABLE Payment_Table (
    Payment_ID VARCHAR(10) PRIMARY KEY,
    student_id VARCHAR(10) NOT NULL,
    payment_type ENUM('monthly_rent', 'security_deposit', 'mess_fee', 'penalty', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    penalty DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) GENERATED ALWAYS AS (amount + penalty) STORED,
    payment_method ENUM('cash', 'online', 'cheque', 'card') DEFAULT 'cash',
    transaction_id VARCHAR(100),
    payment_date DATE,
    due_date DATE,
    status ENUM('Pending', 'Paid', 'Overdue', 'Cancelled') DEFAULT 'Pending',
    admin_id VARCHAR(10),
    approved_by VARCHAR(10),
    approval_date TIMESTAMP NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES Student_Table(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES Admin_Table(Admin_ID),
    FOREIGN KEY (approved_by) REFERENCES Admin_Table(Admin_ID)
);

-- Visitor Table with comprehensive visitor management
CREATE TABLE Visitor_Table (
    Visitor_ID VARCHAR(10) PRIMARY KEY,
    student_id VARCHAR(10) NOT NULL,
    visitor_first_name VARCHAR(50) NOT NULL,
    visitor_last_name VARCHAR(50) NOT NULL,
    visitor_full_name VARCHAR(100) GENERATED ALWAYS AS (CONCAT(visitor_first_name, ' ', visitor_last_name)) STORED,
    visitor_mobile VARCHAR(15),
    visitor_email VARCHAR(100),
    visitor_address TEXT,
    relationship_with_student VARCHAR(50),
    id_proof_type ENUM('Aadhar', 'PAN', 'Driving_License', 'Passport', 'Other'),
    id_proof_number VARCHAR(50),
    visit_date DATE NOT NULL,
    time_in TIME NOT NULL,
    time_out TIME,
    purpose_of_visit TEXT,
    approved_by VARCHAR(10),
    visitor_status ENUM('checked_in', 'checked_out', 'overstayed') DEFAULT 'checked_in',
    security_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES Student_Table(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES Warden_Table(Warden_ID)
);

-- Complaint Table with detailed issue tracking
CREATE TABLE Complaint_Table (
    Complaint_ID VARCHAR(10) PRIMARY KEY,
    student_id VARCHAR(10) NOT NULL,
    category ENUM('Maintenance', 'Behavior', 'Plumbing', 'Electrical', 'Cleaning', 'Security', 
    'Food', 'Internet', 'Furniture', 'Other') NOT NULL,
    title VARCHAR(200),
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('Pending', 'Resolved', 'In_Progress', 'Closed', 'Rejected') DEFAULT 'Pending',
    complaint_date DATE NOT NULL,
    assigned_to VARCHAR(10),
    assigned_date TIMESTAMP NULL,
    resolution_date TIMESTAMP NULL,
    resolution_description TEXT,
    satisfaction_rating INT CHECK (satisfaction_rating BETWEEN 1 AND 5),
    follow_up_required BOOLEAN DEFAULT FALSE,
    estimated_cost DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES Student_Table(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES Warden_Table(Warden_ID)
);

-- Create indexes for better performance
CREATE INDEX idx_student_room ON Student_Table(room_id);
CREATE INDEX idx_payment_student ON Payment_Table(student_id);
CREATE INDEX idx_payment_status ON Payment_Table(status);
CREATE INDEX idx_visitor_student ON Visitor_Table(student_id);
CREATE INDEX idx_visitor_date ON Visitor_Table(visit_date);
CREATE INDEX idx_complaint_student ON Complaint_Table(student_id);
CREATE INDEX idx_complaint_status ON Complaint_Table(status);
CREATE INDEX idx_complaint_category ON Complaint_Table(category);
CREATE INDEX idx_room_status ON Room_Table(room_status);
CREATE INDEX idx_auth_username ON user_auth(username);
CREATE INDEX idx_auth_role ON user_auth(user_role);

-- Create triggers for automatic updates
DELIMITER //
CREATE TRIGGER update_room_occupancy_insert
AFTER INSERT ON Student_Table
FOR EACH ROW
BEGIN
    IF NEW.room_id IS NOT NULL THEN
        UPDATE Room_Table 
        SET occupied_count = occupied_count + 1,
            room_status = CASE 
                WHEN occupied_count + 1 >= capacity THEN 'occupied'
                ELSE 'available'
            END
        WHERE Room_ID = NEW.room_id;
    END IF;
END//

CREATE TRIGGER update_room_occupancy_update
AFTER UPDATE ON Student_Table
FOR EACH ROW
BEGIN
    IF OLD.room_id IS NOT NULL AND OLD.room_id != NEW.room_id THEN
        UPDATE Room_Table 
        SET occupied_count = occupied_count - 1,
            room_status = CASE 
                WHEN occupied_count - 1 = 0 THEN 'available'
                ELSE room_status
            END
        WHERE Room_ID = OLD.room_id;
    END IF;
    
    IF NEW.room_id IS NOT NULL AND OLD.room_id != NEW.room_id THEN
        UPDATE Room_Table 
        SET occupied_count = occupied_count + 1,
            room_status = CASE 
                WHEN occupied_count + 1 >= capacity THEN 'occupied'
                ELSE 'available'
            END
        WHERE Room_ID = NEW.room_id;
    END IF;
END//

CREATE TRIGGER update_room_occupancy_delete
AFTER DELETE ON Student_Table
FOR EACH ROW
BEGIN
    IF OLD.room_id IS NOT NULL THEN
        UPDATE Room_Table 
        SET occupied_count = occupied_count - 1,
            room_status = CASE 
                WHEN occupied_count - 1 = 0 THEN 'available'
                ELSE room_status
            END
        WHERE Room_ID = OLD.room_id;
    END IF;
END//
DELIMITER ;

-- Insert authentication data for sample users
INSERT INTO user_auth (username, password_hash, salt, user_role) VALUES
('admin', '$2y$10$example_hash_here', 'sample_salt', 'admin'),
('warden1', '$2y$10$example_hash_here', 'sample_salt', 'warden'),
('warden2', '$2y$10$example_hash_here', 'sample_salt', 'warden'),
('nimal', '$2y$10$example_hash_here', 'sample_salt', 'student'),
('kamal', '$2y$10$example_hash_here', 'sample_salt', 'student'),
('sunil', '$2y$10$example_hash_here', 'sample_salt', 'student');

-- Insert Admin data
INSERT INTO Admin_Table (Admin_ID, user_id, first_name, last_name, email, mobile_number, address_line1, city, state, postal_code) VALUES
('A01', 1, 'System', 'Administrator', 'admin@hostel.edu', '0112345678', '123 Admin Street', 'Colombo', 'Western', '10001');

-- Insert Warden data from your sample
INSERT INTO Warden_Table (Warden_ID, user_id, first_name, last_name, email, mobile_number, role) VALUES
('W01', 2, 'Sunil', 'Jayasinghe', 'sunil.warden@hostel.edu', '0112345678', 'Chief Warden'),
('W02', 3, 'Anoja', 'Perera', 'anoja.warden@hostel.edu', '0119876543', 'Assistant Warden');

-- Insert Room data from your sample
INSERT INTO Room_Table (Room_ID, room_number, room_type, ac_type, capacity, occupied_count, monthly_rent, security_deposit, warden_id) VALUES
('R201', '201', 'Single', 'AC', 2, 1, 8000.00, 5000.00, 'W01'),
('R202', '202', 'Double', 'NonAC', 2, 1, 6000.00, 3000.00, 'W01'),
('R203', '203', 'Single', 'AC', 2, 1, 8500.00, 5000.00, 'W01');

-- Insert Student data from your sample with enhanced attributes
INSERT INTO Student_Table (Student_ID, user_id, student_roll_number, first_name, last_name, email, mobile_number, room_id, duration_of_stay, admission_date, course, year_of_study, department, gender) VALUES
('S101', 4, 'CS2021101', 'Nimal', 'Perera', 'nimal@student.edu', '0712345678', 'R201', 12, '2024-01-15', 'Computer Science', 2, 'CSE', 'Male'),
('S102', 5, 'CS2021102', 'Kamal', 'Silva', 'kamal@student.edu', '0723456789', 'R202', 10, '2024-02-01', 'Information Technology', 2, 'IT', 'Male'),
('S103', 6, 'CS2021103', 'Sunil', 'Fernando', 'sunil@student.edu', '0709876543', 'R203', 8, '2024-03-01', 'Software Engineering', 1, 'SE', 'Male');

-- Insert Payment data from your sample
INSERT INTO Payment_Table (Payment_ID, student_id, payment_type, amount, penalty, status, admin_id, payment_date) VALUES
('P001', 'S101', 'monthly_rent', 12000.00, 0.00, 'Paid', 'A01', '2025-05-15'),
('P002', 'S102', 'monthly_rent', 13000.00, 500.00, 'Pending', 'A01', '2025-05-20');

-- Insert Visitor data from your sample with enhanced attributes
INSERT INTO Visitor_Table (Visitor_ID, student_id, visitor_first_name, visitor_last_name, visit_date, time_in, time_out, relationship_with_student) VALUES
('V001', 'S101', 'Mahesh', 'Kumar', '2025-06-01', '10:00:00', '10:30:00', 'Father'),
('V002', 'S102', 'Anjali', 'De Silva', '2025-06-02', '14:30:00', '15:00:00', 'Mother');

-- Insert Complaint data from your sample
INSERT INTO Complaint_Table (Complaint_ID, student_id, category, description, status, complaint_date, assigned_to) VALUES
('C001', 'S101', 'Maintenance', 'Leaking faucet in bathroom', 'Resolved', '2025-05-20', 'W01'),
('C002', 'S103', 'Behavior', 'Noisy neighbor disturbing sleep', 'Pending', '2025-05-22', 'W02');

-- Additional sample data for testing
INSERT INTO Payment_Table (Payment_ID, student_id, payment_type, amount, penalty, status, admin_id, payment_date) VALUES
('P003', 'S103', 'monthly_rent', 8500.00, 0.00, 'Paid', 'A01', '2025-05-10');

INSERT INTO Complaint_Table (Complaint_ID, student_id, category, description, status, complaint_date, assigned_to) VALUES
('C003', 'S102', 'Maintenance', 'Air conditioning not working properly', 'In_Progress', '2025-05-25', 'W01');

-- Create views for easier data access
CREATE VIEW student_room_details AS
SELECT 
    s.Student_ID,
    s.full_name,
    s.mobile_number,
    s.room_id,
    r.room_number,
    r.room_type,
    r.ac_type,
    r.monthly_rent,
    s.duration_of_stay
FROM Student_Table s
LEFT JOIN Room_Table r ON s.room_id = r.Room_ID;

CREATE VIEW payment_summary AS
SELECT 
    p.Payment_ID,
    s.full_name as student_name,
    p.amount,
    p.penalty,
    p.total_amount,
    p.status,
    p.payment_date
FROM Payment_Table p
JOIN Student_Table s ON p.student_id = s.Student_ID;

CREATE VIEW complaint_details AS
SELECT 
    c.Complaint_ID,
    s.full_name as student_name,
    c.category,
    c.description,
    c.status,
    c.complaint_date,
    w.first_name as warden_name
FROM Complaint_Table c
JOIN Student_Table s ON c.student_id = s.Student_ID
LEFT JOIN Warden_Table w ON c.assigned_to = w.Warden_ID;

CREATE VIEW visitor_log AS
SELECT 
    v.Visitor_ID,
    s.full_name as student_name,
    v.visitor_full_name,
    v.visit_date,
    v.time_in,
    v.time_out,
    v.relationship_with_student
FROM Visitor_Table v
JOIN Student_Table s ON v.student_id = s.Student_ID;
