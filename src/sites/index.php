<?php
require_once __DIR__ . '/../app/Services/Configuration.php';
$url = (new TWPG\Services\Configuration())->general->domain;
?>
<head>
	<title>Redirecting...</title>
	<meta http-equiv="refresh" content="3;url=http://<?php echo $url; ?>" />
</head>
<body>
	<p>Redirecting to the main panel. Please wait, or <a href="http://<?php echo $url; ?>">click here to continue</a>.</p>
</body>