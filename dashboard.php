<?php

require_once 'db.php';


// ============================================================
// SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ============================================================
// LOGOUT
// ============================================================

if (isset($_GET['logout'])) {

    session_destroy();

    header('Location: index.php');
    exit;
}


// ============================================================
// LOGIN
// ============================================================

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("
        SELECT id, username, password_hash
        FROM ksb_admin_users
        WHERE username = :username
    ");

    $stmt->execute([
        ':username' => $username
    ]);

    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        header('Location: dashboard.php');
        exit;
    } else {

        $login_error = 'Invalid username or password.';
    }
}


// ============================================================
// LOGIN PAGE
// ============================================================

if (!isset($_SESSION['admin_id'])):
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Admin Login - KSB ENTERPRISE 24 LIMITED</title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet">

        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
            rel="stylesheet">

        <link
            rel="stylesheet"
            href="style.css">

    </head>

    <body>

        <div class="container py-5">

            <div class="row justify-content-center">

                <div class="col-md-6 col-lg-4">

                    <div class="text-center mb-4">

                        <img
                            src="logo.png"
                            alt="KSB Enterprise 24 Limited"
                            style="max-width: 220px;">

                    </div>


                    <div class="card shadow-sm border-0">

                        <div class="card-body p-4">

                            <h3 class="mb-4 text-center">
                                Admin Login
                            </h3>


                            <?php if ($login_error): ?>

                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($login_error); ?>
                                </div>

                            <?php endif; ?>


                            <form method="POST">

                                <div class="mb-3">

                                    <label class="form-label">
                                        Username
                                    </label>

                                    <input
                                        type="text"
                                        name="username"
                                        class="form-control"
                                        required>

                                </div>


                                <div class="mb-3">

                                    <label class="form-label">
                                        Password
                                    </label>

                                    <input
                                        type="password"
                                        name="password"
                                        class="form-control"
                                        required>

                                </div>


                                <button
                                    type="submit"
                                    name="login"
                                    class="btn btn-brand w-100">

                                    Login

                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </body>

    </html>

<?php
    exit;
endif;


// ============================================================
// SUPABASE SETTINGS
// ============================================================

$supabase_url        = getenv('SUPABASE_URL');
$supabase_secret_key = getenv('SUPABASE_SECRET_KEY');
$supabase_bucket     = 'project-images';


// ============================================================
// SUPABASE IMAGE UPLOAD FUNCTION
// ============================================================

function uploadToSupabase($file)
{
    global $supabase_url;
    global $supabase_secret_key;
    global $supabase_bucket;


    // No file selected

    if (
        !isset($file) ||
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {

        return null;
    }


    // Upload error

    if ($file['error'] !== UPLOAD_ERR_OK) {

        throw new Exception(
            'Image upload failed. Please try again.'
        );
    }


    // Maximum file size: 5 MB

    if ($file['size'] > 5 * 1024 * 1024) {

        throw new Exception(
            'Image is too large. Maximum size is 5 MB.'
        );
    }


    // Allowed image types

    $allowed_types = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif'
    ];


    // Determine actual MIME type

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $mime_type = finfo_file(
        $finfo,
        $file['tmp_name']
    );

    finfo_close($finfo);


    if (!in_array($mime_type, $allowed_types, true)) {

        throw new Exception(
            'Only JPG, PNG, WEBP and GIF images are allowed.'
        );
    }


    // File extensions

    $extensions = [

        'image/jpeg' => 'jpg',

        'image/png' => 'png',

        'image/webp' => 'webp',

        'image/gif' => 'gif'

    ];


    $extension = $extensions[$mime_type];


    // Generate a unique filename

    $filename =
        'ksb_' .
        date('Ymd_His') .
        '_' .
        bin2hex(random_bytes(5)) .
        '.' .
        $extension;


    // Store KSB project images inside the ksb-projects folder

    $object_path = 'ksb-projects/' . $filename;


    // Supabase Storage upload URL

    $upload_url =
        rtrim($supabase_url, '/') .
        '/storage/v1/object/' .
        $supabase_bucket .
        '/' .
        $object_path;


    // Read uploaded file

    $file_contents = file_get_contents(
        $file['tmp_name']
    );


    if ($file_contents === false) {

        throw new Exception(
            'Unable to read the uploaded image.'
        );
    }


    // Upload to Supabase

    $ch = curl_init($upload_url);

    curl_setopt_array($ch, [

        CURLOPT_POST => true,

        CURLOPT_POSTFIELDS => $file_contents,

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [

            'Authorization: Bearer ' .
                $supabase_secret_key,

            'apikey: ' .
                $supabase_secret_key,

            'Content-Type: ' .
                $mime_type,

            'x-upsert: false'

        ]

    ]);


    $response = curl_exec($ch);

    $http_code = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    $curl_error = curl_error($ch);

    curl_close($ch);


    if ($response === false || $curl_error) {

        throw new Exception(
            'Unable to connect to Supabase Storage.'
        );
    }


    if ($http_code < 200 || $http_code >= 300) {

        throw new Exception(
            'Supabase Storage upload failed. HTTP ' .
                $http_code .
                '.'
        );
    }


    // Create public URL

    $public_url =
        rtrim($supabase_url, '/') .
        '/storage/v1/object/public/' .
        $supabase_bucket .
        '/' .
        $object_path;


    return $public_url;
}


// ============================================================
// MESSAGES
// ============================================================

$success_message = '';
$error_message   = '';


// ============================================================
// UPDATE SITE CONTENT
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_content'])
) {

    $section = $_POST['section'] ?? '';
    $title   = $_POST['title']   ?? '';
    $content = $_POST['content'] ?? '';


    if (
        in_array(
            $section,
            ['home', 'about', 'services', 'contact'],
            true
        )
    ) {

        $stmt = $pdo->prepare("
            UPDATE ksb_site_content
            SET
                title = :title,
                content = :content,
                updated_at = CURRENT_DATE
            WHERE section = :section
        ");


        $stmt->execute([

            ':title'   => $title,

            ':content' => $content,

            ':section' => $section

        ]);


        $success_message =
            'Site content updated successfully.';
    }
}


// ============================================================
// ADD GALLERY IMAGE
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_gallery'])
) {

    $title      = trim($_POST['title'] ?? '');
    $sort_order = (int) ($_POST['sort_order'] ?? 0);

    try {

        $image_path = uploadToSupabase(
            $_FILES['gallery_image'] ?? null
        );

        if (!$image_path) {

            throw new Exception(
                'Please select an image to upload.'
            );
        }

        $stmt = $pdo->prepare("
            INSERT INTO ksb_gallery
            (
                title,
                image_path,
                display,
                sort_order
            )
            VALUES
            (
                :title,
                :image_path,
                TRUE,
                :sort_order
            )
        ");

        $stmt->execute([

            ':title'      => $title,

            ':image_path' => $image_path,

            ':sort_order' => $sort_order

        ]);

        $success_message =
            'Gallery image added successfully.';
    } catch (Exception $e) {

        $error_message =
            $e->getMessage();
    }
}


// ============================================================
// UPDATE GALLERY IMAGE
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_gallery'])
) {

    $id         = (int) ($_POST['id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $sort_order = (int) ($_POST['sort_order'] ?? 0);

    try {

        // Get current image path

        $stmt = $pdo->prepare("
            SELECT image_path
            FROM ksb_gallery
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $existing = $stmt->fetch();

        if (!$existing) {

            throw new Exception(
                'Gallery image not found.'
            );
        }

        $image_path = $existing['image_path'];

        // Replace image if a new one was selected

        if (
            isset($_FILES['gallery_image']) &&
            $_FILES['gallery_image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $image_path = uploadToSupabase(
                $_FILES['gallery_image']
            );
        }

        $stmt = $pdo->prepare("
            UPDATE ksb_gallery
            SET
                title      = :title,
                image_path = :image_path,
                sort_order = :sort_order
            WHERE id = :id
        ");

        $stmt->execute([

            ':title'      => $title,

            ':image_path' => $image_path,

            ':sort_order' => $sort_order,

            ':id'         => $id

        ]);

        $success_message =
            'Gallery image updated successfully.';
    } catch (Exception $e) {

        $error_message =
            $e->getMessage();
    }
}


// ============================================================
// TOGGLE GALLERY VISIBILITY
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['toggle_gallery'])
) {

    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("
        UPDATE ksb_gallery
        SET display = NOT display
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $success_message =
        'Gallery image visibility updated.';
}


// ============================================================
// DELETE GALLERY IMAGE
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_gallery'])
) {

    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("
        DELETE FROM ksb_gallery
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $success_message =
        'Gallery image deleted successfully.';
}


// ============================================================
// ADD PROJECT
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_project'])
) {

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');


    try {

        $image_path = uploadToSupabase(
            $_FILES['image_path'] ?? null
        );


        $image_path_2 = uploadToSupabase(
            $_FILES['image_path_2'] ?? null
        );


        $stmt = $pdo->prepare("
            INSERT INTO ksb_projects
            (
                title,
                description,
                image_path,
                image_path_2,
                display
            )
            VALUES
            (
                :title,
                :description,
                :image_path,
                :image_path_2,
                TRUE
            )
        ");


        $stmt->execute([

            ':title'       => $title,

            ':description' => $description,

            ':image_path'  => $image_path,

            ':image_path_2' => $image_path_2

        ]);


        $success_message =
            'Project added successfully.';
    } catch (Exception $e) {

        $error_message =
            $e->getMessage();
    }
}


// ============================================================
// UPDATE PROJECT
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_project'])
) {

    $id          = (int) ($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');


    try {

        // Get current project

        $stmt = $pdo->prepare("
            SELECT image_path, image_path_2
            FROM ksb_projects
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $existing = $stmt->fetch();


        if (!$existing) {

            throw new Exception(
                'Project not found.'
            );
        }


        // Keep existing images

        $image_path   = $existing['image_path'];
        $image_path_2 = $existing['image_path_2'];


        // Replace Image 1 if a new image was selected

        if (
            isset($_FILES['image_path']) &&
            $_FILES['image_path']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $image_path = uploadToSupabase(
                $_FILES['image_path']
            );
        }


        // Replace Image 2 if a new image was selected

        if (
            isset($_FILES['image_path_2']) &&
            $_FILES['image_path_2']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $image_path_2 = uploadToSupabase(
                $_FILES['image_path_2']
            );
        }


        $stmt = $pdo->prepare("
            UPDATE ksb_projects
            SET
                title        = :title,
                description  = :description,
                image_path   = :image_path,
                image_path_2 = :image_path_2
            WHERE id = :id
        ");


        $stmt->execute([

            ':title'       => $title,

            ':description' => $description,

            ':image_path'  => $image_path,

            ':image_path_2' => $image_path_2,

            ':id'          => $id

        ]);


        $success_message =
            'Project updated successfully.';
    } catch (Exception $e) {

        $error_message =
            $e->getMessage();
    }
}


// ============================================================
// HIDE / SHOW PROJECT
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['toggle_project'])
) {

    $id = (int) ($_POST['id'] ?? 0);


    $stmt = $pdo->prepare("
        UPDATE ksb_projects
        SET display = NOT display
        WHERE id = :id
    ");


    $stmt->execute([
        ':id' => $id
    ]);


    $success_message =
        'Project visibility updated.';
}


// ============================================================
// ACTIVE SECTION
// ============================================================

$active_section = $_GET['section'] ?? '';


// ============================================================
// LOAD SITE CONTENT
// ============================================================

$content_rows = [];

if ($active_section === 'content') {

    $stmt = $pdo->query("
        SELECT
            id,
            section,
            title,
            content
        FROM ksb_site_content
        ORDER BY id
    ");

    $content_rows = $stmt->fetchAll();
}


// ============================================================
// LOAD PROJECTS
// ============================================================

$projects = [];

if ($active_section === 'projects') {

    $stmt = $pdo->query("
        SELECT
            id,
            title,
            description,
            image_path,
            image_path_2,
            display
        FROM ksb_projects
        ORDER BY id DESC
    ");

    $projects = $stmt->fetchAll();
}


// ============================================================
// LOAD MESSAGES
// ============================================================

$messages = [];

if ($active_section === 'messages') {

    $stmt = $pdo->query("
        SELECT
            id,
            name,
            email,
            message,
            created_at
        FROM ksb_contact_messages
        ORDER BY id DESC
    ");

    $messages = $stmt->fetchAll();
}

// ============================================================
// LOAD GALLERY
// ============================================================

$gallery = [];

if ($active_section === 'gallery') {

    $stmt = $pdo->query("
        SELECT
            id,
            title,
            image_path,
            display,
            sort_order,
            created_at
        FROM ksb_gallery
        ORDER BY sort_order ASC, id ASC
    ");

    $gallery = $stmt->fetchAll();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Dashboard - KSB ENTERPRISE 24 LIMITED
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="style.css">

</head>

<body>


    <!-- ============================================================
     DASHBOARD HEADER
============================================================= -->

    <header class="site-header">

        <div class="container">

            <nav class="navbar py-3">

                <a
                    class="navbar-brand"
                    href="dashboard.php">

                    <img
                        src="logo.png"
                        alt="KSB Enterprise 24 Limited"
                        class="company-logo">

                </a>


                <div class="ms-auto d-flex gap-3 align-items-center">

                    <a
                        href="index.php"
                        class="btn btn-brand">

                        View Website

                    </a>


                    <a
                        href="dashboard.php?logout=1"
                        class="nav-link">

                        Logout

                    </a>

                </div>

            </nav>

        </div>

    </header>


    <!-- ============================================================
     DASHBOARD CONTENT
============================================================= -->

    <div class="container py-5">

        <div class="mb-4">

            <h1>
                Administration
            </h1>

            <p class="text-muted">
                Dashboard
            </p>

            <p>
                Welcome,
                <strong>
                    <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                </strong>
            </p>

        </div>


        <?php if ($success_message): ?>

            <div class="alert alert-success">

                <?php echo htmlspecialchars($success_message); ?>

            </div>

        <?php endif; ?>


        <?php if ($error_message): ?>

            <div class="alert alert-danger">

                <?php echo htmlspecialchars($error_message); ?>

            </div>

        <?php endif; ?>


        <!-- ========================================================
         DASHBOARD CARDS
    ========================================================= -->

        <?php if ($active_section === ''): ?>

            <div class="row g-4">

                <div class="col-md-4">

                    <a
                        href="dashboard.php?section=content"
                        class="text-decoration-none">

                        <div class="card h-100 shadow-sm border-0">

                            <div class="card-body p-4">

                                <i class="bi bi-pencil-square fs-2"></i>

                                <h4 class="mt-3">
                                    Site Content
                                </h4>

                                <p class="text-muted">
                                    Manage Home, About Us, Services and Contact content.
                                </p>

                            </div>

                        </div>

                    </a>

                </div>


                <div class="col-md-4">

                    <a
                        href="dashboard.php?section=projects"
                        class="text-decoration-none">

                        <div class="card h-100 shadow-sm border-0">

                            <div class="card-body p-4">

                                <i class="bi bi-images fs-2"></i>

                                <h4 class="mt-3">
                                    Projects
                                </h4>

                                <p class="text-muted">
                                    Add, edit and manage project images.
                                </p>

                            </div>

                        </div>

                    </a>

                </div>


                <div class="col-md-4">

                    <a
                        href="dashboard.php?section=messages"
                        class="text-decoration-none">

                        <div class="card h-100 shadow-sm border-0">

                            <div class="card-body p-4">

                                <i class="bi bi-envelope fs-2"></i>

                                <h4 class="mt-3">
                                    Messages
                                </h4>

                                <p class="text-muted">
                                    View messages submitted through the website.
                                </p>

                            </div>

                        </div>

                    </a>

                </div>

                <div class="col-md-4">

                    <a
                        href="dashboard.php?section=gallery"
                        class="text-decoration-none">

                        <div class="card h-100 shadow-sm border-0">

                            <div class="card-body p-4">

                                <i class="bi bi-collection fs-2"></i>

                                <h4 class="mt-3">
                                    Gallery
                                </h4>

                                <p class="text-muted">
                                    Upload and manage carousel images.
                                </p>

                            </div>

                        </div>

                    </a>

                </div>

            </div>

        <?php endif; ?>


        <!-- ========================================================
         SITE CONTENT
    ========================================================= -->

        <?php if ($active_section === 'content'): ?>

            <div class="mb-4">

                <a
                    href="dashboard.php"
                    class="btn btn-outline-secondary">

                    ← Dashboard

                </a>

            </div>


            <h2 class="mb-4">
                Site Content
            </h2>


            <?php foreach ($content_rows as $row): ?>

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-body p-4">

                        <h4 class="mb-4 text-capitalize">

                            <?php echo htmlspecialchars($row['section']); ?>

                        </h4>


                        <form method="POST">

                            <input
                                type="hidden"
                                name="section"
                                value="<?php echo htmlspecialchars($row['section']); ?>">


                            <div class="mb-3">

                                <label class="form-label">
                                    Title
                                </label>

                                <input
                                    type="text"
                                    name="title"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($row['title'] ?? ''); ?>">

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Content
                                </label>

                                <textarea
                                    name="content"
                                    rows="6"
                                    class="form-control"><?php echo htmlspecialchars($row['content'] ?? ''); ?></textarea>

                            </div>


                            <button
                                type="submit"
                                name="update_content"
                                class="btn btn-brand">

                                Save Changes

                            </button>

                        </form>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>


        <!-- ========================================================
         PROJECTS
    ========================================================= -->

        <?php if ($active_section === 'projects'): ?>

            <div class="mb-4">

                <a
                    href="dashboard.php"
                    class="btn btn-outline-secondary">

                    ← Dashboard

                </a>

            </div>


            <h2 class="mb-4">
                Projects
            </h2>


            <!-- ADD PROJECT -->

            <div class="card shadow-sm border-0 mb-5">

                <div class="card-body p-4">

                    <h4 class="mb-4">
                        Add Project
                    </h4>


                    <form
                        method="POST"
                        enctype="multipart/form-data">

                        <div class="mb-3">

                            <label class="form-label">
                                Project Title
                            </label>

                            <input
                                type="text"
                                name="title"
                                class="form-control"
                                required>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea
                                name="description"
                                rows="5"
                                class="form-control"></textarea>

                        </div>


                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    Image 1
                                </label>

                                <input
                                    type="file"
                                    name="image_path"
                                    class="form-control"
                                    accept="image/jpeg,image/png,image/webp,image/gif">

                                <small class="text-muted">
                                    Maximum 5 MB.
                                </small>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Image 2
                                </label>

                                <input
                                    type="file"
                                    name="image_path_2"
                                    class="form-control"
                                    accept="image/jpeg,image/png,image/webp,image/gif">

                                <small class="text-muted">
                                    Maximum 5 MB.
                                </small>

                            </div>

                        </div>


                        <button
                            type="submit"
                            name="add_project"
                            class="btn btn-brand mt-4">

                            Add Project

                        </button>

                    </form>

                </div>

            </div>


            <!-- EXISTING PROJECTS -->

            <h3 class="mb-4">
                Existing Projects
            </h3>


            <?php if (count($projects) === 0): ?>

                <div class="alert alert-info">

                    No projects have been added yet.

                </div>

            <?php endif; ?>


            <?php foreach ($projects as $project): ?>

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-body p-4">

                        <div class="d-flex justify-content-between align-items-center mb-4">

                            <h4 class="mb-0">

                                <?php echo htmlspecialchars($project['title']); ?>

                            </h4>


                            <?php if ($project['display']): ?>

                                <span class="badge bg-success">
                                    Visible
                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary">
                                    Hidden
                                </span>

                            <?php endif; ?>

                        </div>


                        <form
                            method="POST"
                            enctype="multipart/form-data">

                            <input
                                type="hidden"
                                name="id"
                                value="<?php echo $project['id']; ?>">


                            <div class="mb-3">

                                <label class="form-label">
                                    Project Title
                                </label>

                                <input
                                    type="text"
                                    name="title"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($project['title']); ?>"
                                    required>

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Description
                                </label>

                                <textarea
                                    name="description"
                                    rows="5"
                                    class="form-control"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>

                            </div>


                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        Replace Image 1
                                    </label>

                                    <input
                                        type="file"
                                        name="image_path"
                                        class="form-control"
                                        accept="image/jpeg,image/png,image/webp,image/gif">

                                    <?php if (!empty($project['image_path'])): ?>

                                        <small class="text-muted d-block mt-2">
                                            Current Image 1 is already stored.
                                        </small>

                                    <?php endif; ?>

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Replace Image 2
                                    </label>

                                    <input
                                        type="file"
                                        name="image_path_2"
                                        class="form-control"
                                        accept="image/jpeg,image/png,image/webp,image/gif">

                                    <?php if (!empty($project['image_path_2'])): ?>

                                        <small class="text-muted d-block mt-2">
                                            Current Image 2 is already stored.
                                        </small>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <button
                                type="submit"
                                name="update_project"
                                class="btn btn-brand mt-4">

                                Save Changes

                            </button>

                        </form>


                        <form
                            method="POST"
                            class="mt-2">

                            <input
                                type="hidden"
                                name="id"
                                value="<?php echo $project['id']; ?>">


                            <button
                                type="submit"
                                name="toggle_project"
                                class="btn btn-outline-secondary">

                                <?php if ($project['display']): ?>

                                    Hide

                                <?php else: ?>

                                    Show

                                <?php endif; ?>

                            </button>

                        </form>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>


        <!-- ========================================================
         MESSAGES
    ========================================================= -->

        <?php if ($active_section === 'messages'): ?>

            <div class="mb-4">

                <a
                    href="dashboard.php"
                    class="btn btn-outline-secondary">

                    ← Dashboard

                </a>

            </div>


            <h2 class="mb-4">
                Messages
            </h2>


            <?php if (count($messages) === 0): ?>

                <div class="alert alert-info">

                    No messages have been received yet.

                </div>

            <?php else: ?>

                <?php foreach ($messages as $message): ?>

                    <div class="card shadow-sm border-0 mb-3">

                        <div class="card-body p-4">

                            <div class="d-flex justify-content-between flex-wrap gap-2">

                                <h5 class="mb-0">

                                    <?php echo htmlspecialchars($message['name']); ?>

                                </h5>


                                <small class="text-muted">

                                    <?php echo htmlspecialchars($message['created_at']); ?>

                                </small>

                            </div>


                            <p class="mt-2 mb-2">

                                <i class="bi bi-envelope me-2"></i>

                                <a
                                    href="mailto:<?php echo htmlspecialchars($message['email']); ?>">

                                    <?php echo htmlspecialchars($message['email']); ?>

                                </a>

                            </p>


                            <p class="mb-0">

                                <?php
                                echo nl2br(
                                    htmlspecialchars($message['message'])
                                );
                                ?>

                            </p>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        <?php endif; ?>


        <!-- ========================================================
         GALLERY
    ========================================================= -->

        <?php if ($active_section === 'gallery'): ?>

            <div class="mb-4">

                <a
                    href="dashboard.php"
                    class="btn btn-outline-secondary">

                    ← Dashboard

                </a>

            </div>


            <h2 class="mb-4">
                Gallery
            </h2>


            <!-- ADD IMAGE -->

            <div class="card shadow-sm border-0 mb-5">

                <div class="card-body p-4">

                    <h4 class="mb-4">
                        Add Gallery Image
                    </h4>


                    <form
                        method="POST"
                        enctype="multipart/form-data">

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    Image
                                </label>

                                <input
                                    type="file"
                                    name="gallery_image"
                                    class="form-control"
                                    accept="image/jpeg,image/png,image/webp,image/gif"
                                    required>

                                <small class="text-muted">
                                    Maximum 5 MB. Recommended 1920 × 800.
                                </small>

                            </div>


                            <div class="col-md-4">

                                <label class="form-label">
                                    Caption (optional)
                                </label>

                                <input
                                    type="text"
                                    name="title"
                                    class="form-control">

                            </div>


                            <div class="col-md-2">

                                <label class="form-label">
                                    Sort order
                                </label>

                                <input
                                    type="number"
                                    name="sort_order"
                                    class="form-control"
                                    value="0">

                                <small class="text-muted">
                                    Lower = first.
                                </small>

                            </div>

                        </div>


                        <button
                            type="submit"
                            name="add_gallery"
                            class="btn btn-brand mt-4">

                            Add Image

                        </button>

                    </form>

                </div>

            </div>


            <!-- EXISTING IMAGES -->

            <h3 class="mb-4">
                Existing Images
            </h3>


            <?php if (count($gallery) === 0): ?>

                <div class="alert alert-info">

                    No gallery images have been added yet.

                </div>

            <?php endif; ?>


            <div class="row g-4">

                <?php foreach ($gallery as $image): ?>

                    <div class="col-md-6 col-lg-4">

                        <div class="card shadow-sm border-0 h-100">

                            <img
                                src="<?php echo htmlspecialchars($image['image_path']); ?>"
                                alt="<?php echo htmlspecialchars($image['title'] ?? 'Gallery image'); ?>"
                                style="width: 100%; height: 180px; object-fit: cover;">

                            <div class="card-body p-3">

                                <div class="d-flex justify-content-between align-items-center mb-3">

                                    <span class="badge <?php echo $image['display'] ? 'bg-success' : 'bg-secondary'; ?>">

                                        <?php echo $image['display'] ? 'Visible' : 'Hidden'; ?>

                                    </span>


                                    <small class="text-muted">

                                        #<?php echo $image['sort_order']; ?>

                                    </small>

                                </div>


                                <form
                                    method="POST"
                                    enctype="multipart/form-data">

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?php echo $image['id']; ?>">


                                    <div class="mb-2">

                                        <label class="form-label small">
                                            Caption
                                        </label>

                                        <input
                                            type="text"
                                            name="title"
                                            class="form-control form-control-sm"
                                            value="<?php echo htmlspecialchars($image['title'] ?? ''); ?>">

                                    </div>


                                    <div class="mb-2">

                                        <label class="form-label small">
                                            Sort order
                                        </label>

                                        <input
                                            type="number"
                                            name="sort_order"
                                            class="form-control form-control-sm"
                                            value="<?php echo $image['sort_order']; ?>">

                                    </div>


                                    <div class="mb-3">

                                        <label class="form-label small">
                                            Replace image (optional)
                                        </label>

                                        <input
                                            type="file"
                                            name="gallery_image"
                                            class="form-control form-control-sm"
                                            accept="image/jpeg,image/png,image/webp,image/gif">

                                    </div>


                                    <button
                                        type="submit"
                                        name="update_gallery"
                                        class="btn btn-brand btn-sm w-100">

                                        Save Changes

                                    </button>

                                </form>


                                <form
                                    method="POST"
                                    class="mt-2">

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?php echo $image['id']; ?>">

                                    <button
                                        type="submit"
                                        name="toggle_gallery"
                                        class="btn btn-outline-secondary btn-sm w-100">

                                        <?php echo $image['display'] ? 'Hide' : 'Show'; ?>

                                    </button>

                                </form>


                                <form
                                    method="POST"
                                    class="mt-2"
                                    onsubmit="return confirm('Permanently delete this image? This cannot be undone.');">

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?php echo $image['id']; ?>">

                                    <button
                                        type="submit"
                                        name="delete_gallery"
                                        class="btn btn-outline-danger btn-sm w-100">

                                        Delete

                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>


</body>

</html>