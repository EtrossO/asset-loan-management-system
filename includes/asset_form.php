<?php
// Reusable asset form fields (included by assets.php for add/edit)
// Expected variables: $asset (array or null for add mode)
$asset = $asset ?? null;
$is_edit = $asset !== null;
?>
<div class="row g-3">
    <div class="col-md-4">
        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
        <select class="form-select" id="category" name="category" required>
            <option value="">Select category...</option>
            <?php foreach (asset_categories() as $cat): ?>
                <option value="<?= e($cat) ?>" <?= ($asset['category'] ?? '') === $cat ? 'selected' : '' ?>>
                    <?= e($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label for="brand" class="form-label">Brand</label>
        <select class="form-select" id="brand" name="brand">
            <option value="">Select brand...</option>
            <?php foreach (asset_brands() as $br): ?>
                <option value="<?= e($br) ?>" <?= ($asset['brand'] ?? '') === $br ? 'selected' : '' ?>>
                    <?= e($br) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label for="asset_tag" class="form-label">Asset Tag <span class="text-danger">*</span> <small class="text-muted">(auto)</small></label>
        <input type="text" class="form-control" id="asset_tag" name="asset_tag"
               value="<?= e($asset['asset_tag'] ?? '') ?>" 
               <?= !$is_edit ? 'readonly' : '' ?> 
               required>
        <?php if (!$is_edit): ?>
        <small class="text-muted">Auto-generated from category &amp; brand</small>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <label for="name" class="form-label">Name / Specs <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name"
               value="<?= e($asset['name'] ?? '') ?>" 
               placeholder="e.g. EliteBook 840 G10, 14 inch, Intel i7, 16GB RAM"
               required>
        <small class="text-muted">Model name and specifications</small>
    </div>
    <div class="col-md-3">
        <label for="model" class="form-label">Model</label>
        <input type="text" class="form-control" id="model" name="model"
               value="<?= e($asset['model'] ?? '') ?>"
               placeholder="e.g. 840 G10">
    </div>
    <div class="col-md-3">
        <label for="serial_no" class="form-label">Serial Number</label>
        <input type="text" class="form-control" id="serial_no" name="serial_no"
               value="<?= e($asset['serial_no'] ?? '') ?>"
               placeholder="e.g. CN12345678">
    </div>
    <?php if ($asset): ?>
    <div class="col-md-4">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status">
            <option value="available" <?= ($asset['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
            <option value="on_loan" <?= ($asset['status'] ?? '') === 'on_loan' ? 'selected' : '' ?>>On Loan</option>
            <option value="under_repair" <?= ($asset['status'] ?? '') === 'under_repair' ? 'selected' : '' ?>>Under Repair</option>
            <option value="retired" <?= ($asset['status'] ?? '') === 'retired' ? 'selected' : '' ?>>Retired</option>
        </select>
    </div>
    <?php endif; ?>
    <div class="col-12">
        <label for="notes" class="form-label">Notes</label>
        <textarea class="form-control" id="notes" name="notes" rows="2"><?= e($asset['notes'] ?? '') ?></textarea>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var categorySelect = document.getElementById('category');
    var brandSelect = document.getElementById('brand');
    var assetTagInput = document.getElementById('asset_tag');
    var nameInput = document.getElementById('name');
    var isEditMode = <?= $is_edit ? 'true' : 'false' ?>;
    
    function generateAssetTag() {
        if (isEditMode) return;
        
        var category = categorySelect.value;
        var brand = brandSelect.value;
        
        if (!category) {
            assetTagInput.value = '';
            return;
        }
        
        fetch('assets.php?ajax=generate_tag&category=' + encodeURIComponent(category) + '&brand=' + encodeURIComponent(brand))
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.tag) {
                    assetTagInput.value = data.tag;
                }
            })
            .catch(function(error) {
                console.error('Error generating tag:', error);
            });
    }
    
    categorySelect.addEventListener('change', generateAssetTag);
    brandSelect.addEventListener('change', generateAssetTag);
    
    if (nameInput) {
        nameInput.addEventListener('blur', function() {
            this.value = this.value.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
        });
    }
});
</script>
