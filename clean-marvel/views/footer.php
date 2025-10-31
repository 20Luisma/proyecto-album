<?php

declare(strict_types=1);

$scripts = $scripts ?? [];
?>

  <!-- FOOTER -->
  <footer class="site-footer">
    <small>© creawebes 2025 · Clean Marvel Album</small>
  </footer>

  <?php foreach ($scripts as $script): ?>
    <script type="module" src="<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>"></script>
  <?php endforeach; ?>
</body>
</html>
