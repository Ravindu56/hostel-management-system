<!-- Complaint Modal -->
<div id="complaintModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">File a Complaint</h2>
            <button class="modal-close" onclick="closeModal('complaintModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="complaintForm" action="submit_complaint.php" method="POST">
                <div class="form-group">
                    <label for="category" class="form-label">Category *</label>
                    <select name="category" id="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Plumbing">Plumbing</option>
                        <option value="Electrical">Electrical</option>
                        <option value="Cleaning">Cleaning</option>
                        <option value="Security">Security</option>
                        <option value="Food">Food</option>
                        <option value="Internet">Internet</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="title" class="form-label">Title *</label>
                    <input type="text" name="title" id="title" class="form-control" required maxlength="200">
                </div>
                <div class="form-group">
                    <label for="description" class="form-label">Description *</label>
                    <textarea name="description" id="description" rows="4" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="priority" class="form-label">Priority *</label>
                    <select name="priority" id="priority" class="form-control" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Complaint
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Visitor Modal -->
<div id="visitorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Register Visitor</h2>
            <button class="modal-close" onclick="closeModal('visitorModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="visitorForm" action="register_visitor.php" method="POST">
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="visitor_first_name" class="form-label">First Name *</label>
                            <input type="text" name="visitor_first_name" id="visitor_first_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="visitor_last_name" class="form-label">Last Name *</label>
                            <input type="text" name="visitor_last_name" id="visitor_last_name" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="visitor_mobile" class="form-label">Mobile Number *</label>
                    <input type="tel" name="visitor_mobile" id="visitor_mobile" class="form-control" required pattern="[0-9]{10}">
                </div>
                <div class="form-group">
                    <label for="visitor_email" class="form-label">Email</label>
                    <input type="email" name="visitor_email" id="visitor_email" class="form-control">
                </div>
                <div class="form-group">
                    <label for="relationship" class="form-label">Relationship *</label>
                    <select name="relationship" id="relationship" class="form-control" required>
                        <option value="">Select Relationship</option>
                        <option value="Father">Father</option>
                        <option value="Mother">Mother</option>
                        <option value="Brother">Brother</option>
                        <option value="Sister">Sister</option>
                        <option value="Friend">Friend</option>
                        <option value="Relative">Relative</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="visit_date" class="form-label">Visit Date *</label>
                            <input type="date" name="visit_date" id="visit_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="time_in" class="form-label">Expected Time In *</label>
                            <input type="time" name="time_in" id="time_in" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="purpose" class="form-label">Purpose of Visit</label>
                    <textarea name="purpose" id="purpose" rows="3" class="form-control" placeholder="Optional"></textarea>
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Register Visitor
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Update Profile</h2>
            <button class="modal-close" onclick="closeModal('profileModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="profileForm" action="update_profile.php" method="POST">
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="mobile_number" class="form-label">Mobile Number</label>
                            <input type="tel" name="mobile_number" id="mobile_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['mobile_number']); ?>">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['email']); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="parent_mobile" class="form-label">Parent Mobile</label>
                            <input type="tel" name="parent_mobile" id="parent_mobile" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['parent_mobile']); ?>">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="emergency_contact" class="form-label">Emergency Contact</label>
                            <input type="tel" name="emergency_contact" id="emergency_contact" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['emergency_contact']); ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="local_guardian_name" class="form-label">Local Guardian Name</label>
                    <input type="text" name="local_guardian_name" id="local_guardian_name" class="form-control" 
                           value="<?php echo htmlspecialchars($student['local_guardian_name']); ?>">
                </div>
                <div class="form-group">
                    <label for="local_guardian_mobile" class="form-label">Local Guardian Mobile</label>
                    <input type="tel" name="local_guardian_mobile" id="local_guardian_mobile" class="form-control" 
                           value="<?php echo htmlspecialchars($student['local_guardian_mobile']); ?>">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </div>
</div>
