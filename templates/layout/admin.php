<?php
/**
 * Admin layout (from MyPluginTemplate).
 *
 * Layout CSS/JS only — page-specific assets via $this->fetch('css'|'script'|'scriptBottom').
 *
 * @var \App\View\AppView $this
 */
$pageTitle = $this->fetch('title');
if ($pageTitle === '' || $pageTitle === null) {
	$pageTitle = __('Admin');
}
?>
<!DOCTYPE html>
<html lang="<?= h(substr(\Cake\I18n\I18n::getLocale(), 0, 2)) ?>">
<head>
	<?= $this->Html->charset() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= h($pageTitle) ?></title>
	<meta name="description" content="<?= h(__('Admin')) ?>">
	<meta name="author" content="KaveZoo">

	<?= $this->Html->meta('icon', 'favicon.ico') ?>
	<?= $this->Html->meta('csrfToken', $this->request->getAttribute('csrfToken')) ?>

	<?= $this->Html->css([
		'bootstrap.min',
		'/fontawesome/css/all.min',
		'/fontawesome/css/v4-shims.min',
		'/fontawesome/css/v4-font-face.min',
		'style',
		'/plugins/simple-notify/simple-notify',
		'/plugins/sweetalert2/sweetalert2.min',
	]) ?>

	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
</head>
<body class="adminbody">

<div id="main">

	<?= $this->element('admin/header') ?>
	<?= $this->element('admin/sidebar') ?>

	<div class="content-page">
		<div class="content">
			<div class="container-fluid">
				<?= $this->element('admin/breadcrumb') ?>
				<?= $this->fetch('content') ?>
			</div>
		</div>
	</div>

	<?= $this->element('admin/footer') ?>

	<button type="button"
		id="btn-scroll-top"
		class="btn-scroll-top"
		aria-label="<?= h(__('Back to top')) ?>"
		title="<?= h(__('Back to top')) ?>">
		<i class="fa fa-angle-up" aria-hidden="true"></i>
	</button>

</div>

<script>
window.MyAdmin = window.MyAdmin || {};
window.MyAdmin.messages = <?= json_encode([
	'deleteTitle' => __('Delete'),
	'deleteConfirm' => __('Do you really want to delete the selected record?'),
	'deleteButton' => __('Delete'),
	'cancelButton' => __('Cancel'),
	'deleteNotWired' => __('Delete functionality will be connected later.'),
	'deleteFormMissing' => __('Delete form not found for ID:'),
	'deleteFormNotFound' => __('Delete form not found. ID: {0}'),
	'cannotDeleteHasChildren' => __('Cannot delete this record because it has related child records.'),
	'failedToLoad' => __('Failed to load the record.'),
	'noServerResponse' => __('No response from the server.'),
	'yes' => __('Yes'),
	'no' => __('No'),
	'recordDetails' => __('Record details'),
	'parentDetails' => __('Parent details'),
	'failedToSave' => __('Failed to save the new value.'),
	'saveNewValueFailed' => __('Failed to save the new value.'),
	'noServerResponseSaveFailed' => __('No response from the server. Failed to save the new value.'),
	'errorTitle' => __('Error'),
	'successTitle' => __('Success'),
	'infoTitle' => __('Info'),
	'okButton' => __('OK'),
	'add' => __('Add'),
	'addNewValue' => __('Add a new value to the list.'),
	'addTooltip' => '<b>' . __('Add') . '</b><br>' . __('Add a new value to the list.'),
	'apply' => __('Apply'),
	'cancel' => __('Cancel'),
	'from' => __('From'),
	'to' => __('To'),
	'custom' => __('Custom'),
	'selectCities' => __('Select cities...'),
	'selectSamples' => __('Select samples...'),
	'deleteParentHint' => __('Delete the parent from the Parents list.'),
	'table' => __('Table'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<?= $this->Html->script([
	'modernizr.min',
	'jquery.min',
	'moment.min',
	'bootstrap.bundle.min',
	'bootstrap5-jquery-bridge',
	'detect',
	'fastclick',
	'jquery.blockUI',
	'jquery.nicescroll',
	'pikeadmin',
	'/plugins/simple-notify/simple-notify.min',
	'/plugins/sweetalert2/sweetalert2.min',
	'app',
]) ?>

<?= $this->fetch('script') ?>
<?= $this->fetch('scriptBottom') ?>

<?php if (!empty($this->getRequest()->getSession()->read('Flash'))): ?>
<script>
<?= $this->element('admin/script_flash') ?>
<?= $this->Flash->render() ?>
</script>
<?php endif; ?>

</body>
</html>
