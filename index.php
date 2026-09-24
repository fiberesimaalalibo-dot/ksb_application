<?php

require_once 'db.php';
include 'header.php';


// ============================================================
// HANDLE CONTACT FORM
// ============================================================

$contact_success = '';
$contact_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {

        $contact_error = 'Please complete all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $contact_error = 'Please enter a valid email address.';
    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO ksb_contact_messages (name, email, message)
                VALUES (:name, :email, :message)
            ");

            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':message' => $message
            ]);

            $contact_success = 'Thank you. Your message has been sent successfully.';
        } catch (PDOException $e) {

            $contact_error = 'Unable to send your message. Please try again.';
        }
    }
}


// ============================================================
// GET SITE CONTENT
// ============================================================

$stmt = $pdo->query("
    SELECT section, title, content
    FROM ksb_site_content
");

$site_content = [];

while ($row = $stmt->fetch()) {
    $site_content[$row['section']] = $row;
}

// Home
$home_title   = $site_content['home']['title']     ?? 'Welcome';
$home_content = $site_content['home']['content']   ?? '';

// About
$about_title   = $site_content['about']['title']   ?? 'About Us';
$about_content = $site_content['about']['content'] ?? '';

// Services
$services_title   = $site_content['services']['title']   ?? 'Our Services';
$services_content = $site_content['services']['content'] ?? '';

// Contact
$contact_title   = $site_content['contact']['title']   ?? 'Contact Us';
$contact_content = $site_content['contact']['content'] ?? '';


// ============================================================
// SERVICES LIST (7 ITEMS)
// ============================================================

$services = [
    [
        'icon'  => 'bi-droplet-fill',
        'title' => 'Industrial Cleaning',
        'desc'  => 'Deep cleaning solutions for factories, plants and commercial facilities.'
    ],
    [
        'icon'  => 'bi-tools',
        'title' => 'Facility Maintenance',
        'desc'  => 'Scheduled upkeep and repairs to keep your facilities running smoothly.'
    ],
    [
        'icon'  => 'bi-tree-fill',
        'title' => 'Horticultural & Landscaping Services',
        'desc'  => 'Professional landscaping, gardening and green space management.'
    ],
    [
        'icon'  => 'bi-recycle',
        'title' => 'Waste Management',
        'desc'  => 'Responsible collection, disposal and recycling of industrial waste.'
    ],
    [
        'icon'  => 'bi-lightning-charge-fill',
        'title' => 'Civil & Electrical Works',
        'desc'  => 'Civil construction and certified electrical installation services.'
    ],
    [
        'icon'  => 'bi-cart-fill',
        'title' => 'General Procurement (Local & Foreign)',
        'desc'  => 'Sourcing and supply of equipment, materials and goods worldwide.'
    ],
    [
        'icon'  => 'bi-people-fill',
        'title' => 'Personnel Recruitment',
        'desc'  => 'Skilled and unskilled workforce recruitment for every sector.'
    ],
];


// ============================================================
// GET VISIBLE PROJECTS
// ============================================================

$stmt = $pdo->query("
    SELECT id, title, description, image_path, image_path_2
    FROM ksb_projects
    WHERE display = TRUE
    ORDER BY id DESC
");

$projects = $stmt->fetchAll();



// ============================================================
// GET VISIBLE GALLERY IMAGES
// ============================================================

$stmt = $pdo->query("
SELECT id, title, image_path
FROM ksb_gallery
WHERE display = TRUE
ORDER BY sort_order ASC, id ASC
");

$gallery = $stmt->fetchAll();

?>
<!-- ============================================================
     HERO
============================================================= -->

<section id="home" class="hero-section">

    <div class="container">

        <div class="row align-items-center min-vh-75">

            <div class="col-lg-7">

                <span class="hero-label">
                    INDUSTRIAL • ENVIRONMENTAL • WORKFORCE SOLUTIONS
                </span>

                <h1 class="hero-title">
                    <?php echo htmlspecialchars($home_title); ?>
                </h1>

                <p class="hero-text">
                    <?php echo htmlspecialchars($home_content); ?>
                </p>

                <div class="d-flex gap-3 mt-4 flex-wrap">

                    <a href="#services" class="btn btn-brand btn-lg">
                        Our Services
                    </a>

                    <a href="#contact" class="btn btn-outline-brand btn-lg">
                        Contact Us
                    </a>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ============================================================
     ABOUT
============================================================= -->

<section id="about" class="section-padding">

    <div class="container">

        <div class="section-heading">

            <span>ABOUT US</span>

            <h2>
                <?php echo htmlspecialchars($about_title); ?>
            </h2>

        </div>

        <div class="row">

            <div class="col-lg-8">

                <div class="lead">
                    <?php echo nl2br(htmlspecialchars($about_content)); ?>
                </div>

            </div>

        </div>

    </div>

</section>


<!-- ============================================================
     SERVICES
============================================================= -->

<section id="services" class="services-section section-padding">

    <div class="container">

        <div class="section-heading text-center mx-auto">

            <span>OUR SERVICES</span>

            <h2>
                <?php echo htmlspecialchars($services_title); ?>
            </h2>

            <p>
                <?php echo htmlspecialchars($services_content); ?>
            </p>

        </div>


        <div class="row g-4">

            <?php foreach ($services as $service): ?>

                <div class="col-md-6 col-lg-4">

                    <div class="service-card">

                        <div class="service-icon">

                            <i class="bi <?php echo $service['icon']; ?>"></i>

                        </div>

                        <h3 class="service-title">
                            <?php echo htmlspecialchars($service['title']); ?>
                        </h3>

                        <p class="service-desc">
                            <?php echo htmlspecialchars($service['desc']); ?>
                        </p>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>

<!-- ============================================================
     GALLERY
============================================================= -->

<section id="gallery" class="gallery-section section-padding">

    <div class="container">

        <div class="section-heading text-center mx-auto">

            <span>OUR GALLERY</span>

            <h2>
                A glimpse of our work
            </h2>

            <p>
                Highlights from our projects, operations and team in the field.
            </p>

        </div>


        <?php if (count($gallery) > 0): ?>

            <div
                id="ksbGalleryCarousel"
                class="carousel slide ksb-carousel"
                data-bs-ride="carousel"
                data-bs-interval="5000">

                <!-- Indicators -->

                <div class="carousel-indicators">

                    <?php foreach ($gallery as $index => $image): ?>

                        <button
                            type="button"
                            data-bs-target="#ksbGalleryCarousel"
                            data-bs-slide-to="<?php echo $index; ?>"
                            class="<?php echo $index === 0 ? 'active' : ''; ?>"
                            aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                            aria-label="Slide <?php echo $index + 1; ?>">
                        </button>

                    <?php endforeach; ?>

                </div>


                <!-- Slides -->

                <div class="carousel-inner">

                    <?php foreach ($gallery as $index => $image): ?>

                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">

                            <img
                                src="<?php echo htmlspecialchars($image['image_path']); ?>"
                                alt="<?php echo htmlspecialchars($image['title'] ?? 'KSB Enterprise gallery image'); ?>"
                                class="ksb-carousel-image">

                            <?php if (!empty($image['title'])): ?>

                                <div class="carousel-caption ksb-carousel-caption d-none d-md-block">

                                    <h5>
                                        <?php echo htmlspecialchars($image['title']); ?>
                                    </h5>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>


                <!-- Controls -->

                <button
                    class="carousel-control-prev"
                    type="button"
                    data-bs-target="#ksbGalleryCarousel"
                    data-bs-slide="prev">

                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>

                    <span class="visually-hidden">Previous</span>

                </button>


                <button
                    class="carousel-control-next"
                    type="button"
                    data-bs-target="#ksbGalleryCarousel"
                    data-bs-slide="next">

                    <span class="carousel-control-next-icon" aria-hidden="true"></span>

                    <span class="visually-hidden">Next</span>

                </button>

            </div>

        <?php else: ?>

            <div class="alert alert-info text-center">

                Gallery images will appear here soon.

            </div>

        <?php endif; ?>

    </div>

</section>



<!-- ============================================================
     PROJECTS
============================================================= -->

<section id="projects" class="projects-section section-padding">

    <div class="container">

        <div class="section-heading text-center mx-auto">

            <span>OUR PROJECTS</span>

            <h2>
                Selected projects and engagements
            </h2>

            <p>
                Explore some of the projects delivered by KSB Enterprise 24 Limited.
            </p>

        </div>


        <div class="row g-4">

            <?php if (count($projects) > 0): ?>

                <?php foreach ($projects as $project): ?>

                    <div class="col-md-6 col-lg-4">

                        <article class="project-card">

                            <?php if (!empty($project['image_path_2'])): ?>

                                <div class="project-image-wrapper">

                                    <img
                                        src="<?php echo htmlspecialchars($project['image_path']); ?>"
                                        alt="<?php echo htmlspecialchars($project['title']); ?>"
                                        class="project-image project-image-one">

                                    <img
                                        src="<?php echo htmlspecialchars($project['image_path_2']); ?>"
                                        alt="<?php echo htmlspecialchars($project['title']); ?>"
                                        class="project-image project-image-two">

                                </div>

                            <?php elseif (!empty($project['image_path'])): ?>

                                <img
                                    src="<?php echo htmlspecialchars($project['image_path']); ?>"
                                    alt="<?php echo htmlspecialchars($project['title']); ?>"
                                    class="project-image">

                            <?php endif; ?>


                            <div class="project-body">

                                <span class="project-category">
                                    PROJECT
                                </span>

                                <h3>
                                    <?php echo htmlspecialchars($project['title']); ?>
                                </h3>

                                <p>
                                    <?php echo nl2br(htmlspecialchars($project['description'] ?? '')); ?>
                                </p>

                            </div>

                        </article>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12 text-center">

                    <p>
                        No projects are currently available.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- ============================================================
     CONTACT
============================================================= -->

<section id="contact" class="contact-section section-padding">

    <div class="container">

        <div class="row g-5">

            <div class="col-lg-5">

                <div class="section-heading">

                    <span>CONTACT US</span>

                    <h2>
                        <?php echo htmlspecialchars($contact_title); ?>
                    </h2>

                </div>

                <div>
                    <?php echo nl2br(htmlspecialchars($contact_content)); ?>
                </div>

                <div class="contact-details">

                    <div>
                        <i class="bi bi-telephone"></i>
                        <span>Phone number</span>
                    </div>

                    <div>
                        <i class="bi bi-envelope"></i>
                        <span>Email address</span>
                    </div>

                    <div>
                        <i class="bi bi-geo-alt"></i>
                        <span>Port Harcourt, Rivers State</span>
                    </div>

                </div>

            </div>


            <div class="col-lg-7">

                <div class="contact-card">

                    <?php if ($contact_success): ?>

                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($contact_success); ?>
                        </div>

                    <?php endif; ?>


                    <?php if ($contact_error): ?>

                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($contact_error); ?>
                        </div>

                    <?php endif; ?>


                    <form method="POST">

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">Your Name</label>

                                <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    required>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">Email Address</label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    required>

                            </div>


                            <div class="col-12">

                                <label class="form-label">Message</label>

                                <textarea
                                    name="message"
                                    rows="6"
                                    class="form-control"
                                    required></textarea>

                            </div>


                            <div class="col-12">

                                <button
                                    type="submit"
                                    name="contact_submit"
                                    class="btn btn-brand btn-lg">

                                    Send Message

                                </button>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</section>


<?php include 'footer.php'; ?>