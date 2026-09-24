<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>KSB Enterprise 24 Limited | Industrial, Environmental & Workforce Solutions</title>

    <meta name="description"
        content="KSB Enterprise 24 Limited delivers professional services in industrial cleaning, facility maintenance, landscaping, waste management, civil & electrical works, procurement, and personnel recruitment.">

    <meta name="robots" content="index, follow">

    <link rel="canonical" href="https://ksb-website.onrender.com/">

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "KSB Enterprise 24 Limited",
            "url": "https://ksb-website.onrender.com/",
            "logo": "https://ksb-website.onrender.com/logo.png"
        }
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header class="site-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg py-3">

                <a class="navbar-brand" href="index.php">
                    <img src="logo.png" alt="KSB Enterprise 24 Limited" class="company-logo">
                    <span class="brand-tagline">Cleaning • Maintenance • Procurement • Recruitment</span>
                </a>

                <div class="ms-auto d-flex align-items-center gap-4">

                    <a href="index.php#home" class="nav-link">Home</a>
                    <a href="index.php#about" class="nav-link">About Us</a>
                    <a href="index.php#services" class="nav-link">Services</a>
                    <a href="index.php#projects" class="nav-link">Projects</a>
                    <a href="index.php#contact" class="nav-link">Contact</a>

                    <?php if (isset($_SESSION['admin_id'])): ?>

                        <a href="dashboard.php" class="btn btn-brand">
                            Dashboard
                        </a>

                        <a href="dashboard.php?logout=1" class="nav-link">
                            Logout
                        </a>

                    <?php else: ?>

                        <a href="dashboard.php" class="btn btn-brand">
                            Admin Login
                        </a>

                    <?php endif; ?>

                </div>

            </nav>
        </div>
    </header>