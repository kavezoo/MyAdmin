<?php
/**
 * Auth layout (login / register / password reset / profile) — ValiAdmin sample structure.
 *
 * Minta: CakeDC-Login-layout-with-KeyCloak login.php
 * Helyi login: `.login-box.local-login` + `.local-login-form` (nincs Keycloak flip).
 * Flash: Simple Notify toast (Admin mintára).
 *
 * @var \App\View\AppView $this
 */
use App\Utility\AppBrand;

$pageTitle = AppBrand::title();
$pageName = AppBrand::name();
?>
<!DOCTYPE html>
<html lang="<?= h(str_replace('_', '-', \Cake\I18n\I18n::getLocale())) ?>">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= h($pageTitle) ?> :: <?= h($this->fetch('title') ?: __('Login')) ?></title>
	<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
	<link rel="stylesheet" type="text/css" href="https://saghysat.hu/plugins/valiadmin/css/valiadmin.css">
	<link rel="stylesheet" type="text/css" href="https://saghysat.hu/plugins/valiadmin/css/valilogin.css">
	<?= $this->Html->css([
		'/plugins/simple-notify/simple-notify',
		'pages/users_auth',
	]) ?>
	<?= $this->fetch('css') ?>
</head>
<body class="users-auth-body">
	<section class="material-half-bg">
		<div class="cover"></div>
	</section>

	<section class="login-content">
		<div class="logo mb-0">
			<h1><?= h($pageName) ?></h1>
		</div>

		<div class="login-box local-login">
			<?= $this->fetch('content') ?>
		</div>
	</section>

	<script src="https://saghysat.hu/plugins/valiadmin/js/jquery-3.7.0.min.js"></script>
	<script src="https://saghysat.hu/plugins/valiadmin/js/bootstrap.min.js"></script>
	<?= $this->Html->script('/plugins/simple-notify/simple-notify.min') ?>
	<?= $this->fetch('script') ?>

<?php if (!empty($this->getRequest()->getSession()->read('Flash'))): ?>
<script>
<?= $this->element('admin/script_flash') ?>
<?= $this->Flash->render('auth') ?>
<?= $this->Flash->render() ?>
</script>
<?php endif; ?>

</body>
</html>
