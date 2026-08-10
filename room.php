<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// MOCK AUTHENTICATION FOR CUSTOMER
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['name'] = 'Test User';
}

require_once 'config/database.php';
$roomId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$room = null;
$relatedRooms = [];
$error_message = '';

if ($roomId > 0) {
    try {
        // Fetch the main room
        $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $room = $result->fetch_assoc();
            
            // Extract type for related rooms safely
            $roomType = $room['type'] ?? $room['room_type'] ?? '';
            
            // Fetch related rooms
            if (!empty($roomType)) {
                $rel_stmt = $conn->prepare("SELECT * FROM rooms WHERE (type = ? OR room_type = ?) AND id != ? LIMIT 3");
                $rel_stmt->bind_param("ssi", $roomType, $roomType, $roomId);
                $rel_stmt->execute();
                $rel_result = $rel_stmt->get_result();
                
                while ($r = $rel_result->fetch_assoc()) {
                    $relatedRooms[] = $r;
                }
            }
        }
    } catch (mysqli_sql_exception $e) {
        $error_message = "Could not load room details. Ensure database columns are correct. Error: " . $e->getMessage();
    }

    $review_msg = '';
    $review_err = '';
    
    // Handle Review Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);
        $user_id = $_SESSION['user_id'];
        
        if ($rating >= 1 && $rating <= 5) {
            try {
                $stmt = $conn->prepare("INSERT INTO reviews (user_id, room_id, rating, comment) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $user_id, $roomId, $rating, $comment);
                if ($stmt->execute()) {
                    $review_msg = "Thank you! Your review has been published.";
                } else {
                    $review_err = "Could not submit review.";
                }
            } catch (Exception $e) {
                $review_err = "Database error saving review: " . $e->getMessage();
            }
        }
    }

    // Check if user can review (Must have a completed booking)
    $can_review = false;
    try {
        $c_stmt = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND room_id = ? AND LOWER(status) = 'completed' LIMIT 1");
        $c_stmt->bind_param("ii", $_SESSION['user_id'], $roomId);
        $c_stmt->execute();
        if ($c_stmt->get_result()->num_rows > 0) {
            $can_review = true;
        }
    } catch (Exception $e) {
        // Fallback for testing if schema doesn't perfectly match
        $can_review = true; 
    }

    // Fetch Reviews & Calculate Average
    $reviews = [];
    $avg_rating = 0;
    $total_reviews = 0;
    try {
        $r_stmt = $conn->prepare("SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.room_id = ? ORDER BY r.created_at DESC");
        $r_stmt->bind_param("i", $roomId);
        $r_stmt->execute();
        $r_res = $r_stmt->get_result();
        $sum = 0;
        if ($r_res) {
            while($row = $r_res->fetch_assoc()) {
                $reviews[] = $row;
                $sum += (int)$row['rating'];
            }
        }
        $total_reviews = count($reviews);
        if ($total_reviews > 0) {
            $avg_rating = round($sum / $total_reviews, 1);
        }
    } catch (Exception $e) {}
}

// Variables for display
$name = htmlspecialchars($room['name'] ?? 'Room Not Found');
$roomType = htmlspecialchars($room['type'] ?? $room['room_type'] ?? 'Standard');
$cap = htmlspecialchars($room['capacity'] ?? '2');
$price = htmlspecialchars($room['price'] ?? '0.00');
$status = strtolower($room['status'] ?? 'available');
$image = htmlspecialchars($room['image'] ?? $room['image_url'] ?? 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=2000&q=80');

$description = htmlspecialchars($room['description'] ?? 'No description available for this luxurious room yet. Please contact support for more details.');
$facilities_raw = $room['facilities'] ?? 'Free Wi-Fi,Air Conditioning,Room Service,Flat-screen TV,Minibar';
$facilities = array_filter(array_map('trim', explode(',', $facilities_raw)));

$isAvailable = in_array($status, ['available', 'active', '1', 'true']);

// Default icon mapping for facilities
function getFacilityIcon($fac) {
    $facLower = strtolower($fac);
    if (strpos($facLower, 'wi-fi') !== false || strpos($facLower, 'wifi') !== false) return 'fa-wifi';
    if (strpos($facLower, 'tv') !== false) return 'fa-tv';
    if (strpos($facLower, 'air') !== false || strpos($facLower, 'ac') !== false) return 'fa-snowflake';
    if (strpos($facLower, 'pool') !== false) return 'fa-swimming-pool';
    if (strpos($facLower, 'gym') !== false || strpos($facLower, 'fitness') !== false) return 'fa-dumbbell';
    if (strpos($facLower, 'bar') !== false) return 'fa-glass-martini-alt';
    if (strpos($facLower, 'parking') !== false) return 'fa-parking';
    if (strpos($facLower, 'breakfast') !== false || strpos($facLower, 'service') !== false) return 'fa-concierge-bell';
    return 'fa-check-circle';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?> | Luxe Hotel</title>
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

    <?php if (!$room): ?>
        <div style="text-align: center; padding: 100px 20px; min-height: 60vh;">
            <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: var(--danger); margin-bottom: 20px;"></i>
            <h1>Room Not Found</h1>
            <p style="color: var(--text-muted); margin-bottom: 30px;">The room you are looking for does not exist or has been removed.</p>
            <a href="rooms.php" class="btn btn-primary">Browse All Rooms</a>
            
            <?php if (!empty($error_message)): ?>
                <div style="margin-top: 30px; background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; display: inline-block; text-align: left;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>

        <!-- Hero Section for Room Image -->
        <section class="room-hero" style="background-image: url('<?php echo $image; ?>');">
            <div class="room-hero-content">
                <span class="room-badge" style="position: relative; display: inline-block; margin-bottom: 15px; top: 0; right: 0; background: var(--primary-color); color: white;"><?php echo $roomType; ?></span>
                <h1><?php echo $name; ?></h1>
                <?php if ($total_reviews > 0): ?>
                    <div style="margin-top: 15px; color: #f59e0b; font-size: 1.2rem;">
                        <i class="fas fa-star"></i> <?php echo $avg_rating; ?>/5 (<?php echo $total_reviews; ?> Reviews)
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Room Details Content -->
        <section class="room-details-container">
            
            <!-- Left Column: Info -->
            <div class="room-main-info">
                <h2>Room Overview</h2>
                <div class="room-meta" style="font-size: 1.1rem; margin-bottom: 25px;">
                    <span><i class="fas fa-user-friends"></i> Max <?php echo $cap; ?> Guests</span>
                    <span style="margin-left: 20px;"><i class="fas fa-bed"></i> <?php echo $roomType; ?> Bed</span>
                </div>
                
                <p class="room-description"><?php echo nl2br($description); ?></p>
                
                <h2>Facilities</h2>
                <ul class="facilities-list">
                    <?php foreach ($facilities as $fac): ?>
                        <li><i class="fas <?php echo getFacilityIcon($fac); ?>"></i> <?php echo htmlspecialchars($fac); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Right Column: Booking Card -->
            <aside>
                <div class="booking-card">
                    <div class="price">
                        $<?php echo $price; ?><span> / night</span>
                    </div>
                    
                    <div class="status">
                        <?php if ($isAvailable): ?>
                            <span class="status-available"><i class="fas fa-check-circle"></i> Available to Book</span>
                        <?php else: ?>
                            <span class="status-booked"><i class="fas fa-times-circle"></i> Currently Unavailable</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($isAvailable): ?>
                        <a href="book.php?id=<?php echo $roomId; ?>" class="btn btn-primary">Book Now</a>
                    <?php else: ?>
                        <button class="btn btn-primary" style="opacity: 0.5; cursor: not-allowed;" disabled>Sold Out</button>
                    <?php endif; ?>
                </div>
            </aside>
        </section>

        <!-- Reviews Section -->
        <section class="featured-section reviews-section">
            <div class="reviews-header">
                <div>
                    <h2>Guest Reviews</h2>
                    <p>See what others are saying about this room.</p>
                </div>
                <div class="average-rating">
                    <h2><?php echo $avg_rating > 0 ? $avg_rating : '-'; ?></h2>
                    <div>
                        <div class="star-rating">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="<?php echo $i <= round($avg_rating) ? 'fas' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.9rem;">Based on <?php echo $total_reviews; ?> reviews</div>
                    </div>
                </div>
            </div>

            <?php if (!empty($review_msg)): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($review_msg); ?></div>
            <?php endif; ?>
            <?php if (!empty($review_err)): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($review_err); ?></div>
            <?php endif; ?>

            <?php if ($can_review): ?>
                <div class="review-form-container">
                    <h3 style="margin-bottom: 15px;">Leave a Review</h3>
                    <form action="room.php?id=<?php echo $roomId; ?>" method="POST">
                        <div class="rating-input">
                            <input type="radio" id="star5" name="rating" value="5" required />
                            <label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star4" name="rating" value="4" />
                            <label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star3" name="rating" value="3" />
                            <label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star2" name="rating" value="2" />
                            <label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star1" name="rating" value="1" />
                            <label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                        </div>
                        <div class="form-group">
                            <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience (optional)..." style="width: 100%;"></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary" style="margin-top: 15px;">Post Review</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="review-list">
                <?php if (empty($reviews)): ?>
                    <div style="text-align: center; padding: 30px; color: var(--text-muted); background: #f8fafc; border-radius: 8px;">
                        No reviews yet. Be the first to leave a review after your stay!
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): 
                        $revName = htmlspecialchars($rev['user_name'] ?? 'Guest User');
                        $revRating = (int)$rev['rating'];
                        $revDate = isset($rev['created_at']) ? date('M d, Y', strtotime($rev['created_at'])) : '';
                    ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div>
                                    <div class="review-author"><?php echo $revName; ?></div>
                                    <div class="review-date"><?php echo $revDate; ?></div>
                                </div>
                                <div class="star-rating" style="font-size: 1rem;">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="<?php echo $i <= $revRating ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if(!empty($rev['comment'])): ?>
                                <p class="review-text"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <!-- Related Rooms Section -->
        <?php if (!empty($relatedRooms)): ?>
        <section class="featured-section" style="padding-top: 20px;">
            <div class="section-header">
                <h2>Similar Rooms</h2>
                <p>Other <?php echo strtolower($roomType); ?> rooms you might love</p>
            </div>
            
            <div class="room-grid">
                <?php foreach ($relatedRooms as $rRoom): ?>
                    <?php 
                        $rId = htmlspecialchars($rRoom['id'] ?? '');
                        $rName = htmlspecialchars($rRoom['name'] ?? 'Luxury Room');
                        $rType = htmlspecialchars($rRoom['type'] ?? $rRoom['room_type'] ?? 'Standard');
                        $rCap = htmlspecialchars($rRoom['capacity'] ?? '2');
                        $rPrice = htmlspecialchars($rRoom['price'] ?? '0.00');
                        $rStatus = strtolower($rRoom['status'] ?? 'available');
                        $rImage = htmlspecialchars($rRoom['image'] ?? $rRoom['image_url'] ?? 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80');
                        $rAvail = in_array($rStatus, ['available', 'active', '1', 'true']);
                    ?>
                    <div class="room-card">
                        <a href="room.php?id=<?php echo $rId; ?>" style="color: inherit; text-decoration: none; display: flex; flex-direction: column; height: 100%;">
                            <div class="room-img-wrapper">
                                <img src="<?php echo $rImage; ?>" alt="<?php echo $rName; ?>" class="room-img">
                                <span class="room-badge"><?php echo $rType; ?></span>
                            </div>
                            <div class="room-content">
                                <div class="room-title">
                                    <h3><?php echo $rName; ?></h3>
                                    <div class="room-price">$<?php echo $rPrice; ?><span>/nt</span></div>
                                </div>
                                <div class="room-meta">
                                    <span><i class="fas fa-user-friends"></i> <?php echo $rCap; ?> Guests</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    <?php endif; ?>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Luxe Hotel Booking. All Rights Reserved.</p>
    </footer>

</body>
</html>
