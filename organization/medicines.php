<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('organization');

$user = getCurrentUser();
$stmt = $pdo->prepare("SELECT * FROM organizations WHERE user_id = ?");
$stmt->execute([$user['id']]);
$org = $stmt->fetch();
if ($org['status'] != 'approved') {
    $_SESSION['error'] = 'Organization not approved.';
    header('Location: ../index.php');
    exit;
}

$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$city = $_GET['city'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$params = [];
$where = "m.verification_status = 'verified' AND m.expiry_status = 'valid' AND i.available_quantity > 0";
if ($search) {
    $where .= " AND m.name LIKE ?";
    $params[] = "%$search%";
}
if ($type) {
    $where .= " AND m.type = ?";
    $params[] = $type;
}
if ($city) {
    $where .= " AND u.city LIKE ?";
    $params[] = "%$city%";
}

// Count total
$countSql = "SELECT COUNT(*) as total FROM medicines m 
             JOIN inventory i ON m.id = i.medicine_id 
             JOIN users u ON m.donor_id = u.id 
             WHERE $where";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();

// Fetch data
$sql = "SELECT m.*, i.available_quantity, u.city as donor_city 
        FROM medicines m 
        JOIN inventory i ON m.id = i.medicine_id 
        JOIN users u ON m.donor_id = u.id 
        WHERE $where 
        ORDER BY m.expiry_date ASC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

$pageTitle = 'Search Medicines';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Available Medicines</h2>
            <?php echo displayAlerts(); ?>
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="Tablet" <?php if($type=='Tablet') echo 'selected'; ?>>Tablet</option>
                        <option value="Capsule" <?php if($type=='Capsule') echo 'selected'; ?>>Capsule</option>
                        <option value="Syrup" <?php if($type=='Syrup') echo 'selected'; ?>>Syrup</option>
                        <option value="Cream" <?php if($type=='Cream') echo 'selected'; ?>>Cream</option>
                        <option value="Drops" <?php if($type=='Drops') echo 'selected'; ?>>Drops</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="city" class="form-control" placeholder="Donor City" value="<?php echo htmlspecialchars($city); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Search</button>
                </div>
            </form>

            <?php if (empty($medicines)): ?>
                <div class="alert alert-info">No medicines available matching your criteria.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($medicines as $m): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h5><?php echo htmlspecialchars($m['name']); ?></h5>
                                    <p>
                                        <strong>Type:</strong> <?php echo $m['type']; ?><br>
                                        <strong>Available:</strong> <?php echo $m['available_quantity']; ?> <?php echo $m['unit']; ?><br>
                                        <strong>Expiry:</strong> <?php echo formatDate($m['expiry_date']); ?><br>
                                        <strong>Donor City:</strong> <?php echo htmlspecialchars($m['donor_city']); ?>
                                    </p>
                                    <a href="medicine-details.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <a href="request-medicine.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-success">Request</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Pagination -->
                <?php if ($total > $limit): ?>
                    <nav>
                        <ul class="pagination">
                            <?php for ($i=1; $i<=ceil($total/$limit); $i++): ?>
                                <li class="page-item <?php if($i==$page) echo 'active'; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&city=<?php echo urlencode($city); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>