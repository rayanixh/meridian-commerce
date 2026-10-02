<?php
require_once __DIR__ . '/../config/config.php';
require_admin();
require_once __DIR__ . '/_layout.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$product = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
    $category_id = (int)($_POST['category_id'] ?? 0) ?: null;
    $short = trim($_POST['short_description'] ?? '');
    $desc = $_POST['description'] ?? '';
    $specs = $_POST['specifications'] ?? '';
    $price = (float)($_POST['price'] ?? 0);
    $sale = $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
    $stock = (int)($_POST['stock'] ?? 0);
    $threshold = (int)($_POST['low_stock_threshold'] ?? 5);
    $weight = $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
    $brand = trim($_POST['brand'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_desc = trim($_POST['seo_description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
    $is_bestseller = !empty($_POST['is_bestseller']) ? 1 : 0;
    $is_new = !empty($_POST['is_new']) ? 1 : 0;
    $main_image = $_POST['main_image'] ?? '';
    $gallery = $_POST['gallery'] ?? '[]';
    $variants = $_POST['variants'] ?? '[]';

    if (!$name) $errors['name'] = 'Name is required';
    if (!is_numeric($_POST['price'] ?? '')) $errors['price'] = 'Price required';
    if (!$sku) $sku = 'MC-' . strtoupper(substr(md5($slug . microtime(true)), 0, 6));

    $galleryArr = [];
    try { $galleryArr = json_decode($gallery, true) ?: []; } catch (Throwable $e) {}
    $galleryStr = json_encode(array_values(array_filter($galleryArr)));
    $variantGroups = [];
    try { $variantGroups = json_decode($variants, true) ?: []; } catch (Throwable $e) {}

    if (!$errors) {
        try {
            if ($id > 0) {
                $pdo->prepare("UPDATE products SET name=?, slug=?, sku=?, category_id=?, short_description=?, description=?, specifications=?, price=?, sale_price=?, stock=?, low_stock_threshold=?, weight=?, brand=?, tags=?, seo_title=?, seo_description=?, main_image=?, gallery=?, status=?, is_featured=?, is_bestseller=?, is_new=?, updated_at=NOW() WHERE id=?")
                    ->execute([$name, $slug, $sku, $category_id, $short, $desc, $specs, $price, $sale, $stock, $threshold, $weight, $brand, $tags, $seo_title, $seo_desc, $main_image, $galleryStr, $status, $is_featured, $is_bestseller, $is_new, $id]);
            } else {
                $pdo->prepare("INSERT INTO products (name, slug, sku, category_id, short_description, description, specifications, price, sale_price, stock, low_stock_threshold, weight, brand, tags, seo_title, seo_description, main_image, gallery, status, is_featured, is_bestseller, is_new) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$name, $slug, $sku, $category_id, $short, $desc, $specs, $price, $sale, $stock, $threshold, $weight, $brand, $tags, $seo_title, $seo_desc, $main_image, $galleryStr, $status, $is_featured, $is_bestseller, $is_new]);
                $id = (int)$pdo->lastInsertId();
            }
            $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$id]);
            if ($variantGroups) {
                $ins = $pdo->prepare("INSERT INTO product_variants (product_id, name, value, stock) VALUES (?, ?, ?, ?)");
                foreach ($variantGroups as $g) {
                    if (empty($g['name']) || empty($g['values'])) continue;
                    foreach ((array)$g['values'] as $v) $ins->execute([$id, $g['name'], $v, $stock]);
                }
            }
            redirect(base_url('admin/product-edit.php?id=' . $id . '&saved=1'));
        } catch (Throwable $e) {
            $errors['db'] = 'Could not save: ' . $e->getMessage();
        }
    }
}

$cats = $pdo->query("SELECT * FROM categories ORDER BY position ASC")->fetchAll();
$variantGroups = [];
if ($product) {
    $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id");
    $stmt->execute([$id]);
    foreach ($stmt as $v) $variantGroups[$v['name']][] = $v['value'];
    $variantGroups = array_map(fn($name, $values) => ['name' => $name, 'values' => $values], array_keys($variantGroups), $variantGroups);
}
$galleryArr = [];
if (!empty($product['gallery'])) {
    $galleryArr = json_decode($product['gallery'], true) ?: [];
}
$saved = !empty($_GET['saved']);

admin_header($id ? 'Edit product' : 'Add product', 'products.php');
?>
<?php if ($saved): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<?php if (!empty($errors['db'])): ?><div class="alert alert-error"><?= e($errors['db']) ?></div><?php endif; ?>

<form method="post" data-autosave>
    <?= csrf_field() ?>
    <div class="grid-2">
        <div class="card-block">
            <h2>Basic</h2>
            <div class="form-field" style="margin-bottom:14px"><label>Name *</label><input type="text" name="name" required value="<?= e($product['name'] ?? '') ?>"></div>
            <div class="form-row">
                <div class="form-field"><label>SKU</label><input type="text" name="sku" value="<?= e($product['sku'] ?? '') ?>" placeholder="Auto-generated if empty"></div>
                <div class="form-field"><label>Slug</label><input type="text" name="slug" value="<?= e($product['slug'] ?? '') ?>" placeholder="Auto from name"></div>
            </div>
            <div class="form-field" style="margin-bottom:14px"><label>Category</label>
                <select name="category_id">
                    <option value="">—</option>
                    <?php foreach ($cats as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ($product['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field" style="margin-bottom:14px"><label>Short description</label><textarea name="short_description" rows="2"><?= e($product['short_description'] ?? '') ?></textarea></div>
            <div class="form-field" style="margin-bottom:14px"><label>Description</label><textarea name="description" rows="6"><?= e($product['description'] ?? '') ?></textarea></div>
            <div class="form-field" style="margin-bottom:14px"><label>Specifications</label><textarea name="specifications" rows="4"><?= e($product['specifications'] ?? '') ?></textarea></div>
            <h2 style="margin-top:18px;font-size:14px">SEO</h2>
            <div class="form-field" style="margin-bottom:14px"><label>SEO title</label><input type="text" name="seo_title" value="<?= e($product['seo_title'] ?? '') ?>"></div>
            <div class="form-field" style="margin-bottom:0"><label>SEO description</label><textarea name="seo_description" rows="2"><?= e($product['seo_description'] ?? '') ?></textarea></div>
        </div>

        <div>
            <div class="card-block">
                <h2>Pricing & stock</h2>
                <div class="form-row-3">
                    <div class="form-field"><label>Regular price</label><input type="number" step="0.01" min="0" name="price" value="<?= e($product['price'] ?? '0') ?>"></div>
                    <div class="form-field"><label>Sale price</label><input type="number" step="0.01" min="0" name="sale_price" value="<?= e($product['sale_price'] ?? '') ?>"></div>
                    <div class="form-field"><label>Stock</label><input type="number" min="0" name="stock" value="<?= e($product['stock'] ?? '0') ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-field"><label>Low stock threshold</label><input type="number" min="0" name="low_stock_threshold" value="<?= e($product['low_stock_threshold'] ?? '5') ?>"></div>
                    <div class="form-field"><label>Weight (kg)</label><input type="number" step="0.01" min="0" name="weight" value="<?= e($product['weight'] ?? '') ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-field"><label>Brand</label><input type="text" name="brand" value="<?= e($product['brand'] ?? '') ?>"></div>
                    <div class="form-field"><label>Tags (comma separated)</label><input type="text" name="tags" value="<?= e($product['tags'] ?? '') ?>"></div>
                </div>
            </div>

            <div class="card-block">
                <h2>Variants</h2>
                <p style="font-size:13px;color:var(--c-muted);margin-bottom:14px">Add custom attribute types like Color, Size, Storage.</p>
                <div data-variant-editor>
                    <input type="hidden" name="variants" value='<?= e(json_encode($variantGroups)) ?>'>
                    <div data-variant-list></div>
                    <button type="button" class="btn btn-ghost" data-add-variant style="margin-top:8px">+ Add variant type</button>
                </div>
            </div>

            <div class="card-block">
                <h2>Visibility & status</h2>
                <div class="form-field" style="margin-bottom:14px"><label>Status</label>
                    <select name="status">
                        <option value="active" <?= ($product['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="draft" <?= ($product['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
                <div class="form-field"><label class="checkbox"><input type="checkbox" name="is_featured" value="1" <?= !empty($product['is_featured']) ? 'checked' : '' ?>> Featured</label></div>
                <div class="form-field"><label class="checkbox"><input type="checkbox" name="is_bestseller" value="1" <?= !empty($product['is_bestseller']) ? 'checked' : '' ?>> Bestseller</label></div>
                <div class="form-field"><label class="checkbox"><input type="checkbox" name="is_new" value="1" <?= !empty($product['is_new']) ? 'checked' : '' ?>> New arrival</label></div>
            </div>
        </div>
    </div>

    <div class="card-block">
        <h2>Images</h2>
        <div class="grid-2">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;margin-bottom:8px">Main image</label>
                <div class="uploader" data-field="image" style="max-width:240px;margin:0 auto">
                    <input type="file" accept="image/jpeg,image/png,image/webp">
                    <input type="hidden" name="main_image" value="<?= e($product['main_image'] ?? '') ?>">
                    <?php if (!empty($product['main_image'])): ?>
                        <img src="<?= e(APP_URL . '/' . $product['main_image']) ?>" class="uploader-preview">
                    <?php else: ?>
                        <div class="uploader-empty">
                            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <div>Click to upload</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;margin-bottom:8px">Gallery</label>
                <div data-multi-uploader data-field="gallery">
                    <div class="uploader">
                        <input type="file" accept="image/jpeg,image/png,image/webp" multiple>
                        <input type="hidden" name="gallery" value='<?= e(json_encode(array_map(fn($g) => APP_URL . '/' . $g, $galleryArr))) ?>'>
                        <div class="uploader-empty">
                            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            <div>Add multiple images</div>
                        </div>
                    </div>
                    <div class="thumbs-grid" style="margin-top:12px"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="products.php" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary"><?= $id ? 'Save changes' : 'Create product' ?></button>
    </div>
</form>
<?php admin_footer(); ?>
