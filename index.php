<?php session_start();?>
<?php require __DIR__ . '/header.php'; ?>

<div class="container mt-3">
    <?php require __DIR__ . '/navbar.php'; ?>
    <div class="row">
        <div class="col-lg-3">
            <h3 class="mb-4">Categories</h3>
            <?php require_once "./categoryDisplay.php" ?>
        </div>
        <div class="col-lg-9">
            <h3 class="mb-4">Products</h3>
            <div class="mb-3">
                <label for="sort">Sort by Price:</label>
                <?php if(isset($_GET['category_id'])): ?>
                    <a href="?category_id=<?php echo $_GET['category_id']; ?>&sort=asc" class="btn btn-link">Low to High</a>
                    <a href="?category_id=<?php echo $_GET['category_id']; ?>&sort=desc" class="btn btn-link">High to Low</a>
                <?php else: ?>
                    <a href="?sort=asc" class="btn btn-link">Low to High</a>
                    <a href="?sort=desc" class="btn btn-link">High to Low</a>
                <?php endif; ?>
            </div>
            <?php require_once __DIR__ . '/productDisplay.php'; ?>
        </div>
    </div>
</div>

</body>

<?php require __DIR__ . '/footer.php'; ?>
