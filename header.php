<!DOCTYPE html>
<html lang="en">
<head>
<!-- Favicon -->
<link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon/favicon.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon/favicon.png">
<link rel="apple-touch-icon" href="/assets/images/favicon/favicon.png">
  
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Main Channel Brewing</title>

<link rel="stylesheet" href="style.css?v=10">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<header class="site-header" id="siteHeader">
  <div class="header-inner">

    <div class="logo-container">
      <a href="index.php#hero" aria-label="Main Channel Brewing home">
        <img
          src="assets/images/logos/logo-white.png"
          alt="Main Channel Brewing Company Logo"
          class="site-logo"
        >
      </a>
    </div>

    <div class="nav-right">
      <button
        class="nav-toggle"
        id="navToggle"
        type="button"
        aria-label="Toggle navigation menu"
        aria-expanded="false"
        aria-controls="navPanel"
      >
        <span class="nav-toggle-bar" aria-hidden="true"></span>
      </button>

      <div class="nav-panel" id="navPanel">
        <nav class="main-nav" aria-label="Primary">
          <a href="index.php#hero">Home</a>
          <a href="index.php#about">About</a>
          <a href="index.php#seasonal-specials">Seasonal</a>
          <a href="index.php#beer-menu">Beer Menu</a>
          <a href="index.php#merch">Merch</a>
          <a href="index.php#locations">Locations</a>
          <a href="index.php#contact">Contact</a>
        </nav>

        <div class="social-nav" aria-label="Social media">
          <a href="https://www.facebook.com/MainChannel" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
            <i class="fab fa-facebook-f" aria-hidden="true"></i>
          </a>
          <a href="https://x.com/MainChannelBeer" target="_blank" rel="noopener noreferrer" aria-label="X">
            <i class="fab fa-x-twitter" aria-hidden="true"></i>
          </a>
          <a href="https://www.instagram.com/mainchannelbeer/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
            <i class="fab fa-instagram" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>

  </div>
</header>

<script>
(function () {
    const header = document.getElementById('siteHeader');
    const navToggle = document.getElementById('navToggle');
    const navLinks = Array.from(document.querySelectorAll('.main-nav a'));

    function getSections() {
        return navLinks
            .map((link) => {
                const href = link.getAttribute('href') || '';
                const hashIndex = href.indexOf('#');
                if (hashIndex === -1) {
                    return null;
                }
                return document.querySelector(href.slice(hashIndex));
            })
            .filter(Boolean);
    }

    function closeMenu() {
        header.classList.remove('menu-open');
        navToggle.setAttribute('aria-expanded', 'false');
    }

    function openMenu() {
        header.classList.add('menu-open');
        navToggle.setAttribute('aria-expanded', 'true');
    }

    navToggle.addEventListener('click', function () {
        if (header.classList.contains('menu-open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 860) {
                closeMenu();
            }
        });
    });

    document.addEventListener('click', function (event) {
        if (!header.contains(event.target) && header.classList.contains('menu-open')) {
            closeMenu();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 860) {
            closeMenu();
        }
    });

    function setActiveLink() {
        const sections = getSections();
        const scrollPosition = window.scrollY + 140;
        let currentId = 'hero';

        sections.forEach(function (section) {
            if (scrollPosition >= section.offsetTop) {
                currentId = section.id;
            }
        });

        navLinks.forEach(function (link) {
            const href = link.getAttribute('href') || '';
            const target = href.includes('#') ? href.split('#')[1] : '';
            link.classList.toggle('active', target === currentId);
        });
    }

    setActiveLink();
    window.addEventListener('scroll', setActiveLink, { passive: true });
})();
</script>

<main>
