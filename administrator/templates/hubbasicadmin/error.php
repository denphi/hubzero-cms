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

$browser = new \Hubzero\Browser\Detector();
$b = $browser->name();
$v = $browser->major();
?>
<!DOCTYPE html>
<!--[if lt IE 7 ]> <html dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>" class="ie6"> <![endif]-->
<!--[if IE 7 ]>    <html dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>" class="ie7"> <![endif]-->
<!--[if IE 8 ]>    <html dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>" class="ie8"> <![endif]-->
<!--[if IE 9 ]>    <html dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>" class="ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html dir="<?php echo $this->direction; ?>" lang="<?php echo $this->language; ?>" class="j25 <?php echo $b . ' ' . $b . $v; ?>"> <!--<![endif]-->
	<head>
		<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/template.css" rel="stylesheet" type="text/css" />
		<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/common/icons.css" rel="stylesheet" type="text/css" />
		<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/error.css" rel="stylesheet" type="text/css" />
		<?php if ($this->direction == 'rtl') : ?>
			<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/common/rtl.css" rel="stylesheet" type="text/css" />
		<?php endif; ?>

		<?php if ($b == 'firefox' && intval($v) < 4 && $browser->getBrowserMinorVersion() < 5) { ?>
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
	</head>
	<body id="error-body">
		<header id="header" role="banner">
			<h1><a href="<?php echo Request::root(); ?>"><?php echo Config::get('sitename'); ?></a></h1>

			<ul class="user-options">
				<li>
					<?php
					//Display an harcoded logout
					$task = Request::getCmd('task');

					$logoutLink = Route::url('index.php?option=com_login&task=logout&' . JUtility::getToken() . '=1');
					if ($task == 'edit' || $task == 'editA' || Request::getInt('hidemainmenu')) :
						$logoutLink = '';
					endif;

					$output = array();
					$output[] = '<a class="logout" href="' . $logoutLink . '">' . Lang::txt('TPL_HUBBASICADMIN_LOGOUT') . '</a>';

					// Reverse rendering order for rtl display.
					if ($this->direction == 'rtl') :
						$output = array_reverse($output);
					endif;

					// Output the items.
					foreach ($output as $item) :
						echo $item;
					endforeach;
					?>
				</li>
			</ul>

			<div class="clear"></div>
		</header><!-- / header -->

		<div id="wrap">
			<nav role="navigation" class="main-navigation">
				<div class="inner-wrap">
					<ul id="menu">
						<li><a href="<?php echo Route::url('index.php'); ?>"><?php echo Lang::txt('Site') ?></a></li>
						<li><a href="<?php echo Route::url('index.php?option=com_admin&view=help'); ?>"><?php echo Lang::txt('Help'); ?></a></li>
					</ul>
				</div>
				<div class="clr"><!-- We need this for the drop downs --></div>
			</nav><!-- / .navigation -->

			<section id="component-content">
				<div id="toolbar-box" class="toolbar-box">
					<div class="header icon-48-alert">
						<?php echo Lang::txt('An error has occurred'); ?>
					</div>
				</div><!-- / #toolbar-box -->

				<div id="errorbox">
					<div class="col width-50 fltlft">
						<h3><?php echo $this->error->getCode() ?></h3>
					</div>
					<div class="col width-50 fltrt">
						<p class="error"><?php echo $this->error->getMessage(); ?></p>
					</div>
					<div class="clr"></div>
				</div>

				<?php if ($this->debug) :
					echo $this->renderBacktrace();
				endif; ?>

				<noscript>
					<?php echo Lang::txt('JGLOBAL_WARNJAVASCRIPT') ?>
				</noscript>
			</section>
		</div>

		<footer id="footer">
			<section class="basement">
				<p class="copyright">
					<?php echo Lang::txt('TPL_HUBBASICADMIN_COPYRIGHT', '<a href="' . Request::root() . '">'. Config::get('sitename') . '</a>', date("Y")); ?>
				</p>
				<p class="promotion">
					<?php echo Lang::txt('TPL_HUBBASICADMIN_POWERED_BY', App::version()); ?>
				</p>
			</section><!-- / .basement -->
		</footer><!-- / #footer -->
	</body>
</html>