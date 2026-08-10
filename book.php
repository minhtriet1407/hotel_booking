<?php
require_once 'config/database.php';

$roomId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$room = null;
$error_msg = '';
$success_msg = '';

// 1. Fetch Room Details
if ($roomId > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $room = $result->fetch_assoc();
        } else {
            $error_msg = "Room not found.";
        }
    } catch (Exception $e) {
        $error_msg = "Database error: " . $e->getMessage();
    }
}

// Ensure the room is available
$status = strtolower($room['status'] ?? 'available');
$isAvailable = in_array($status, ['available', 'active', '1', 'true']);
if ($room && !$isAvailable) {
    $error_msg = "This room is currently marked as unavailable for booking.";
}

// 2. Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $room && $isAvailable) {
    $checkIn = $_POST['check_in'] ?? '';
    $checkOut = $_POST['check_out'] ?? '';
    $guests = (int)($_POST['guests'] ?? 1);
    $specialRequest = trim($_POST['special_request'] ?? '');
    
    // We assume these fields exist to collect user info since there's no auth
    $guestName = trim($_POST['guest_name'] ?? 'Guest');
    $guestEmail = trim($_POST['guest_email'] ?? 'guest@example.com');
    $guestPhone = trim($_POST['guest_phone'] ?? '');

    $today = date('Y-m-d');
    
    // Validations
    if (empty($checkIn) || empty($checkOut)) {
        $error_msg = "Please select both check-in and check-out dates.";
    } elseif ($checkIn < $today) {
        $error_msg = "Check-in date cannot be in the past.";
    } elseif ($checkOut <= $checkIn) {
        $error_msg = "Check-out date must be after the check-in date.";
    } else {
        // Calculate Total Price
        $date1 = new DateTime($checkIn);
        $date2 = new DateTime($checkOut);
        $nights = $date2->diff($date1)->format("%a");
        if ($nights == 0) $nights = 1; // minimum 1 night
        $pricePerNight = (float)($room['price'] ?? 0);
        $totalPrice = $nights * $pricePerNight;

        try {
            // Check for Overlapping Bookings
            // An overlap occurs if an existing booking's check_in < requested check_out AND existing check_out > requested check_in
            $overlap_sql = "SELECT COUNT(*) as cnt FROM bookings 
                            WHERE room_id = ? 
                            AND status != 'Cancelled' AND status != 'Rejected'
                            AND check_in < ? 
                            AND check_out > ?";
            $overlap_stmt = $conn->prepare($overlap_sql);
            $overlap_stmt->bind_param("iss", $roomId, $checkOut, $checkIn);
            $overlap_stmt->execute();
            $overlap_res = $overlap_stmt->get_result();
            $overlap_count = $overlap_res->fetch_assoc()['cnt'];

            if ($overlap_count > 0) {
                $error_msg = "Sorry, this room is already booked for the selected dates. Please choose different dates.";
            } else {
                // Insert the Booking
                // Extract user_id if logged in, otherwise use NULL to avoid foreign key violations with default '0'
                $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
                
                if ($user_id > 0) {
                    $insert_sql = "INSERT INTO bookings (room_id, user_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, special_request, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("iisssssids", $roomId, $user_id, $guestName, $guestEmail, $guestPhone, $checkIn, $checkOut, $guests, $totalPrice, $specialRequest);
                } else {
                    $insert_sql = "INSERT INTO bookings (room_id, user_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, special_request, status) 
                                   VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("isssssids", $roomId, $guestName, $guestEmail, $guestPhone, $checkIn, $checkOut, $guests, $totalPrice, $specialRequest);
                }
                
                if ($insert_stmt->execute()) {
                    $success_msg = "Your booking has been submitted successfully and is now Pending! Booking Reference ID: " . $conn->insert_id;
                } else {
                    $error_msg = "Failed to submit booking.";
                }
            }
        } catch (mysqli_sql_exception $e) {
            $error_msg = "Booking failed due to a database error. Please ensure your `bookings` table has the correct schema (guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, special_request, status). Error: " . $e->getMessage();
        }
    }
}

// Variables for display
$name = htmlspecialchars($room['name'] ?? 'Room');
$roomType = htmlspecialchars($room['type'] ?? $room['room_type'] ?? 'Standard');
$price = (float)($room['price'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo $name; ?> | Luxe Hotel</title>
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
                    <a href="dashboard.php">My Profile</a>
                <?php endif; ?>
                <a href="public/index.php?url=auth/logout">Logout</a>
            <?php else: ?>
                <a href="public/index.php?url=auth/login">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="page-header" style="padding: 40px 20px;">
        <h1>Complete Your Booking</h1>
        <p>You are one step away from an unforgettable stay.</p>
    </div>

    <div class="booking-container">
        
        <?php if (!$room): ?>
            <div style="grid-column: 1/-1; text-align:center;">
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
                <a href="rooms.php" class="btn btn-primary">Go Back to Rooms</a>
            </div>
        <?php elseif (!empty($success_msg)): ?>
            <div style="grid-column: 1/-1; text-align:center; padding: 40px; background: var(--surface-color); border-radius: 12px; box-shadow: var(--shadow-md);">
                <i class="fas fa-check-circle" style="font-size: 5rem; color: var(--success); margin-bottom: 20px;"></i>
                <h2>Booking Successful!</h2>
                <p style="font-size: 1.1rem; color: var(--text-muted); margin: 15px 0 30px;"><?php echo $success_msg; ?></p>
                <a href="index.php" class="btn btn-primary">Return Home</a>
            </div>
        <?php else: ?>
            
            <div class="booking-form-wrapper">
                <h2>Guest & Stay Details</h2>
                
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?></div>
                <?php endif; ?>

                <?php if (!$isAvailable): ?>
                    <div class="alert alert-error"><i class="fas fa-ban"></i> This room is currently unavailable.</div>
                <?php else: ?>
                <form action="book.php?id=<?php echo $roomId; ?>" method="POST" id="bookingForm">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="guest_name" class="form-control" required placeholder="John Doe">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="guest_email" class="form-control" required placeholder="john@example.com">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="guest_phone" class="form-control" required placeholder="+1 234 567 8900">
                        </div>
                    </div>

                    <h3 style="margin: 30px 0 15px; font-size:1.2rem; border-bottom:1px solid var(--border-color); padding-bottom:10px;">Stay Dates</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Check-in Date</label>
                            <input type="date" name="check_in" id="check_in" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Check-out Date</label>
                            <input type="date" name="check_out" id="check_out" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Number of Guests</label>
                            <select name="guests" class="form-control">
                                <?php 
                                    $maxCap = (int)($room['capacity'] ?? 2);
                                    for($i=1; $i<=$maxCap; $i++) {
                                        echo "<option value='$i'>$i " . ($i==1?'Guest':'Guests') . "</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Special Requests (Optional)</label>
                        <textarea name="special_request" class="form-control" rows="4" placeholder="Any special needs, early check-in, dietary requirements..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Confirm & Book Request</button>
                </form>
                <?php endif; ?>
            </div>

            <aside>
                <div class="summary-card">
                    <h3>Booking Summary</h3>
                    <div class="summary-row">
                        <span>Room</span>
                        <span style="font-weight: 500; color:white; text-align:right; max-width: 200px;"><?php echo $name; ?> (<?php echo $roomType; ?>)</span>
                    </div>
                    <div class="summary-row">
                        <span>Price per Night</span>
                        <span>$<span id="display_price"><?php echo number_format($price, 2); ?></span></span>
                    </div>
                    <div class="summary-row" style="margin-top:20px;">
                        <span>Nights</span>
                        <span id="display_nights">0</span>
                    </div>
                    
                    <div class="summary-total">
                        <span>Total Price</span>
                        <span>$<span id="display_total">0.00</span></span>
                    </div>
                </div>
            </aside>
            
        <?php endif; ?>

    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Luxe Hotel Booking. All Rights Reserved.</p>
    </footer>

    <!-- JavaScript for Dynamic Price Calculation -->
    <script>
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');
        const displayNights = document.getElementById('display_nights');
        const displayTotal = document.getElementById('display_total');
        const pricePerNight = <?php echo $price; ?>;

        function calculatePrice() {
            if (checkInInput && checkOutInput && checkInInput.value && checkOutInput.value) {
                const date1 = new Date(checkInInput.value);
                const date2 = new Date(checkOutInput.value);
                
                // Reset time to properly calculate day diff across daylight savings
                date1.setHours(0,0,0,0);
                date2.setHours(0,0,0,0);
                
                const diffTime = Math.abs(date2 - date1);
                let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (date2 <= date1) {
                    diffDays = 0;
                    checkOutInput.setCustomValidity("Check-out date must be after check-in date.");
                } else {
                    checkOutInput.setCustomValidity("");
                }

                displayNights.innerText = diffDays;
                displayTotal.innerText = (diffDays * pricePerNight).toFixed(2);
            }
        }

        if (checkInInput && checkOutInput) {
            checkInInput.addEventListener('change', () => {
                // Minimum check-out date is 1 day after check-in
                if(checkInInput.value) {
                    let minOut = new Date(checkInInput.value);
                    minOut.setDate(minOut.getDate() + 1);
                    checkOutInput.min = minOut.toISOString().split('T')[0];
                }
                calculatePrice();
            });
            checkOutInput.addEventListener('change', calculatePrice);
        }
    </script>
</body>
</html>
