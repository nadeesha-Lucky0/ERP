  <!-- Desk Toast Container -->
  <div id="deskToast" class="desk-toast"></div>

  <script src="<?= BASE_PATH ?>/assets/js/erp-desk.js?v=<?= time() ?>"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Desk.init('<?= BASE_PATH ?>');
    });
  </script>
</body>
</html>

