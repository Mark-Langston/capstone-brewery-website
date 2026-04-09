</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-legal">
      <p class="responsibility-msg">PLEASE DRINK RESPONSIBLY</p>
      <p>&copy; <?php echo date("Y"); ?> Main Channel Brewing. All rights reserved.</p>
    </div>
  </div>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const ageGate = document.getElementById("age-gate");
    const yesBtn = document.getElementById("age-yes");
    const noBtn = document.getElementById("age-no");

    if (localStorage.getItem("ageVerified") === "true") {
      ageGate.style.display = "none";
    }

    yesBtn.addEventListener("click", function () {
      localStorage.setItem("ageVerified", "true");
      ageGate.style.display = "none";
    });

    noBtn.addEventListener("click", function () {
      window.location.href = "https://www.google.com";
    });
});
</script>

</body>
</html>
