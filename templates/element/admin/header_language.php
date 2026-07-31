			<li class="list-inline-item dropdown notif">
				<a class="nav-link dropdown-toggle nav-lang" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false" title="<?= h(__('Language')) ?>">
					<img src="<?= $this->Url->image('flags/en.png') ?>" alt="<?= h(__('English')) ?>" class="lang-flag-icon">
				</a>
				<div class="dropdown-menu dropdown-menu-right language-dropdown border border-1 border-secondary">
					<div class="dropdown-item noti-title">
						<h5 class="text-overflow"><small><?= __('Language') ?></small></h5>
					</div>
					<a href="#" class="dropdown-item notify-item language-item" data-lang="hu">
						<img src="<?= $this->Url->image('flags/hu.png') ?>" alt="<?= h(__('Hungarian')) ?>" class="lang-flag-option"> <span><?= __('Hungarian') ?></span>
					</a>
					<a href="#" class="dropdown-item notify-item language-item active" data-lang="en">
						<img src="<?= $this->Url->image('flags/en.png') ?>" alt="<?= h(__('English')) ?>" class="lang-flag-option"> <span><?= __('English') ?></span>
					</a>
					<a href="#" class="dropdown-item notify-item language-item" data-lang="de">
						<img src="<?= $this->Url->image('flags/de.png') ?>" alt="<?= h(__('German')) ?>" class="lang-flag-option"> <span><?= __('German') ?></span>
					</a>
					<a href="#" class="dropdown-item notify-item language-item" data-lang="sk">
						<img src="<?= $this->Url->image('flags/sk.png') ?>" alt="<?= h(__('Slovak')) ?>" class="lang-flag-option"> <span><?= __('Slovak') ?></span>
					</a>
				</div>
			</li>
