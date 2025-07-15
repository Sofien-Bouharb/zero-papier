<?php
// index.php
session_start();
if (isset($_SESSION['admin_id'])) {
  header("Location: admin/dashboard.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Admin Login</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <style>
    body {
      background-color: #0f1218;
      color: #33d6e6;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background-image: url('../assets/bg2.avif');
      background-size: cover;
      background-repeat: no-repeat;
      background-position: center;
      background-attachment: fixed;
    }

    .login-container {
      background: #121820;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 0 6px #1da8b9cc;
      width: 320px;
    }

    h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #33d6e6;
    }

    label {
      color: #28a5b2;
    }

    input[type="text"],
    input[type="password"] {
      background: #0a0f14;
      width: 100%;
      border: 1px solid #28a5b2;
      color: #33d6e6;
      border-radius: 6px;
      padding: 0.5rem 0.75rem;
      transition: border-color 0.3s ease;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      outline: none;
      border-color: #33d6e6;
      background: #071017;
    }

    button {
      display: block;
      width: 100%;
      background-color: #33d6e6;
      border: none;
      color: #0a0f14;
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
  </style>
</head>

<body>
  <script src="js/bootstrap.bundle.min.js"></script>

  <div class="login-container">
    <!-- ✅ Message d'inactivité ici -->
    <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        ⏱️ Vous avez été déconnecté pour cause d'inactivité.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
    <?php endif; ?>

    <!-- Formulaire -->
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