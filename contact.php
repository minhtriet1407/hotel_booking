<?php
require_once 'config/database.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please provide a valid email address.";
    } else {
        try {
            // Assume table is named 'contacts' or 'contact_messages'
            $stmt = $conn->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            if ($stmt->execute()) {
                $success_msg = "Thank you! Your message has been sent. We will get back to you shortly.";
            } else {
                $error_msg = "Something went wrong. Please try again later.";
            }
        } catch (mysqli_sql_exception $e) {
            $error_msg = "Database Error: Please ensure you have a `contacts` table with columns `name`, `email`, `subject`, `message`. Details: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Luxe Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .contact-hero {
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1542314831-c6a4d27ce66b?w=2000&q=80');
            background-size: cover;
            background-position: center;
            height: 40vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        
        .contact-hero h1 {
            font-size: 3.5rem;
            margin-bottom: 15px;
            font-family: var(--font-heading);
        }

        .contact-container {
            max-width: 1200px;
            margin: -50px auto 100px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            position: relative;
            z-index: 10;
        }

        .contact-info-card {
            background: var(--primary-color);
            color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
        }

        .contact-info-card h3 {
            font-family: var(--font-heading);
            font-size: 1.8rem;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item i {
            font-size: 1.5rem;
            color: #d1d5db;
        }

        .info-item h4 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
        }

        .info-item p {
            margin: 0;
            color: #e5e7eb;
            line-height: 1.6;
        }

        .contact-form-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
        }

        .contact-form-card h2 {
            font-family: var(--font-heading);
            margin-bottom: 30px;
            color: var(--text-main);
        }

        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
            .contact-hero {
                height: 30vh;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">Luxe Hotel</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="rooms.php">Rooms</a>
            <a href="contact.php" class="active">Contact</a>
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

    <section class="contact-hero">
        <div>
            <h1>Get in Touch</h1>
            <p style="font-size: 1.2rem;">We're here to help make your stay perfect.</p>
        </div>
    </section>

    <div class="contact-container">
        
        <!-- Contact Info -->
        <div class="contact-info-card">
            <h3>Contact Information</h3>
            
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h4>Our Location</h4>
                    <p>123 Luxury Avenue<br>Beverly Hills, CA 90210<br>United States</p>
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-phone-alt"></i>
                <div>
                    <h4>Phone Number</h4>
                    <p>+1 (555) 123-4567<br>+1 (555) 987-6543</p>
                </div>
            </div>
            
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h4>Email Address</h4>
                    <p>reservations@luxehotel.com<br>support@luxehotel.com</p>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form-card">
            <h2>Send us a Message</h2>
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success" style="margin-bottom: 25px;"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error" style="margin-bottom: 25px;"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <form action="contact.php" method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group" style="margin: 0;">
                        <label>Your Name <span style="color:red;">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="John Doe" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Your Email <span style="color:red;">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="john@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="How can we help?" value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Message <span style="color:red;">*</span></label>
                    <textarea name="message" class="form-control" rows="6" required placeholder="Write your message here..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.1rem; padding: 15px;">Send Message</button>
            </form>
        </div>

    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Luxe Hotel Booking. All Rights Reserved.</p>
    </footer>

</body>
</html>
