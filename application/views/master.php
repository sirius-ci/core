<!DOCTYPE html>
<html lang="<?php echo $this->language ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo $this->site->get('metaTitle') ?></title>
    <meta name="description" content="<?php echo $this->site->get('metaDescription') ?>">
    <meta name="keywords" content="<?php echo $this->site->get('metaKeywords') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <base href="<?php echo base_url('/') ?>" />

    <link rel="stylesheet" type="text/css" href="public/plugin/bootstrap/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="public/plugin/font-awesome/css/font-awesome.min.css" />
    <link rel="stylesheet" type="text/css" href="public/plugin/fancybox/jquery.fancybox.css" />
    <link rel="stylesheet" type="text/css" href="public/css/main.css" />

    <?php foreach ($this->site->assets('css') as $asset): ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $asset ?>" />
    <?php endforeach; ?>

    <script type="text/javascript" src="public/js/jquery.js"></script>
    <script type="text/javascript" src="public/js/jquery.maskedinput.min.js"></script>
    <script type="text/javascript" src="public/js/jquery.numeric.min.js"></script>
    <script type="text/javascript" src="public/js/bootstrap.filestyle.min.js"></script>
    <script type="text/javascript" src="public/plugin/bootstrap/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="public/plugin/fancybox/jquery.fancybox.js"></script>

    <?php foreach ($this->site->assets('js') as $asset): ?>
        <script type="text/javascript" src="<?php echo $asset ?>"></script>
    <?php endforeach; ?>

    <script type="text/javascript" src="public/js/main.js"></script>


    <!-- HTML5 shim, for IE6-8 support of HTML5 elements html5shiv.js -->
    <!--[if lt IE 9]><script src="public/js/html5shiv.js'"></script><![endif]-->


    <?php if ($ogType = $this->site->get('ogType')): ?>
        <meta property="og:type" content="<?php echo $ogType ?>" />
    <?php endif; ?>
    <?php if ($ogTitle = $this->site->get('ogTitle')): ?>
        <meta property="og:title" content="<?php echo htmlspecialchars($ogTitle) ?>" />
    <?php endif; ?>
    <?php if ($ogDescription = $this->site->get('ogDescription')): ?>
        <meta property="og:description" content="<?php echo htmlspecialchars($ogDescription) ?>" />
    <?php endif; ?>
    <?php if ($ogImage = $this->site->get('ogImage')): ?>
        <meta property="og:image" content="<?php echo base_url('/').$ogImage ?>"/>
    <?php endif; ?>

    <meta property="og:url" content="<?php echo current_url() ?>"/>

    <?php echo $this->site->get('customMeta') ?>
</head>
<body>

    <?php $this->view($view); ?>

</body>
</html>
