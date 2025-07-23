<?php
//start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

// Check if user is already logged in
if (isset($_SESSION['admin_id'])) {
  header("Location: admin/dashboard.php");
  exit();
}
// Update last activity time
$_SESSION['LAST_ACTIVITY'] = time();
//Session messages handling
if (isset($_SESSION['error_message'])):
?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
    <?= htmlspecialchars($_SESSION['error_message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
  </div>
<?php unset($_SESSION['error_message']);
endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
    <?= htmlspecialchars($_SESSION['success_message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
  </div>
<?php unset($_SESSION['success_message']);
endif; ?>

<script src="js/bootstrap.bundle.min.js"></script>
<script>
  setTimeout(function() {
    const alert = document.querySelector('.alert');
    if (alert) {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    }
  }, 4000);
</script>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Admin Login</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <style>
    body {
      background-color: #eaeaea;
      color: #ffffff;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .login-container {

      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 0 6px #bdd284;
      width: 320px;
    }

    h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #383131ff;
    }

    label {
      color: #000;
    }

    input[type="text"],
    input[type="password"] {
      background: #ffffff;
      width: 100%;
      border: 1px solid #28a5b2;
      border-radius: 6px;
      padding: 0.5rem 0.75rem;
      transition: border-color 0.3s ease;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      outline: none;
      border-color: #33d6e6;

    }

    button {
      display: block;
      width: 100%;
      background-color: #bdd284;
      border: none;
      color: #000;
      font-weight: bold;
      padding: 0.5rem 0;
      border-radius: 8px;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #28a5b2;
      color: #f0f9fb;
    }

    .alert {
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }

    .nav-link {
      color: #00d6ff !important;
      font-weight: bold;
    }

    .nav-link:hover {
      text-decoration: underline;
    }

    .navbar .nav-link {
      color: #fff !important;
    }

    .nav-link.active {
      color: #90969D !important;
    }
  </style>
</head>

<body>



  <!-- Navigation bar -->
  <nav class="navbar fixed-top navbar-expand-lg navbar-dark border-bottom border-info shadow-sm mb-4" style="background-color: #000;">
    <div class="container-fluid">

      <a class="navbar-brand" href="#">
        <img src="..\assets\logo.png" alt="Company Logo" height="48">
      </a>


    </div>
  </nav>

  <script src="js/bootstrap.bundle.min.js"></script>

  <div class="login-container">
    <!-- Idle message -->
    <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        ⏱️ Vous avez été déconnecté pour cause d'inactivité.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
    <?php endif; ?>

    <!-- Form -->
    <form action="login.php" method="POST" autocomplete="off" spellcheck="false">
      <h2>Admin Login</h2>
      <div class="mb-3">
        <label for="username">Username</label>
        <input
          type="text"
          name="username"
          id="username"
          required
          autofocus
          autocomplete="username" />
      </div>
      <div class="mb-3">
        <label for="password">Password</label>
        <input
          type="password"
          name="password"
          id="password"
          required
          autocomplete="current-password" />
      </div>
      <button type="submit">Login</button>
    </form>
  </div>
</body>

</html>