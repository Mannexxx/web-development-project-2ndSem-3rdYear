<?php $signedIn = !empty($_SESSION['user']); ?>
<header class="wrap topbar">
  <a class="brand" href="index.php">BANTAYAN<br>MARKETPLACE</a>
  <form action="products.php" method="get" style="display:flex;flex:1"><input class="search" name="search" placeholder="Search products on Bantayan Island" value="<?= htmlspecialchars((string)($_GET['search'] ?? '')) ?>"></form>
  <nav class="toplinks">
    <?php if ($signedIn): ?>
      <a href="messages.php" class="wide">Messages</a>
      <a class="wide" href="profile.php?id=<?= (int) $_SESSION['user']['id'] ?>"><?= htmlspecialchars((string) ($_SESSION['user']['name'] ?? 'Profile')) ?></a>
      <form method="post" action="logout.php" onsubmit="return window.confirm('Are you sure you want to log out?')">
        <?= csrf_field() ?>
        <button type="submit">Logout</button>
      </form>
    <?php else: ?>
      <a class="button" href="login.php">Sign in</a>
      <a class="button" href="register.php">Register</a>
    <?php endif; ?>
    <a class="button dark" href="sell.php">Sell a product</a>
  </nav>
</header>
