<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('admin');

$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$params = [];
$where = "1=1";
if ($search) {
    $where .= " AND (action LIKE ? OR description LIKE ? OR entity_type LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countSql = "SELECT COUNT(*) FROM audit_logs WHERE $where";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();

$sql = "SELECT a.*, u.full_name as user_name 
        FROM audit_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE $where 
        ORDER BY a.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = 'Audit Logs';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Audit Logs</h2>
            <?php echo displayAlerts(); ?>
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" placeholder="Search by action, description, entity" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100">Search</button>
                </div>
            </form>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Description</th>
                        <th>IP</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['entity_type']); ?> #<?php echo $log['entity_id']; ?></td>
                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                            <td><?php echo $log['ip_address']; ?></td>
                            <td><?php echo formatDateTime($log['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination -->
            <?php if ($total > $limit): ?>
                <nav>
                    <ul class="pagination">
                        <?php for ($i=1; $i<=ceil($total/$limit); $i++): ?>
                            <li class="page-item <?php if($i==$page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>