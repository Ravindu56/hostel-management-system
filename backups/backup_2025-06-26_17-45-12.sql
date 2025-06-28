-- Database Backup Created: 2025-06-26 17:45:12



-- Table structure for `admin_table`
DROP TABLE IF EXISTS `admin_table`;
;

-- Data for table `admin_table`
INSERT INTO `admin_table` VALUES ('A01', '1', 'System', 'Administrator', 'admin@hostel.edu', '0112345678', '123 Admin Street', NULL, 'Colombo', 'Western', '10001', 'System Administrator', NULL, '2025-06-17 13:59:37');


-- Table structure for `complaint_details`
DROP TABLE IF EXISTS `complaint_details`;
;

-- Data for table `complaint_details`
INSERT INTO `complaint_details` VALUES ('C001', 'Nimal Perera', 'Maintenance', 'Leaking faucet in bathroom', 'Closed', '2025-05-20', 'Sunil');
INSERT INTO `complaint_details` VALUES ('C002', 'Sunil Fernando', 'Behavior', 'Noisy neighbor disturbing sleep', 'Resolved', '2025-05-22', 'Sunil');
INSERT INTO `complaint_details` VALUES ('C003', 'Kamal Silva', 'Maintenance', 'Air conditioning not working properly', 'In_Progress', '2025-05-25', 'Sunil');
INSERT INTO `complaint_details` VALUES ('C004', 'Nimal Perera', 'Cleaning', 'There is an washroom cleaning delay on wing b 2nd floor', 'Pending', '2025-06-22', NULL);


-- Table structure for `complaint_table`
DROP TABLE IF EXISTS `complaint_table`;
;

-- Data for table `complaint_table`
INSERT INTO `complaint_table` VALUES ('C001', 'S101', 'Maintenance', NULL, 'Leaking faucet in bathroom', 'medium', 'Closed', '2025-05-20', 'W01', NULL, NULL, '', NULL, '0', NULL, NULL, '2025-06-17 13:59:37', '2025-06-23 02:44:59');
INSERT INTO `complaint_table` VALUES ('C002', 'S103', 'Behavior', NULL, 'Noisy neighbor disturbing sleep', 'medium', 'Resolved', '2025-05-22', 'W01', NULL, '2025-06-23 01:49:38', '', NULL, '0', NULL, NULL, '2025-06-17 13:59:37', '2025-06-23 01:49:38');
INSERT INTO `complaint_table` VALUES ('C003', 'S102', 'Maintenance', NULL, 'Air conditioning not working properly', 'medium', 'In_Progress', '2025-05-25', 'W01', NULL, NULL, NULL, NULL, '0', NULL, NULL, '2025-06-17 13:59:37', '2025-06-17 13:59:37');
INSERT INTO `complaint_table` VALUES ('C004', 'S101', 'Cleaning', 'Washroom Cleaning', 'There is an washroom cleaning delay on wing b 2nd floor', 'medium', 'Pending', '2025-06-22', NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, '2025-06-22 22:47:40', '2025-06-22 22:47:40');


-- Table structure for `payment_summary`
DROP TABLE IF EXISTS `payment_summary`;
;

-- Data for table `payment_summary`
INSERT INTO `payment_summary` VALUES ('P001', 'Nimal Perera', '12000.00', '0.00', '12000.00', 'Paid', '2025-05-15');
INSERT INTO `payment_summary` VALUES ('P002', 'Kamal Silva', '13000.00', '500.00', '13500.00', 'Paid', '2025-05-20');
INSERT INTO `payment_summary` VALUES ('P003', 'Sunil Fernando', '8500.00', '0.00', '8500.00', 'Paid', '2025-05-10');
INSERT INTO `payment_summary` VALUES ('P004', 'Nayanaka Dayarathna', '1000.00', '0.00', '1000.00', 'Paid', NULL);
INSERT INTO `payment_summary` VALUES ('P005', 'Ashan  Kumara', '500.00', '0.00', '500.00', 'Pending', NULL);


-- Table structure for `payment_table`
DROP TABLE IF EXISTS `payment_table`;
;

-- Data for table `payment_table`
INSERT INTO `payment_table` VALUES ('P001', 'S101', 'monthly_rent', '12000.00', '0.00', '12000.00', 'cash', NULL, '2025-05-15', NULL, 'Paid', 'A01', NULL, NULL, NULL, '2025-06-17 13:59:37');
INSERT INTO `payment_table` VALUES ('P002', 'S102', 'monthly_rent', '13000.00', '500.00', '13500.00', 'cash', NULL, '2025-05-20', NULL, 'Paid', 'A01', 'A01', '2025-06-22 19:18:33', NULL, '2025-06-17 13:59:37');
INSERT INTO `payment_table` VALUES ('P003', 'S103', 'monthly_rent', '8500.00', '0.00', '8500.00', 'cash', NULL, '2025-05-10', NULL, 'Paid', 'A01', NULL, NULL, NULL, '2025-06-17 13:59:37');
INSERT INTO `payment_table` VALUES ('P004', 'S004', 'monthly_rent', '1000.00', '0.00', '1000.00', 'cash', NULL, NULL, '2025-07-03', 'Paid', 'A01', 'A01', '2025-06-24 21:41:32', '', '2025-06-22 22:49:27');
INSERT INTO `payment_table` VALUES ('P005', 'S006', 'monthly_rent', '500.00', '0.00', '500.00', 'cash', NULL, NULL, '2025-07-03', 'Pending', 'A01', NULL, NULL, '', '2025-06-26 20:50:04');


-- Table structure for `room_table`
DROP TABLE IF EXISTS `room_table`;
;

-- Data for table `room_table`
INSERT INTO `room_table` VALUES ('306', '306', 'Double', 'AC', '4', '1', '2', 'B', '1000.00', '200.00', NULL, 'available', 'W01', '2025-06-25 14:07:23');
INSERT INTO `room_table` VALUES ('R201', '201', 'Single', 'AC', '2', '0', NULL, NULL, '3000.00', '500.00', NULL, 'occupied', 'W01', '2025-06-17 13:59:37');
INSERT INTO `room_table` VALUES ('R202', '202', 'Double', 'NonAC', '2', '2', NULL, NULL, '6000.00', '3000.00', NULL, 'occupied', 'W01', '2025-06-17 13:59:37');
INSERT INTO `room_table` VALUES ('R203', '203', 'Single', 'AC', '2', '2', NULL, NULL, '8500.00', '5000.00', NULL, 'occupied', 'W01', '2025-06-17 13:59:37');
INSERT INTO `room_table` VALUES ('R305', '305', 'Double', '', '4', '1', '2', 'B', '1000.00', '200.00', NULL, 'available', 'W01', '2025-06-25 01:44:49');


-- Table structure for `student_room_details`
DROP TABLE IF EXISTS `student_room_details`;
;

-- Data for table `student_room_details`
INSERT INTO `student_room_details` VALUES ('S004', 'Nayanaka Dayarathna', '0705625156', '306', '306', 'Double', 'AC', '1000.00', NULL);
INSERT INTO `student_room_details` VALUES ('S005', 'S sdsd', '0745612563', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `student_room_details` VALUES ('S006', 'Ashan  Kumara', '0741236854', 'R305', '305', 'Double', '', '1000.00', NULL);
INSERT INTO `student_room_details` VALUES ('S101', 'Nimal Perera', '0712345678', 'R201', '201', 'Single', 'AC', '3000.00', '12');
INSERT INTO `student_room_details` VALUES ('S102', 'Kamal Silva', '0723456789', 'R202', '202', 'Double', 'NonAC', '6000.00', '10');
INSERT INTO `student_room_details` VALUES ('S103', 'Sunil Fernando', '0709876543', 'R203', '203', 'Single', 'AC', '8500.00', '8');


-- Table structure for `student_table`
DROP TABLE IF EXISTS `student_table`;
;

-- Data for table `student_table`
INSERT INTO `student_table` VALUES ('S004', '7', '2022/E/033', 'Nayanaka', 'Dayarathna', 'Nayanaka Dayarathna', 'raveest56@gmail.com', '0705625156', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Engineering', '3', 'Computer Engineering', NULL, 'Male', NULL, '306', NULL, '2025-06-22', NULL, 'active', '2025-06-22 16:10:35');
INSERT INTO `student_table` VALUES ('S005', '9', '1111111', 'S', 'sdsd', 'S sdsd', 'pasindud@student.edu', '0745612563', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Engineering', '3', 'Computer Engineering', NULL, 'Male', NULL, NULL, NULL, '2025-06-26', NULL, 'active', '2025-06-26 00:29:03');
INSERT INTO `student_table` VALUES ('S006', '12', '2020/E/012', 'Ashan ', 'Kumara', 'Ashan  Kumara', 'ashan1@student.edu', '0741236854', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Engineering', '2', 'Computer Engineering', NULL, 'Male', NULL, 'R305', NULL, NULL, NULL, 'active', '2025-06-26 20:29:09');
INSERT INTO `student_table` VALUES ('S101', '4', 'CS2021101', 'Nimal', 'Perera', 'Nimal Perera', 'nimal@student.edu', '0712345678', '0775623172', '0775623172', NULL, NULL, NULL, NULL, NULL, 'Siripala', '0775623172', NULL, 'Computer Science', '2', 'CSE', NULL, 'Male', NULL, 'R201', '12', '2024-01-15', NULL, 'active', '2025-06-17 13:59:37');
INSERT INTO `student_table` VALUES ('S102', '5', 'CS2021102', 'Kamal', 'Silva', 'Kamal Silva', 'kamal@student.edu', '0723456789', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Information Technology', '2', 'IT', NULL, 'Male', NULL, 'R202', '10', '2024-02-01', NULL, 'active', '2025-06-17 13:59:37');
INSERT INTO `student_table` VALUES ('S103', '6', 'CS2021103', 'Sunil', 'Fernando', 'Sunil Fernando', 'sunil@student.edu', '0709876543', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Software Engineering', '1', 'SE', NULL, 'Male', NULL, 'R203', '8', '2024-03-01', NULL, 'active', '2025-06-17 13:59:37');


-- Table structure for `system_announcements`
DROP TABLE IF EXISTS `system_announcements`;
;

-- Data for table `system_announcements`
INSERT INTO `system_announcements` VALUES ('1', 'Test01', 'This annocement for test cases ', 'all', '1', '2025-06-26 18:46:21', '1');


-- Table structure for `user_auth`
DROP TABLE IF EXISTS `user_auth`;
;

-- Data for table `user_auth`
INSERT INTO `user_auth` VALUES ('1', 'admin', '$2y$10$example_hash_here', 'sample_salt', 'bcrypt', 'admin', '1', '2025-06-17 13:59:37', '2025-06-26 20:55:39', '0', NULL);
INSERT INTO `user_auth` VALUES ('2', 'warden1', '$2y$10$example_hash_here', 'sample_salt', 'bcrypt', 'warden', '1', '2025-06-17 13:59:37', '2025-06-26 20:30:39', '0', NULL);
INSERT INTO `user_auth` VALUES ('3', 'warden2', '$2y$10$example_hash_here', 'sample_salt', 'bcrypt', 'warden', '1', '2025-06-17 13:59:37', NULL, '0', NULL);
INSERT INTO `user_auth` VALUES ('4', 'nimal', '$2y$10$example_hash_here', 'sample_salt', 'bcrypt', 'student', '1', '2025-06-17 13:59:37', '2025-06-26 20:55:15', '0', NULL);
INSERT INTO `user_auth` VALUES ('5', 'kamal', '$2y$10$example_hash_here', 'sample_salt', 'bcrypt', 'student', '1', '2025-06-17 13:59:37', NULL, '0', NULL);
INSERT INTO `user_auth` VALUES ('6', 'sunil', '$2y$10$example_hash_here', 'sample_salt', 'bcrypt', 'student', '1', '2025-06-17 13:59:37', NULL, '0', NULL);
INSERT INTO `user_auth` VALUES ('7', 'Nayanaka', '$2y$10$N5.clIeNWO0myDxIr0EKH.H.UYuXW0XNV12RsmDPTsQyM9GJLyYRO', '1809e4ccf7223c217a10c5187fced209', 'bcrypt', 'student', '1', '2025-06-22 16:10:35', '2025-06-22 16:10:49', '0', NULL);
INSERT INTO `user_auth` VALUES ('8', 'Pasindu', '$2y$10$MAJQVt2ij9Pb8Y8OtQyv3e3t2T.CLhjr4HcicSPGMTAHeU/hWAk6q', 'fd8202207e3415ada1c1bf19e60d9ffc', 'bcrypt', 'warden', '1', '2025-06-23 15:08:35', '2025-06-23 15:08:47', '1', NULL);
INSERT INTO `user_auth` VALUES ('9', 'fff', '$2y$10$vnSf3ZdtP6h9dbyU6vJOquaAL/8QkqQXTl3ngZV/D9noUIrL9nkDy', '5bb90a022883e5bcc57b3de51a8ae6c5', 'bcrypt', 'student', '0', '2025-06-26 00:29:03', '2025-06-26 00:30:36', '0', NULL);
INSERT INTO `user_auth` VALUES ('10', 'bhanuka', '$2y$10$cXgiKXvxIL1J7DNs7ENHm.SSogWjGQh18sHNX1PXNsHbUwbHClXK2', '368cff112f3269b4e83daecebb7b223f', 'bcrypt', 'warden', '1', '2025-06-26 00:30:12', NULL, '0', NULL);
INSERT INTO `user_auth` VALUES ('12', 'ashan', '$2y$10$4U1FR.LOY9UAI.Xw46B4oOLAwsLnbgVz1fEfjO5zxdjY0GqSJbIu2', '7e832f004739356c96fbc37d065434b3', 'bcrypt', 'student', '1', '2025-06-26 20:29:09', NULL, '0', NULL);


-- Table structure for `visitor_log`
DROP TABLE IF EXISTS `visitor_log`;
;

-- Data for table `visitor_log`
INSERT INTO `visitor_log` VALUES ('V001', 'Nimal Perera', 'Mahesh Kumar', '2025-06-01', '10:00:00', '22:23:03', 'Father');
INSERT INTO `visitor_log` VALUES ('V002', 'Kamal Silva', 'Anjali De Silva', '2025-06-02', '14:30:00', '22:22:51', 'Mother');
INSERT INTO `visitor_log` VALUES ('V003', 'Nimal Perera', 'Sadish Liyanage', '2025-07-01', '19:00:00', '23:52:34', 'Friend');
INSERT INTO `visitor_log` VALUES ('V004', 'Nimal Perera', 'Sahan Shashin', '2025-07-04', '10:00:00', NULL, 'Brother');


-- Table structure for `visitor_table`
DROP TABLE IF EXISTS `visitor_table`;
;

-- Data for table `visitor_table`
INSERT INTO `visitor_table` VALUES ('V001', 'S101', 'Mahesh', 'Kumar', 'Mahesh Kumar', NULL, NULL, NULL, 'Father', NULL, NULL, '2025-06-01', '10:00:00', '22:23:03', NULL, 'W01', 'checked_out', NULL, '2025-06-17 13:59:37');
INSERT INTO `visitor_table` VALUES ('V002', 'S102', 'Anjali', 'De Silva', 'Anjali De Silva', NULL, NULL, NULL, 'Mother', NULL, NULL, '2025-06-02', '14:30:00', '22:22:51', NULL, 'W01', 'checked_out', NULL, '2025-06-17 13:59:37');
INSERT INTO `visitor_table` VALUES ('V003', 'S101', 'Sadish', 'Liyanage', 'Sadish Liyanage', '0745124512', 'sadish@student.edu', NULL, 'Friend', NULL, NULL, '2025-07-01', '19:00:00', '23:52:34', '', 'W01', 'checked_out', NULL, '2025-06-22 22:42:20');
INSERT INTO `visitor_table` VALUES ('V004', 'S101', 'Sahan', 'Shashin', 'Sahan Shashin', '0712354623', 'sahanshash@gmail.com', NULL, 'Brother', NULL, NULL, '2025-07-04', '10:00:00', NULL, '', NULL, 'checked_in', NULL, '2025-06-26 18:43:50');


-- Table structure for `warden_table`
DROP TABLE IF EXISTS `warden_table`;
;

-- Data for table `warden_table`
INSERT INTO `warden_table` VALUES ('W01', '2', 'Sunil', 'Jayasinghe', 'sunil.warden@hostel.edu', '0112345678', NULL, NULL, NULL, NULL, NULL, NULL, 'Chief Warden', NULL, NULL, '2025-06-17 13:59:37');
INSERT INTO `warden_table` VALUES ('W02', '3', 'Anoja', 'Perera', 'anoja.warden@hostel.edu', '0119876543', NULL, NULL, NULL, NULL, NULL, NULL, 'Assistant Warden', NULL, NULL, '2025-06-17 13:59:37');
INSERT INTO `warden_table` VALUES ('W03', '8', 'Pasindu', 'Danajaya', 'pasindu@student.edu', '0741225638', NULL, NULL, NULL, NULL, NULL, NULL, 'Chief Warden', 'Full Time', NULL, '2025-06-23 15:08:35');
INSERT INTO `warden_table` VALUES ('W04', '10', 'Ravindu', 'Bhanuka', 'ravindubhanuka@student.edu', '0751245896', NULL, NULL, NULL, NULL, NULL, NULL, 'Chief Warden', 'Full Time', NULL, '2025-06-26 00:30:12');
