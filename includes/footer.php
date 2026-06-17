<?php
// includes/footer.php
?>
    <!-- Toast -->
    <div id="toast"></div>

  </div><!-- end #main-content -->
</div><!-- end #app -->

<script src="/SU-Housing/assets/js/main.js"></script>

<?php
// Page-specific scripts — set $extraScripts array before including footer
if (!empty($extraScripts)):
  foreach ($extraScripts as $script):
?>
  <script src="<?php echo $script; ?>"></script>
<?php
  endforeach;
endif;
?>

</body>
</html>