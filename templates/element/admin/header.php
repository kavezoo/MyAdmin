<!-- top bar navigation -->
<div class="headerbar">

	<div class="headerbar-left">
		<a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']) ?>" class="logo">
			<img alt="Logo" src="<?= $this->Url->image('logo.png') ?>" />
			<span><?= __('Admin') ?></span>
		</a>
	</div>

	<nav class="navbar-custom">

		<ul class="list-inline float-right mb-0">
			<?= $this->element('admin/header_search') ?>
			<?= $this->element('admin/header_help') ?>
			<?= $this->element('admin/header_messages') ?>
			<?= $this->element('admin/header_alerts') ?>
			<?= $this->element('admin/header_profile') ?>
		</ul>

		<ul class="list-inline menu-left mb-0">
			<li class="float-left">
				<button class="button-menu-mobile open-left">
					<i class="fa fa-fw fa-bars"></i>
				</button>
			</li>
		</ul>

	</nav>

</div>
<!-- End Navigation -->
