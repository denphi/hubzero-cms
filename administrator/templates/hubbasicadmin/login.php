<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// No direct access
defined('_HZEXEC_') or die();

// Load CSS
$this->addStyleSheet($this->baseurl . '/templates/' . $this->template . '/css/login.css');
if ($this->params->get('theme') && $this->params->get('theme') != 'gray')
{
	$this->addStyleSheet($this->baseurl . '/templates/' . $this->template . '/css/themes/' . $this->params->get('theme') . '.css');
}

// Load language direction CSS
if ($this->direction == 'rtl')
{
	$this->addStyleSheet($this->baseurl . '/templates/' . $this->template . '/css/common/rtl.css');
}

$browser = new \Hubzero\Browser\Detector();
$b = $browser->name();
$v = $browser->major();
?>
<!DOCTYPE html>
<!--[if lt IE 7 ]> <html dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>" class="ie ie6"> <![endif]-->
<!--[if IE 7 ]>    <html dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>" class="ie ie7"> <![endif]-->
<!--[if IE 8 ]>    <html dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>" class="ie ie8"> <![endif]-->
<!--[if IE 9 ]>    <html dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>" class="ie ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>" class="j25 <?php echo $b . ' ' . $b . $v; ?>"> <!--<![endif]-->
	<head>
		<jdoc:include type="head" />
		<?php if ($b == 'firefox' && intval($v) < 4) { ?>
			<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/browser/firefox.css" rel="stylesheet" type="text/css" />
		<?php } ?>
		<!--[if IE 7]>
			<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/browser/ie7.css" rel="stylesheet" type="text/css" />
			<script src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/html5.js" type="text/javascript"></script>
		<![endif]-->
		<!--[if IE 8]>
			<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/browser/ie8.css" rel="stylesheet" type="text/css" />
			<script src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/html5.js" type="text/javascript"></script>
		<![endif]-->
		<script type="text/javascript">
			jQuery(document).ready(function($){
				(function worker() {
					$.ajax({
						url: 'index.php',
						complete: function() {
							setTimeout(worker, 3540000);
						}
					});
				})();
				document.getElementById('form-login').username.select();
				document.getElementById('form-login').username.focus();
			});
		</script>
	</head>
	<body id="login-body">
		<jdoc:include type="modules" name="notices" />
		<header id="header" role="banner">
			<h1><a href="<?php echo Request::root(); ?>"><?php echo Config::get('sitename'); ?></a></h1>
			<div class="clr"></div>
		</header><!-- / header -->

		<div id="wrap">
			<section id="component-content">
				<div id="toolbar-box">
					<h2><?php echo Lang::txt('Administration Login') ?></h2>
				</div>

				<section id="main" class="<?php echo Request::getCmd('option', ''); ?>">
					<!-- Notifications begins -->
					<jdoc:include type="message" />
					<!-- Notifications ends -->
					<!-- Content begins -->
					<jdoc:include type="component" />
					<!-- Content ends -->
					<noscript>
						<?php echo Lang::txt('JGLOBAL_WARNJAVASCRIPT') ?>
					</noscript>
					<div class="clr"></div>
				</section><!-- / #main -->
			</section><!-- / #content -->
		</div><!-- / #wrap -->
	</body>
</html>