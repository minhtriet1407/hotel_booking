<?php
require_once 'config/database.php';

// Pagination settings
$limit = 9;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Filter and Sort inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$capacity = isset($_GET['capacity']) ? trim($_GET['capacity']) : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? $_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? $_GET['max_price'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'price_asc';

// Build the query dynamically
$conditions = [];
$params = [];
$types = "";

if ($search !== '') {
    $conditions[] = "name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

if ($type !== '') {
    $conditions[] = "type = ?";
    $params[] = $type;
    $types .= "s";
}

if ($capacity !== '') {
    $conditions[] = "capacity >= ?";
    $params[] = $capacity;
    $types .= "i";
}

if ($min_price !== '') {
    $conditions[] = "price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price !== '') {
    $conditions[] = "price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

// Sort Order
$orderClause = " ORDER BY price ASC"; // default
if ($sort === 'price_desc') {
    $orderClause = " ORDER BY price DESC";
}

$error_message = '';
$rooms = [];
$total_pages = 1;
$distinct_types = [];
$distinct_capacities = [];

try {
    // 1. Fetch distinct types and capacities for the filter sidebar
    $type_res = $conn->query("SELECT DISTINCT type FROM rooms WHERE type IS NOT NULL AND type != ''");
    if ($type_res) {
        while ($r = $type_res->fetch_assoc()) $distinct_types[] = $r['type'];
    }
    
    $cap_res = $conn->query("SELECT DISTINCT capacity FROM rooms WHERE capacity IS NOT NULL ORDER BY capacity ASC");
    if ($cap_res) {
        while ($r = $cap_res->fetch_assoc()) $distinct_capacities[] = $r['capacity'];
    }

    // 2. Count total records for pagination
    $count_sql = "SELECT COUNT(*) as total FROM rooms" . $whereClause;
    $stmt_count = $conn->prepare($count_sql);
    
    if ($stmt_count) {
        if (!empty($params)) {
            $stmt_count->bind_param($types, ...$params);
        }
        $stmt_count->execute();
        $count_res = $stmt_count->get_result();
        $total_records = $count_res->fetch_assoc()['total'];
        $total_pages = ceil($total_records / $limit);
        if ($total_pages < 1) $total_pages = 1;
    }

    // 3. Fetch the actual records for the current page
    $sql = "SELECT * FROM rooms" . $whereClause . $orderClause . " LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // add offset and limit to params
        $params[] = $offset;
        $params[] = $limit;
        $types .= "ii";
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }

} catch (mysqli_sql_exception $e) {
    $error_message = "Could not load rooms. Ensure your database has the required columns (`name`, `type`, `capacity`, `price`). Error: " . $e->getMessage();
}

// Helper to keep query string on pagination links
function buildQueryString($page_num) {
    $query = $_GET;
    $query['page'] = $page_num;
    return '?' . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Rooms | Luxe Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">Luxe Hotel</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="rooms.php" class="active">Rooms</a>
            <a href="contact.php">Contact</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin/dashboard.php" style="color: var(--primary-color); font-weight: bold;"><i class="fas fa-cog"></i> Admin Panel</a>
                <?php else: ?>
                    <a href="dashboard.php">My Profile</a>
                <?php endif; ?>
                <a href="public/index.php?url=auth/logout">Logout</a>
            <?php else: ?>
                <a href="public/index.php?url=auth/login">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="page-header">
        <h1>Our Premium Rooms</h1>
        <p>Find the perfect space for your unforgettable stay.</p>
    </div>

    <div class="rooms-container">
        
        <!-- Sidebar Filters -->
        <aside class="filter-sidebar">
            <h3><i class="fas fa-filter"></i> Filter & Sort</h3>
            <form action="rooms.php" method="GET">
                
                <div class="form-group">
                    <label for="search">Search Name</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="e.g. Ocean View" value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="form-group">
                    <label for="type">Room Type</label>
                    <select name="type" id="type" class="form-control">
                        <option value="">All Types</option>
                        <?php foreach ($distinct_types as $dt): ?>
                            <option value="<?php echo htmlspecialchars($dt); ?>" <?php if ($type === $dt) echo 'selected'; ?>><?php echo htmlspecialchars($dt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="capacity">Min Capacity</label>
                    <select name="capacity" id="capacity" class="form-control">
                        <option value="">Any Capacity</option>
                        <?php foreach ($distinct_capacities as $dc): ?>
                            <option value="<?php echo htmlspecialchars($dc); ?>" <?php if ($capacity == $dc) echo 'selected'; ?>><?php echo htmlspecialchars($dc); ?>+ Guests</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Price Range ($)</label>
                    <div class="price-range">
                        <input type="number" name="min_price" class="form-control" placeholder="Min" min="0" value="<?php echo htmlspecialchars($min_price); ?>">
                        <span>-</span>
                        <input type="number" name="max_price" class="form-control" placeholder="Max" min="0" value="<?php echo htmlspecialchars($max_price); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="sort">Sort By</label>
                    <select name="sort" id="sort" class="form-control">
                        <option value="price_asc" <?php if ($sort === 'price_asc') echo 'selected'; ?>>Lowest Price First</option>
                        <option value="price_desc" <?php if ($sort === 'price_desc') echo 'selected'; ?>>Highest Price First</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary filter-btn">Apply Filters</button>
                <a href="rooms.php" class="btn btn-secondary filter-btn" style="margin-top: 10px; background: transparent; color: var(--text-muted); box-shadow: none; border: 1px solid var(--border-color);">Reset Filters</a>
            </form>
        </aside>

        <!-- Rooms Content -->
        <div class="rooms-content">
            <?php if (!empty($error_message)): ?>
                <div style="background-color: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="room-grid">
                <?php if (empty($rooms) && empty($error_message)): ?>
                    <div style="text-align: center; grid-column: 1 / -1; padding: 40px; background: var(--surface-color); border-radius: 12px; box-shadow: var(--shadow-sm);">
                        <i class="fas fa-search" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 20px;"></i>
                        <h3 style="margin-bottom: 10px;">No rooms found</h3>
                        <p style="color: var(--text-muted);">Try adjusting your filters or search terms.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <?php 
                            $roomId = htmlspecialchars($room['id'] ?? '');
                            $name = htmlspecialchars($room['name'] ?? 'Luxury Room');
                            $roomType = htmlspecialchars($room['type'] ?? 'Standard');
                            $cap = htmlspecialchars($room['capacity'] ?? '2');
                            $price_night = htmlspecialchars($room['price'] ?? '0.00');
                            $status = strtolower($room['status'] ?? 'available');
                            
                            $image = htmlspecialchars($room['image'] ?? 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&q=80');
                            $isAvailable = in_array($status, ['available', 'active', '1', 'true']);
                        ?>
                        <div class="room-card">
                            <div class="room-img-wrapper">
                                <img src="<?php echo $image; ?>" alt="<?php echo $name; ?>" class="room-img">
                                <span class="room-badge"><?php echo $roomType; ?></span>
                            </div>
                            <div class="room-content">
                                <div class="room-title">
                                    <h3><?php echo $name; ?></h3>
                                    <div class="room-price">$<?php echo $price_night; ?><span>/night</span></div>
                                </div>
                                
                                <div class="room-meta">
                                    <span><i class="fas fa-user-friends"></i> <?php echo $cap; ?> Guests</span>
                                    <span><i class="fas fa-bed"></i> <?php echo $roomType; ?></span>
                                </div>
                                
                                <div class="room-status">
                                    <?php if ($isAvailable): ?>
                                        <span class="status-available"><i class="fas fa-check-circle"></i> Available</span>
                                    <?php else: ?>
                                        <span class="status-booked"><i class="fas fa-times-circle"></i> Booked</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="room-footer">
                                    <?php if ($isAvailable): ?>
                                        <a href="book.php?id=<?php echo $roomId; ?>" class="btn btn-secondary">Book Now</a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed;" disabled>Sold Out</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo buildQueryString($page - 1); ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo buildQueryString($i); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo buildQueryString($page + 1); ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Luxe Hotel Booking. All Rights Reserved.</p>
    </footer>

</body>
</html>
