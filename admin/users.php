<?php
$currentPage = 'admin_users';

require_once 'php/admin_session_check.php';
require_once '../config/database.php';

// Get filter parameters
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
$role_filter = filter_input(INPUT_GET, 'role', FILTER_SANITIZE_STRING);
$sort_by = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING) ?: 'created';

// Get overall user statistics
$user_stats = [
    'total_users' => 0,
    'total_students' => 0,
    'total_teachers' => 0,
    'total_admins' => 0,
    'active_users' => 0
];

try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $user_stats['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total teachers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
    $user_stats['total_teachers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total admins
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $user_stats['total_admins'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active users (users who have logged in or have activity)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM quiz_attempts");
    $user_stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (PDOException $e) {
    error_log("Error fetching user stats: " . $e->getMessage());
}

// Build users query
$sql = "SELECT 
            u.user_id,
            u.username,
            u.full_name,
            u.email,
            u.role,
            u.created_at,
            COUNT(qa.attempt_id) as quiz_attempts,
            MAX(qa.created_at) as last_activity
        FROM users u
        LEFT JOIN quiz_attempts qa ON u.user_id = qa.user_id
        WHERE 1=1";

$params = [];

// Add search filter
if ($search) {
    $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Add role filter
if ($role_filter) {
    $sql .= " AND u.role = ?";
    $params[] = $role_filter;
}

$sql .= " GROUP BY u.user_id";

// Add sorting
switch ($sort_by) {
    case 'name':
        $sql .= " ORDER BY u.full_name, u.username";
        break;
    case 'username':
        $sql .= " ORDER BY u.username";
        break;
    case 'role':
        $sql .= " ORDER BY u.role, u.username";
        break;
    case 'activity':
        $sql .= " ORDER BY last_activity DESC";
        break;
    case 'created':
    default:
        $sql .= " ORDER BY u.created_at DESC";
}

$users = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

// Get recent user registrations
$recent_registrations = [];
try {
    $stmt = $pdo->prepare("
        SELECT user_id, username, full_name, email, role, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent registrations: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        :root {
            --admin-primary: #0a74da;
            --admin-secondary: #1c3d5a;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fc;
        }
        
        .sidebar {
            background: #1c3d5a;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #0a74da 0%, #1c3d5a 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(10, 116, 218, 0.3);
        }

        .page-header h1 {
            margin: 0 0 8px 0;
            font-size: 2em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header p {
            margin: 0;
            opacity: 0.95;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            margin-bottom: 15px;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #0a74da 0%, #1c3d5a 100%); color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }

        .stat-details h3 {
            margin: 0;
            font-size: 2em;
            color: #2d3748;
            font-weight: 700;
        }

        .stat-details p {
            margin: 5px 0 0 0;
            color: #718096;
            font-size: 0.9em;
        }

        /* Controls Section */
        .controls-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .controls-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .control-group {
            flex: 1;
            min-width: 200px;
        }

        .control-group label {
            display: block;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        .control-group input,
        .control-group select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
        }

        .btn-search {
            padding: 10px 20px;
            background: var(--admin-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-search:hover {
            background: var(--admin-secondary);
        }

        .btn-add {
            padding: 10px 20px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-add:hover {
            background: #059669;
        }

        /* Users Section */
        .users-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .section-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            margin: 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th,
        .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .users-table th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.9em;
            text-transform: uppercase;
        }

        .users-table tr:hover {
            background: #f7fafc;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0a74da 0%, #1c3d5a 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2em;
        }

        .user-details h4 {
            margin: 0 0 3px 0;
            color: #2d3748;
            font-size: 1em;
        }

        .user-details p {
            margin: 0;
            color: #718096;
            font-size: 0.85em;
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85em;
            text-transform: uppercase;
        }

        .role-badge.admin {
            background: #dbeafe;
            color: #1e40af;
        }

        .role-badge.teacher {
            background: #d1fae5;
            color: #065f46;
        }

        .role-badge.student {
            background: #e0e7ff;
            color: #3730a3;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85em;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn.edit {
            background: #dbeafe;
            color: #1e40af;
        }

        .action-btn.edit:hover {
            background: #bfdbfe;
        }

        .action-btn.delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-btn.delete:hover {
            background: #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #cbd5e0;
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <p>Manage system users, roles, and permissions</p>
        </div>

        <!-- User Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $user_stats['total_users']; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $user_stats['total_students']; ?></h3>
                    <p>Students</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $user_stats['total_teachers']; ?></h3>
                    <p>Teachers</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $user_stats['total_admins']; ?></h3>
                    <p>Administrators</p>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="controls-section">
            <form method="GET" action="">
                <div class="controls-row">
                    <div class="control-group">
                        <label for="search">Search Users</label>
                        <input type="text" name="search" id="search" 
                               placeholder="Search by name, username, or email..." 
                               value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    </div>

                    <div class="control-group">
                        <label for="role">Filter by Role</label>
                        <select name="role" id="role">
                            <option value="">All Roles</option>
                            <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Students</option>
                            <option value="teacher" <?php echo $role_filter === 'teacher' ? 'selected' : ''; ?>>Teachers</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrators</option>
                        </select>
                    </div>

                    <div class="control-group">
                        <label for="sort">Sort By</label>
                        <select name="sort" id="sort">
                            <option value="created" <?php echo $sort_by === 'created' ? 'selected' : ''; ?>>Recently Added</option>
                            <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name</option>
                            <option value="username" <?php echo $sort_by === 'username' ? 'selected' : ''; ?>>Username</option>
                            <option value="role" <?php echo $sort_by === 'role' ? 'selected' : ''; ?>>Role</option>
                            <option value="activity" <?php echo $sort_by === 'activity' ? 'selected' : ''; ?>>Recent Activity</option>
                        </select>
                    </div>

                    <div class="control-group">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="users-section">
            <div class="section-header">
                <h2><i class="fas fa-list"></i> Users (<?php echo count($users); ?>)</h2>
                <a href="add_user.php" class="btn-add">
                    <i class="fas fa-plus"></i> Add New User
                </a>
            </div>
            <div style="overflow-x: auto;">
                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No Users Found</h3>
                        <p>No users match your search criteria</p>
                    </div>
                <?php else: ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Quiz Attempts</th>
                                <th>Last Activity</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1)); ?>
                                            </div>
                                            <div class="user-details">
                                                <h4><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h4>
                                                <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge <?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo $user['quiz_attempts'] ?: 0; ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($user['last_activity']): ?>
                                            <?php echo date('M j, Y', strtotime($user['last_activity'])); ?>
                                        <?php else: ?>
                                            <span style="color: #6b7280;">No activity</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="action-btn edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="delete_user.php?id=<?php echo $user['user_id']; ?>" 
                                               class="action-btn delete"
                                               onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<script>
// Auto-submit form on select change
document.getElementById('role').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('sort').addEventListener('change', function() {
    this.form.submit();
});
</script>

</body>
</html>
