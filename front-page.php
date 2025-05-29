<?php
/**
 * Template Name: Cleaning SaaS Front Page
 * Description: Public marketing landing page for the multi-tenant cleaning booking SaaS.
 *
 * @package CleaningSaaSTheme
 */
\get_header();
?>

<!-- Font Awesome CDN for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" referrerpolicy="no-referrer" />

<style>
/* =============================
   Cleaning SaaS – Sleek Landing
   ============================= */
:root {
  --primary: #2d8cff;
  --primary-dark: #005ad1;
  --secondary: #12263a;
  --accent: #f7a600;
  --bg-light: #f5f7fa;
  --text-dark: #1e1e1e;
  --radius: 1.25rem;
}

body.front-page {
  font-family: 'Inter', sans-serif;
  background: var(--bg-light);
  color: var(--text-dark);
  margin: 0;
  -webkit-font-smoothing: antialiased;
}

/* ========== Global ========== */
section {
  padding: clamp(3rem, 5vw, 6rem) 1rem;
  max-width: 1280px;
  margin: 0 auto;
}
img {
  max-width: 100%;
  height: auto;
  border-radius: var(--radius);
}
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  text-decoration: none;
  padding: 0.75rem 1.75rem;
  border-radius: 0.75rem;
  transition: background 0.3s ease, transform 0.2s ease;
}
.btn-primary {
  background: var(--primary);
  color: #fff;
}
.btn-primary:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
}
.btn-outline {
  background: transparent;
  border: 2px solid var(--primary);
  color: var(--primary);
}
.btn-outline:hover {
  background: var(--primary);
  color: #fff;
  transform: translateY(-2px);
}

/* ========== Header / Nav ========== */
.site-header {
  background: #fff;
  position: sticky;
  top: 0;
  z-index: 999;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
}
.navbar {
  max-width: 1280px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
}
.logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--secondary);
  text-decoration: none;
}
.logo i {
  color: var(--primary);
}
.nav-actions {
  display: flex;
  gap: 1rem;
}

/* ========== Hero ========== */
.hero {
  display: grid;
  gap: 3rem;
  align-items: center;
  grid-template-columns: 1fr;
}
@media (min-width: 900px) {
  .hero { grid-template-columns: 1fr 1fr; }
}
.hero h1 {
  font-size: clamp(2.5rem, 4vw, 3.5rem);
  line-height: 1.15;
  margin: 0 0 1rem;
  color: var(--secondary);
}
.hero p {
  font-size: 1.25rem;
  line-height: 1.6;
  margin: 0 0 2rem;
}
.hero__illustration {
  position: relative;
  animation: float 8s ease-in-out infinite;
  border: 1px solid #e2e8f0;
  border-radius: var(--radius);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
}
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-12px); }
}

/* ========== Features ========== */
.features-grid {
  display: grid;
  gap: 2rem;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  margin-top: 2rem;
}
.feature-card {
  background: #fff;
  padding: 2rem;
  border-radius: var(--radius);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
}
.feature-card i {
  font-size: 1.5rem;
  width: 3rem;
  height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  background: var(--primary);
  border-radius: 50%;
  margin-bottom: 1rem;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}
.feature-card h3 {
  margin: 0 0 0.5rem;
  font-size: 1.25rem;
}

/* ========== CTA ========== */
.cta {
  text-align: center;
}
.cta h2 {
  font-size: 2rem;
  margin-bottom: 1rem;
}

/* ========== Footer ========== */
footer.site-footer {
  padding: 2rem 1rem;
  text-align: center;
  font-size: 0.875rem;
  color: #666;
}

/* ========== Reveal Animation ========== */
.reveal { opacity: 0; transform: translateY(40px); transition: opacity 0.6s ease, transform 0.6s ease; }
.reveal.visible { opacity: 1; transform: none; }
</style>

<script>
// Intersection Observer for reveal animations
addEventListener('DOMContentLoaded', () => {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => entry.isIntersecting && entry.target.classList.add('visible'));
  }, { threshold: 0.15 });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
});
</script>

<main class="front-page">
  <!-- ======== Header / Navigation ======== -->
  <header class="site-header">
    <nav class="navbar">
      <a href="<?php echo esc_url( home_url('/') ); ?>" class="logo"><i class="fa-solid fa-broom"></i> Cleaning SaaS</a>
      <div class="nav-actions">
        <a href="/login" class="btn btn-outline"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login</a>
        <a href="/login" class="btn btn-primary"><i class="fa-solid fa-rocket"></i> Free Trial</a>
      </div>
    </nav>
  </header>

  <!-- ======== Hero Section ======== -->
  <section class="hero reveal">
    <div class="hero__content">
      <h1>Run Your Cleaning Business on Autopilot</h1>
      <p>Online bookings, automated payments & powerful analytics — all in one gorgeous dashboard.</p>
      <div style="display:flex; gap:1rem; flex-wrap:wrap;">
        <a href="/login" class="btn btn-primary"><i class="fa-solid fa-play"></i> Start Free Trial</a>
        <a href="#features" class="btn btn-outline"><i class="fa-solid fa-circle-info"></i> Learn More</a>
      </div>
    </div>
    <div class="hero__illustration">
      <?php echo '<img src="' . esc_url( get_template_directory_uri() . '/assets/img/hero.png' ) . '" alt="Dashboard Illustration">'; ?>
    </div>
  </section>

  <!-- ======== Features Section ======== -->
  <section id="features" class="reveal">
    <h2 style="text-align:center; font-size:2rem; margin:0 0 2rem;">Features Built for Modern Cleaning Teams</h2>
    <div class="features-grid">
      <div class="feature-card"><i class="fa-solid fa-gauge-high"></i><h3>Frontend Dashboard</h3><p>Goodbye WP Admin! Manage everything from a sleek, branded panel.</p></div>
      <div class="feature-card"><i class="fa-solid fa-layer-group"></i><h3>Multi‑Tenant Core</h3><p>Each account uses isolated database tables for security & performance.</p></div>
      <div class="feature-card"><i class="fa-solid fa-credit-card"></i><h3>WooCommerce + Stripe</h3><p>Offer trials, monthly or yearly plans with zero extra plugins.</p></div>
      <div class="feature-card"><i class="fa-solid fa-calendar-check"></i><h3>Smart Booking Form</h3><p>ZIP validation, discount codes & live pricing — all friction‑free.</p></div>
      <div class="feature-card"><i class="fa-solid fa-chart-line"></i><h3>Insightful Analytics</h3><p>Track revenue, top services & monthly trends at a glance.</p></div>
      <div class="feature-card"><i class="fa-solid fa-gears"></i><h3>Modular PHP Classes</h3><p>Clean PSR‑4 architecture makes future expansion effortless.</p></div>
    </div>
  </section>

  <!-- ======== CTA Section ======== -->
  <section class="cta reveal">
    <h2>Ready to automate your cleaning business?</h2>
    <a href="<?php echo esc_url( wc_get_page_permalink('login') ); ?>" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Create Account</a>
  </section>
</main>

<footer class="site-footer">
  © <?php echo date('Y'); ?> Cleaning SaaS — Built on WordPress + WooCommerce
</footer>

<?php get_footer(); ?>
