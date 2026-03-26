</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<footer class="site-footer mt-5">
	<div class="container py-4 py-lg-5">
		<div class="row g-4 align-items-start">
			<div class="col-12 col-md-4">
				<div class="d-flex align-items-center gap-2 mb-3">
					<img class="navbar-brand-logo" src="<?= e(app_url('/assets/image/logo.png')) ?>" alt="Tacticus logo">
					<strong class="text-white">Tacticus</strong>
				</div>
				<p class="mb-0 text-muted">A football management platform for players, coaches, fans, and admins.</p>
			</div>
			<div class="col-6 col-md-2">
				<h2 class="h6 text-white mb-3">Explore</h2>
				<ul class="list-unstyled footer-links mb-0">
					<li><a href="<?= e(app_url('/players/index.php')) ?>">Players</a></li>
					<li><a href="<?= e(app_url('/teams/index.php')) ?>">Teams</a></li>
					<li><a href="<?= e(app_url('/matches/index.php')) ?>">Matches</a></li>
				</ul>
			</div>
			<div class="col-6 col-md-2">
				<h2 class="h6 text-white mb-3">Account</h2>
				<ul class="list-unstyled footer-links mb-0">
					<?php if (is_logged_in()): ?>
						<li><a href="<?= e(app_url('/player/profile.php')) ?>">Profile</a></li>
						<li><a href="<?= e(app_url('/auth/logout.php')) ?>">Logout</a></li>
					<?php else: ?>
						<li><a href="<?= e(app_url('/auth/login.php')) ?>">Login</a></li>
						<li><a href="<?= e(app_url('/auth/register.php')) ?>">Register</a></li>
					<?php endif; ?>
				</ul>
			</div>
			<div class="col-12 col-md-4">
				<h2 class="h6 text-white mb-3">Keep in touch</h2>
				<p class="mb-0 text-muted">Track squad performance, keep your lineup fresh, and stay on top of results with Tacticus.</p>
			</div>
		</div>
	</div>
</footer>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= e(app_url('/assets/js/app.js')) ?>"></script>
</body>
</html>
