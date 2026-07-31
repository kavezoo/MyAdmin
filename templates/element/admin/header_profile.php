			<li class="list-inline-item dropdown notif">
				<a class="nav-link dropdown-toggle nav-user" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
					<img src="<?= $this->Url->image('avatars/admin.png') ?>" alt="<?= h(__('Profile picture')) ?>" class="avatar-rounded">
				</a>
				<div class="dropdown-menu dropdown-menu-right profile-dropdown border border-1 border-secondary">
					<div class="dropdown-item noti-title">
						<h5 class="text-overflow"><small><?= __('Hello, admin') ?></small></h5>
					</div>
					<a href="#" class="dropdown-item notify-item">
						<i class="fa fa-user"></i> <span><?= __('Profile') ?></span>
					</a>
					<a href="#" class="dropdown-item notify-item">
						<i class="fa fa-power-off"></i> <span><?= __('Log out') ?></span>
					</a>
				</div>
			</li>
