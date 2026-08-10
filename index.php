<?php
require_once 'config/database.php';

// If session isn't started in database.php, uncomment this:
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rooms = [];
$error_message = '';

try {
    $query = "SELECT * FROM rooms LIMIT 6";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Could not load rooms. Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxe Hotel | Premium Bookings</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="assets/css/premium-theme.css">
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-crown text-gold me-2"></i>Luxe<span>Hotel</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rooms.php">Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#facilities">Facilities</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="admin/dashboard.php" class="btn btn-outline-gold me-2"><i class="fas fa-cog"></i> Admin</a>
                        <?php else: ?>
                            <a href="dashboard.php" class="btn btn-outline-gold me-2"><i class="fas fa-user"></i> Profile</a>
                        <?php endif; ?>
                        <a href="public/index.php?url=auth/logout" class="text-white text-decoration-none me-3"><i class="fas fa-sign-out-alt"></i></a>
                    <?php else: ?>
                        <a href="public/index.php?url=auth/login" class="text-white text-decoration-none me-3"><i class="fas fa-user-circle fa-lg"></i></a>
                    <?php endif; ?>
                    <a href="rooms.php" class="btn btn-gold">Book Now</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h1>Experience Luxury & <span class="text-gold">Comfort</span></h1>
                    <p>Discover the perfect blend of comfort, elegance and world-class hospitality. Book your stay with us today.</p>
                    <div class="btn-group gap-3">
                        <a href="#booking-search" class="btn btn-gold">Book Now</a>
                        <a href="#featured" class="btn btn-outline-gold">Explore Rooms</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Search Box -->
    <div class="container" id="booking-search">
        <div class="booking-search">
            <form action="rooms.php" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label"><i class="far fa-calendar-alt me-2"></i>Check In</label>
                        <input type="date" class="form-control" name="checkin" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="far fa-calendar-alt me-2"></i>Check Out</label>
                        <input type="date" class="form-control" name="checkout" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="fas fa-user me-2"></i>Adults</label>
                        <select class="form-select" name="adults">
                            <option value="1">1</option>
                            <option value="2" selected>2</option>
                            <option value="3">3</option>
                            <option value="4">4+</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="fas fa-child me-2"></i>Children</label>
                        <select class="form-select" name="children">
                            <option value="0" selected>0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3+</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-gold w-100 py-3">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Featured Rooms Section -->
    <section id="featured" class="section-padding">
        <div class="container">
            <div class="section-title">
                <h2>Featured Rooms</h2>
                <p>Explore our most popular and luxurious accommodations</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php if (empty($rooms) && empty($error_message)): ?>
                    <div class="col-12 text-center text-muted">No featured rooms available at the moment.</div>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <?php 
                            $roomId = htmlspecialchars($room['id'] ?? '');
                            $name = htmlspecialchars($room['name'] ?? $room['room_name'] ?? 'Luxury Room');
                            $type = htmlspecialchars($room['room_type'] ?? $room['type'] ?? 'Standard');
                            $capacity = htmlspecialchars($room['capacity'] ?? $room['max_persons'] ?? '2');
                            $price = htmlspecialchars($room['price'] ?? $room['price_per_night'] ?? '0.00');
                            $status = strtolower($room['status'] ?? $room['availability'] ?? 'available');
                            
                            $image = htmlspecialchars($room['image'] ?? $room['image_url'] ?? 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&q=80');
                            $isAvailable = in_array($status, ['available', 'active', '1', 'true']);
                        ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="room-card">
                                <div class="room-img-wrapper">
                                    <img src="<?php echo $image; ?>" alt="<?php echo $name; ?>" class="room-img">
                                    <span class="room-badge"><?php echo $type; ?></span>
                                    <div class="room-wishlist"><i class="far fa-heart"></i></div>
                                </div>
                                <div class="room-content">
                                    <div class="room-rating">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                                        <span>(120 reviews)</span>
                                    </div>
                                    <div class="room-title">
                                        <h3><?php echo $name; ?></h3>
                                        <div class="room-price">$<?php echo $price; ?><span>/night</span></div>
                                    </div>
                                    
                                    <div class="room-meta">
                                        <span><i class="fas fa-user-friends"></i> <?php echo $capacity; ?> Guests</span>
                                        <span><i class="fas fa-bed"></i> <?php echo $type; ?></span>
                                        <span><i class="fas fa-bath"></i> 1 Bath</span>
                                    </div>
                                    
                                    <div class="room-status">
                                        <?php if ($isAvailable): ?>
                                            <span class="status-available"><i class="fas fa-check-circle"></i> Available</span>
                                        <?php else: ?>
                                            <span class="status-booked"><i class="fas fa-times-circle"></i> Sold Out</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <a href="room.php?id=<?php echo $roomId; ?>" class="text-decoration-none text-dark fw-bold">View Details <i class="fas fa-arrow-right ms-1"></i></a>
                                        <?php if ($isAvailable): ?>
                                            <a href="book.php?id=<?php echo $roomId; ?>" class="btn btn-gold">Book Now</a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" disabled>Sold Out</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="rooms.php" class="btn btn-outline-gold">View All Rooms</a>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="section-padding bg-light">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Luxe Hotel?</h2>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h4>Best Price Guarantee</h4>
                        <p>Get the best price when you book direct with us. No hidden fees.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fas fa-lock"></i></div>
                        <h4>Secure Booking</h4>
                        <p>Your information is safe with our secure booking system.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fas fa-times-circle"></i></div>
                        <h4>Free Cancellation</h4>
                        <p>Cancel for free up to 48 hours before check-in.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fas fa-headset"></i></div>
                        <h4>24/7 Support</h4>
                        <p>We are here to help you anytime, everytime.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fas fa-concierge-bell"></i></div>
                        <h4>Premium Service</h4>
                        <p>Enjoy world-class service during your stay with us.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fas fa-tags"></i></div>
                        <h4>Exclusive Offers</h4>
                        <p>Get exclusive deals and discounts only on our website.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Special Offers -->
    <section class="section-padding">
        <div class="container">
            <div class="section-title">
                <h2>Special Offers</h2>
                <p>Exclusive deals for our most valued guests.</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-lg-6">
                    <div class="offer-card h-100">
                        <div class="row g-0 h-100">
                            <div class="col-md-5 position-relative">
                                <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=800&q=80" alt="Offer" style="height: 100%; object-fit: cover;">
                                <span class="offer-badge">20% OFF</span>
                            </div>
                            <div class="col-md-7 d-flex align-items-center">
                                <div class="offer-content w-100">
                                    <h4>Summer Sale</h4>
                                    <p class="text-muted">Enjoy up to 20% off for all rooms. Book before August 31, 2026.</p>
                                    <h5 class="text-gold mb-3">Starting from $99/night</h5>
                                    <a href="rooms.php" class="btn btn-outline-gold btn-sm">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="offer-card h-100">
                        <div class="row g-0 h-100">
                            <div class="col-md-5 position-relative">
                                <img src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80" alt="Offer" style="height: 100%; object-fit: cover;">
                                <span class="offer-badge">Free Breakfast</span>
                            </div>
                            <div class="col-md-7 d-flex align-items-center">
                                <div class="offer-content w-100">
                                    <h4>Family Package</h4>
                                    <p class="text-muted">Free breakfast for family bookings. Minimum stay 2 nights.</p>
                                    <h5 class="text-gold mb-3">Starting from $149/night</h5>
                                    <a href="rooms.php" class="btn btn-outline-gold btn-sm">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Hotel Facilities Section -->
    <section class="section-padding bg-light" id="facilities">
        <div class="container">
            <div class="section-title">
                <h2>Hotel Facilities</h2>
                <p>Enjoy premium amenities designed for comfort, relaxation, and convenience.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-wifi facility-icon"></i>
                        <h4>Free High-Speed WiFi</h4>
                        <p>Stay connected with our complimentary premium wireless internet access available everywhere.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-swimming-pool facility-icon"></i>
                        <h4>Swimming Pool</h4>
                        <p>Relax and unwind in our temperature-controlled infinity pool with breathtaking views.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-dumbbell facility-icon"></i>
                        <h4>Fitness Center</h4>
                        <p>Keep up with your routine in our fully equipped state-of-the-art gym facility.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-spa facility-icon"></i>
                        <h4>Spa & Wellness</h4>
                        <p>Rejuvenate your body and mind with our exclusive therapeutic massage treatments.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-utensils facility-icon"></i>
                        <h4>Restaurant</h4>
                        <p>Savor culinary masterpieces prepared by our award-winning executive chefs.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-glass-martini-alt facility-icon"></i>
                        <h4>Bar & Lounge</h4>
                        <p>Enjoy handcrafted cocktails and premium spirits in an elegant, cozy atmosphere.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-shuttle-van facility-icon"></i>
                        <h4>Airport Transfer</h4>
                        <p>Experience hassle-free travel with our dedicated luxury airport pickup service.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-parking facility-icon"></i>
                        <h4>Free Parking</h4>
                        <p>Enjoy secure and complimentary valet parking during your entire stay.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-concierge-bell facility-icon"></i>
                        <h4>24/7 Front Desk</h4>
                        <p>Our dedicated staff is always available to assist you with any request, anytime.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-bell facility-icon"></i>
                        <h4>Room Service</h4>
                        <p>Indulge in delicious meals delivered straight to the comfort of your room.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-briefcase facility-icon"></i>
                        <h4>Conference Room</h4>
                        <p>Host successful meetings in our modern, fully equipped business center.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="modern-facility-card">
                        <i class="fas fa-tshirt facility-icon"></i>
                        <h4>Laundry Service</h4>
                        <p>Take advantage of our quick and professional dry cleaning and laundry services.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="section-padding stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <i class="fas fa-bed stat-icon"></i>
                        <div class="stat-number">150+</div>
                        <div class="stat-text">Luxury Rooms</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <i class="fas fa-users stat-icon"></i>
                        <div class="stat-number">12K+</div>
                        <div class="stat-text">Happy Guests</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <i class="fas fa-award stat-icon"></i>
                        <div class="stat-number">15</div>
                        <div class="stat-text">Years Experience</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <i class="fas fa-star stat-icon"></i>
                        <div class="stat-number">4.9</div>
                        <div class="stat-text">Average Rating</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="section-padding bg-light">
        <div class="container">
            <div class="section-title">
                <h2>What Our Guests Say</h2>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User" class="testimonial-img">
                        <div class="room-rating mb-3">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Excellent hotel with friendly staff and amazing service. The room was clean and comfortable. Will definitely come back."</p>
                        <h5 class="testimonial-name">John Smith</h5>
                        <span class="text-muted small">July 15, 2026</span>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User" class="testimonial-img">
                        <div class="room-rating mb-3">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"The best hotel I've ever stayed in! Everything was perfect from check-in to check-out. Highly recommended!"</p>
                        <h5 class="testimonial-name">Emily Johnson</h5>
                        <span class="text-muted small">July 10, 2026</span>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 d-none d-lg-block">
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/67.jpg" alt="User" class="testimonial-img">
                        <div class="room-rating mb-3">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="testimonial-text">"Great location, beautiful rooms, and delicious breakfast. The pool area is fantastic for relaxing."</p>
                        <h5 class="testimonial-name">Michael Brown</h5>
                        <span class="text-muted small">July 5, 2026</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery -->
    <section class="section-padding pb-0">
        <div class="container-fluid px-4">
            <div class="section-title">
                <h2>Moments at Luxe Hotel</h2>
            </div>
            
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1542314831-c6a4d14d8387?auto=format&fit=crop&w=600&q=80" alt="Gallery">
                        <div class="gallery-overlay"><i class="fas fa-search-plus"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=600&q=80" alt="Gallery">
                        <div class="gallery-overlay"><i class="fas fa-search-plus"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=600&q=80" alt="Gallery">
                        <div class="gallery-overlay"><i class="fas fa-search-plus"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=600&q=80" alt="Gallery">
                        <div class="gallery-overlay"><i class="fas fa-search-plus"></i></div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3 mb-5">
                <button class="btn btn-primary bg-primary-color border-0 px-4 py-2" style="background-color: var(--primary-color);">View More Photos</button>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter-section">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <div class="newsletter-title d-flex align-items-center">
                        <div class="bg-white rounded-circle p-3 me-4 d-inline-flex">
                            <i class="far fa-envelope text-gold fa-2x"></i>
                        </div>
                        <div>
                            <h3>Subscribe to Our Newsletter</h3>
                            <p>Get exclusive offers and updates straight to your inbox.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <form class="newsletter-form">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Enter your email address" required>
                            <button class="btn btn-gold" type="button">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <div class="footer-widget">
                        <a href="index.php" class="navbar-brand d-block mb-3 fs-3"><i class="fas fa-crown text-gold me-2"></i>Luxe<span class="text-white">Hotel</span></a>
                        <p class="footer-text">Experience luxury and comfort like never before. We are dedicated to making your stay unforgettable.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="footer-widget">
                        <h4>Quick Links</h4>
                        <ul class="footer-links">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="rooms.php">Rooms & Suites</a></li>
                            <li><a href="#">Special Offers</a></li>
                            <li><a href="#">About Us</a></li>
                            <li><a href="contact.php">Contact Us</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="footer-widget">
                        <h4>Customer Service</h4>
                        <ul class="footer-links">
                            <li><a href="#">Help Center</a></li>
                            <li><a href="#">FAQs</a></li>
                            <li><a href="#">Cancellation Policy</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                            <li><a href="#">Terms & Conditions</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="footer-widget">
                        <h4>Contact Info</h4>
                        <ul class="footer-contact list-unstyled">
                            <li><i class="fas fa-map-marker-alt"></i> 123 Luxury Street, Da Nang, Vietnam</li>
                            <li><i class="fas fa-phone-alt"></i> +84 123 456 789</li>
                            <li><i class="fas fa-envelope"></i> info@luxehotel.com</li>
                            <li><i class="fas fa-clock"></i> 24/7 Customer Support</li>
                        </ul>
                        <div class="mt-4">
                            <h6 class="text-white mb-2">We Accept</h6>
                            <div class="d-flex gap-2">
                                <i class="fab fa-cc-visa fa-2x text-light"></i>
                                <i class="fab fa-cc-mastercard fa-2x text-light"></i>
                                <i class="fab fa-cc-paypal fa-2x text-light"></i>
                                <i class="fab fa-cc-amex fa-2x text-light"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Luxe Hotel Booking. All Rights Reserved.</p>
                <p class="mb-0">Designed with <i class="fas fa-heart text-danger"></i> for your comfort</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Navbar Scroll Effect -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    document.querySelector('.navbar').style.padding = '10px 0';
                    document.querySelector('.navbar').style.backgroundColor = 'rgba(15, 23, 42, 0.98)';
                } else {
                    document.querySelector('.navbar').style.padding = '15px 0';
                    document.querySelector('.navbar').style.backgroundColor = 'rgba(15, 23, 42, 0.95)';
                }
            });
        });
    </script>
</body>
</html>
