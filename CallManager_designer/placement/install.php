<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require_once ('crest.php');
$result = CRest::installApp();



$resulti = CRest::call(
	'placement.bind',
	[
		 'PLACEMENT' => 'CRM_DEAL_DETAIL_TOOLBAR',
		 'HANDLER' => 'https://bx24.kitchenbecker.ru/local/php_interface/classes/CallManager_designer/placement/placement.php',
		 'ru' => [
            'TITLE' => 'жмай на кнопка',
            'DESCRIPTION' => 'описание',
            'GROUP_NAME' => 'группа',
         ],
	]
);

if($result['rest_only'] === false):?>
	<head>
		<script src="//api.bitrix24.com/api/v1/"></script>
		<?php if($result['install'] == true):?>
			<script>
				BX24.init(function(){
					BX24.installFinish();
				});
			</script>
		<?php endif;?>
	</head>
	<body>
		<?php if($result['install'] == true):?>
		<?php	echo	'installation has been finished'; ?>
		<?php else:?>
		<?php	echo 'installation error'; ?>
		<?php endif;?>
	</body>
<?php endif;






