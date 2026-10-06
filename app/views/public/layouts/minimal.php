<?php
$name = 'journzey.ai';
try { $name = setting('site_name', 'journzey.ai'); } catch (\Throwable) {}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e(($title ?? 'Error') . ' | ' . $name) ?></title>
<link rel="icon" href="<?= e(asset('images/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<?php try { echo App\Core\View::partial('public/partials/theme'); } catch (\Throwable) {} ?>
</head>
<body class="is-error">
<main id="main"><?= $content ?></main>
</body>
</html>
