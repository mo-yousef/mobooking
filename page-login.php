<?php
/**
 * Template Name: Vandel Cleaning Login
 * Description: Front‑end login page with demo credentials pre‑filled.
 *
 * @package VandelCleaningTheme
 */
\get_header();
?>

<!-- Ensure Font Awesome is available for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" referrerpolicy="no-referrer" />

<style>
/* =============================
   Vandel Cleaning – Login Page
   ============================= */
:root {
  --primary: #2d8cff;
  --primary-dark: #005ad1;
  --secondary: #0f2135;
  --bg-light: #f6f9fc;
  --radius: 1.25rem;
  --shadow: 0 10px 25px rgba(0,0,0,.06);
}
body.login-page { background: var(--bg-light); font-family: 'Inter', sans-serif; margin: 0; }

section.login-wrapper { display: flex; justify-content: center; align-items: center; min-height: 80vh; padding: 2rem; }
.login-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); padding: 3rem 2.5rem; width: 100%; max-width: 420px; }
.login-card h1 { margin: 0 0 1.5rem; font-size: 1.75rem; color: var(--secondary); text-align: center; }
.form-group { margin-bottom: 1.25rem; }
.form-group label { display: block; margin-bottom: .5rem; font-weight: 600; color: var(--secondary); }
.form-control { width: 100%; padding: .75rem 1rem; border: 1px solid #d1d5db; border-radius: .5rem; font-size: 1rem; }
.btn-primary { width: 100%; padding: .75rem 1rem; background: var(--primary); color: #fff; border: none; border-radius: .5rem; font-weight: 600; cursor: pointer; transition: background .3s ease; }
.btn-primary:hover { background: var(--primary-dark); }
.demo-info { margin-top: 2rem; background: var(--bg-light); border: 1px dashed var(--primary); border-radius: .75rem; padding: 1.5rem; font-size: .95rem; }
.demo-info h2 { margin-top: 0; font-size: 1.1rem; color: var(--secondary); }
.demo-info code { background: #e2e8f0; padding: .25rem .5rem; border-radius: .25rem; }
.error-msg { background: #fee2e2; color: #b91c1c; padding: .75rem 1rem; border-radius: .5rem; margin-bottom: 1rem; font-size: .95rem; }
</style>

<?php
// Handle error messages via URL parameter ?login=failed
$login_failed = isset( $_GET['login'] ) && 'failed' === $_GET['login'];
$redirect_to  = home_url( '/dashboard' ); // change if needed
?>

<main class="login-page">
  <section class="login-wrapper">
    <div class="login-card">
      <h1><i class="fa-solid fa-right-to-bracket"></i> Account Login</h1>

      <?php if ( $login_failed ) : ?>
        <div class="error-msg">Incorrect username or password. Please try again.</div>
      <?php endif; ?>

      <form method="post" action="<?php echo esc_url( wp_login_url( $redirect_to ) ); ?>">
        <div class="form-group">
          <label for="user_login">Username</label>
          <input id="user_login" class="form-control" type="text" name="log" value="demo" required />
        </div>
        <div class="form-group">
          <label for="user_pass">Password</label>
          <input id="user_pass" class="form-control" type="password" name="pwd" value="demopass" required />
        </div>
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
        <?php wp_nonce_field( 'vandel_login_nonce', 'vandel_login_nonce_field' ); ?>
        <button type="submit" class="btn-primary">Log In</button>
      </form>

      <div class="demo-info">
        <h2>Demo Account</h2>
        <p>Feel free to explore the dashboard using our demo credentials:</p>
        <p><strong>Username:</strong> <code>demo</code><br><strong>Password:</strong> <code>demopass</code></p>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>

