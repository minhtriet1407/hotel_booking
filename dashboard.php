<?php
require_once 'config/database.php';

// MOCK AUTHENTICATION
// Since the Authentication system (login.php) wasn't built yet, we are forcing a fake session here so you can test the Dashboard UI.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1; // Assuming user with ID 1 exists
$user_id = $_SESSION['user_id'];

$current_tab = $_GET['tab'] ?? 'upcoming';
$error_msg = '';
$success_msg = '';

// --- Handle Form Submissions ---

// 1. Cancel Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancel_id = (int)$_POST['cancel_id'];
    try {
        // Only allow cancellation if it's Pending or Approved and not in the past
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND check_in > CURDATE()");
        $stmt->bind_param("ii", $cancel_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success_msg = "Booking #$cancel_id has been successfully cancelled.";
        } else {
            $error_msg = "Could not cancel booking. It may be too late or the booking doesn't exist.";
        }
    } catch (Exception $e) {
        $error_msg = "Database Error: " . $e->getMessage();
    }
}

// 2. Update Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    try {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
        if ($stmt->execute()) {
            $success_msg = "Profile updated successfully.";
        } else {
            $error_msg = "Failed to update profile.";
        }
    } catch (Exception $e) {
        $error_msg = "Database Error updating profile: " . $e->getMessage();
    }
}

// 3. Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pw = $_POST['current_password'] ?? '';
    $new_pw = $_POST['new_password'] ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    if ($new_pw !== $confirm_pw) {
        $error_msg = "New passwords do not match.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $user = $res->fetch_assoc();
                if (password_verify($current_pw, $user['password'])) {
                    $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param("si", $hashed, $user_id);
                    $upd->execute();
                    $success_msg = "Password changed successfully.";
                } else {
                    $error_msg = "Current password is incorrect.";
                }
            }
        } catch (Exception $e) {
            $error_msg = "Database Error checking password: " . $e->getMessage();
        }
    }
}

// --- Fetch Data for Dashboard ---

$user_profile = ['name' => 'Mock User', 'email' => 'mock@example.com', 'phone' => ''];
$bookings = [];

try {
    // Fetch Profile
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $user_profile = $res->fetch_assoc();
        }
    }

    // Fetch Bookings (Upcoming or History)
    // Assuming bookings table has user_id, room_id, check_in, check_out, total_price, status
    $date_op = $current_tab === 'history' ? '<' : '>=';
    $order = $current_tab === 'history' ? 'DESC' : 'ASC';
    
    $b_sql = "SELECT b.*, r.name as room_name, r.image as room_image 
              FROM bookings b 
              LEFT JOIN rooms r ON b.room_id = r.id 
              WHERE b.user_id = ? AND b.check_in $date_op CURDATE() 
              ORDER BY b.check_in $order";
    
    $b_stmt = $conn->prepare($b_sql);
    if ($b_stmt) {
        $b_stmt->bind_param("i", $user_id);
        $b_stmt->execute();
        $b_res = $b_stmt->get_result();
        while ($row = $b_res->fetch_assoc()) {
            $bookings[] = $row;
        }
    }

} catch (mysqli_sql_exception $e) {
    // If the tables/columns don't exist, we catch it here so the UI still loads with an error message
    $error_msg = "Database Schema Error: Ensure your `users` and `bookings` tables have the correct columns (user_id in bookings, etc.). Details: " . $e->getMessage();
}

// Helper to colorize status
function getStatusBadge($status) {
    $s = strtolower($status);
    if ($s === 'pending') return 'badge-pending';
    if ($s === 'approved' || $s === 'confirmed') return 'badge-approved';
    if ($s === 'cancelled' || $s === 'rejected') return 'badge-cancelled';
    if ($s === 'completed') return 'badge-completed';
    return 'badge-pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | Luxe Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">Luxe Hotel</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="rooms.php">Rooms</a>
            <a href="contact.php">Contact</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin/dashboard.php" style="color: var(--primary-color); font-weight: bold;"><i class="fas fa-cog"></i> Admin Panel</a>
                <?php else: ?>
                    <a href="dashboard.php" class="active">My Profile</a>
                <?php endif; ?>
                <a href="public/index.php?url=auth/logout">Logout</a>
            <?php else: ?>
                <a href="public/index.php?url=auth/login">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="page-header" style="padding: 40px 20px;">
        <h1>Welcome back, <?php echo htmlspecialchars($user_profile['name'] ?? $user_profile['first_name'] ?? 'Guest'); ?>!</h1>
        <p>Manage your stays and profile preferences.</p>
    </div>

    <div class="dashboard-container">
        
        <!-- Sidebar Navigation -->
        <aside class="dashboard-sidebar">
            <h3>My Account</h3>
            <div class="dashboard-nav">
                <a href="dashboard.php?tab=upcoming" class="<?php echo $current_tab === 'upcoming' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Upcoming Bookings</a>
                <a href="dashboard.php?tab=history" class="<?php echo $current_tab === 'history' ? 'active' : ''; ?>"><i class="fas fa-history"></i> Booking History</a>
                <a href="dashboard.php?tab=profile" class="<?php echo $current_tab === 'profile' ? 'active' : ''; ?>"><i class="fas fa-user"></i> My Profile</a>
                <a href="dashboard.php?tab=security" class="<?php echo $current_tab === 'security' ? 'active' : ''; ?>"><i class="fas fa-lock"></i> Change Password</a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="dashboard-content">
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if ($current_tab === 'upcoming' || $current_tab === 'history'): ?>
                <h2><?php echo $current_tab === 'upcoming' ? 'Upcoming Stays' : 'Past Booking History'; ?></h2>
                
                <?php if (empty($bookings) && empty($error_msg)): ?>
                    <div style="text-align: center; padding: 40px; border: 1px dashed var(--border-color); border-radius: 12px;">
                        <i class="fas fa-calendar-times" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px;"></i>
                        <p style="color: var(--text-muted); font-size: 1.1rem;">You have no <?php echo $current_tab; ?> bookings.</p>
                        <?php if ($current_tab === 'upcoming'): ?>
                            <a href="rooms.php" class="btn btn-primary" style="margin-top: 20px;">Book a Room Now</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="booking-history-list">
                        <?php foreach ($bookings as $b): 
                            $status = htmlspecialchars($b['status'] ?? 'Pending');
                            $b_id = $b['id'] ?? 0;
                        ?>
                            <div class="history-card">
                                <img src="<?php echo htmlspecialchars($b['room_image'] ?? 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&q=80'); ?>" alt="Room" class="history-img">
                                <div class="history-info">
                                    <div>
                                        <div class="history-header">
                                            <div>
                                                <h4><?php echo htmlspecialchars($b['room_name'] ?? 'Luxury Room'); ?></h4>
                                                <span class="ref">Booking ID: #<?php echo str_pad($b_id, 5, '0', STR_PAD_LEFT); ?></span>
                                            </div>
                                            <span class="<?php echo getStatusBadge($status); ?>"><?php echo $status; ?></span>
                                        </div>
                                        
                                        <div class="history-dates">
                                            <span><i class="fas fa-sign-in-alt"></i> Check-in: <?php echo date('M d, Y', strtotime($b['check_in'])); ?></span>
                                            <span><i class="fas fa-sign-out-alt"></i> Check-out: <?php echo date('M d, Y', strtotime($b['check_out'])); ?></span>
                                        </div>
                                        <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 15px;">
                                            <i class="fas fa-users"></i> <?php echo htmlspecialchars($b['guests'] ?? 1); ?> Guests
                                        </div>
                                    </div>
                                    
                                    <div class="history-footer">
                                        <div class="history-price">
                                            Total: $<?php echo htmlspecialchars($b['total_price'] ?? '0.00'); ?>
                                        </div>
                                        
                                        <?php if ($current_tab === 'upcoming' && strtolower($status) !== 'cancelled'): ?>
                                            <form action="dashboard.php?tab=upcoming" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.');">
                                                <input type="hidden" name="cancel_id" value="<?php echo $b_id; ?>">
                                                <button type="submit" class="btn btn-secondary" style="padding: 6px 15px; font-size: 0.9rem;">Cancel Booking</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($current_tab === 'profile'): ?>
                <h2>Update Profile</h2>
                <form action="dashboard.php?tab=profile" method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user_profile['name'] ?? $user_profile['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_profile['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user_profile['phone'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>

            <?php elseif ($current_tab === 'security'): ?>
                <h2>Change Password</h2>
                <form action="dashboard.php?tab=security" method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            <?php endif; ?>
            
        </main>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Luxe Hotel Booking. All Rights Reserved.</p>
    </footer>

</body>
</html>
