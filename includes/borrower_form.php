<?php
// Reusable borrower form fields
$borrower = $borrower ?? null;
?>
<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name"
               value="<?= e($borrower['name'] ?? '') ?>" required>
    </div>
    <div class="col-md-6">
        <label for="id_number" class="form-label">Staff / Student ID</label>
        <input type="text" class="form-control" id="id_number" name="id_number"
               value="<?= e($borrower['id_number'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label for="department" class="form-label">Department</label>
        <input type="text" class="form-control" id="department" name="department"
               value="<?= e($borrower['department'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label for="phone" class="form-label">Phone</label>
        <input type="text" class="form-control" id="phone" name="phone"
               value="<?= e($borrower['phone'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email"
               value="<?= e($borrower['email'] ?? '') ?>">
    </div>
</div>
