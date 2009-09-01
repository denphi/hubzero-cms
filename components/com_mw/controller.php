<?php
/**
 * @package		HUBzero CMS
 * @author		Shawn Rice <zooley@purdue.edu>
 * @copyright	Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );

class MwController extends JObject
{	
	private $_name  = NULL;
	private $_data  = array();
	private $_task  = NULL;
	
	//-----------
	
	public function __construct( $config=array() )
	{
		$this->_redirect = NULL;
		$this->_message = NULL;
		$this->_messageType = 'message';
		
		//Set the controller name
		if (empty( $this->_name ))
		{
			if (isset($config['name']))  {
				$this->_name = $config['name'];
			}
			else
			{
				$r = null;
				if (!preg_match('/(.*)Controller/i', get_class($this), $r)) {
					echo "Controller::__construct() : Can't get or parse class name.";
				}
				$this->_name = strtolower( $r[1] );
			}
		}
		
		$this->_option = 'com_'.$this->_name;
	}
	
	//-----------
	
	public function __set($property, $value)
	{
		$this->_data[$property] = $value;
	}
	
	//-----------
	
	public function __get($property)
	{
		if (isset($this->_data[$property])) {
			return $this->_data[$property];
		}
	}
		
	//-----------
	
	private function getTask()
	{
		$task = JRequest::getVar( 'task', '' );
		
		$juser =& JFactory::getUser();
		if ($juser->get('guest')) {
			$task = 'login';
		}
		
		$this->_task = $task;
		return $task;
	}
	
	//-----------

	public function execute()
	{
		// Get the component config
		//$config = new MwConfig( $this->_option );
		$config =& JComponentHelper::getParams( $this->_option );
		$this->config = $config;
		
		$enabled = $this->config->get('mw_on');
		
		if (!$enabled) {
			// Redirect to home page
			$this->_redirect = '/home/';
			return;
		}
		
		// Are we banking?
		$upconfig =& JComponentHelper::getParams( 'com_userpoints' );
		$banking = $upconfig->get('bankAccounts');
		$this->banking = ($banking && $this->config->get('banking') ) ? 1: 1;
		
		if ($banking) {
			ximport( 'bankaccount' );
		}
		
		// Push some styles to the template
		$this->getStyles();
		
		// Push some scripts to the template
		$this->getScripts($this->_name,'mw');

		switch ( $this->getTask() ) 
		{
			case 'login':     $this->login();     break;
			
			// Error views
			case 'accessdenied':    $this->accessdenied();    	break;
			case 'quotaexceeded':   $this->quotaexceeded();   	break;
			case 'storageexceeded': $this->storage(true); 		break;
			case 'storage': 		$this->storage(); 			break;
			
			// Tasks typically called via AJAX
			case 'rename':    		$this->renames();   		break;
			case 'diskusage': 		$this->diskusage(); 		break;
			case 'purge':     		$this->purge();     		break;
			
			// Session tasks
			case 'share':     		$this->share();     		break;
			case 'unshare':   		$this->unshare();   		break;
			case 'invoke':    		$this->invoke();    		break;
			case 'view':      		$this->view();      		break;
			case 'stop':      		$this->stop();      		break;
			
			// Media manager
			case 'listfiles':    	$this->listfiles();     	break;
			case 'download':      	$this->download();      	break;
			//case 'upload':       	$this->upload();        	break;
			case 'deletefolder': 	$this->deletefolder();  	break;
			case 'deletefile':   	$this->deletefile();    	break;

			default: $this->view(); break;
		}
	}

	//-----------

	public function redirect()
	{
		if ($this->_redirect != NULL) {
			$app =& JFactory::getApplication();
			$app->redirect( $this->_redirect, $this->_message );
		}
	}

	//-----------
	
	private function getStyles() 
	{
		ximport('xdocument');
		XDocument::addComponentStylesheet($this->_option);
	}

	//-----------
	
	private function getScripts($option='',$name='')
	{
		$document =& JFactory::getDocument();
		if ($option) {
			$name = ($name) ? $name : $option;
			if (is_file(JPATH_ROOT.DS.'components'.DS.'com_'.$option.DS.$name.'.js')) {
				$document->addScript('components'.DS.'com_'.$option.DS.$name.'.js');
			}
		} else {
			if (is_file(JPATH_ROOT.DS.'components'.DS.$this->_option.DS.$this->_name.'.js')) {
				$document->addScript('components'.DS.$this->_option.DS.$this->_name.'.js');
			}
		}
	}

	//----------------------------------------------------------
	// Views
	//----------------------------------------------------------

	protected function login() 
	{
		// Set the page title
		$title = JText::_(strtoupper($this->_name)).': '.JText::_(strtoupper($this->_task));
		
		$document =& JFactory::getDocument();
		$document->setTitle( $title );
		
		$japp =& JFactory::getApplication();
		$pathway =& $japp->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem( JText::_(strtoupper($this->_name)), 'index.php?option='.$this->_option );
		}
		$pathway->addItem( JText::_(strtoupper($this->_task)), 'index.php?option='.$this->_option.a.'task='.$this->_task );
		
		echo MwHtml::div( MwHtml::hed( 2, $title ), 'full', 'content-header' );
		echo '<div class="main section">'.n;
		ximport('xmodule');
		XModuleHelper::displayModules('force_mod');
		echo '</div><!-- / .main section -->'.n;
	}
	
	//-----------

	protected function accessdenied() 
	{
		// Build the page title
		$title  = JText::_(strtoupper($this->_name));
		$title .= ': '.JText::_('MW_ACCESS_DENIED');
		
		// Set the page title
		$document =& JFactory::getDocument();
		$document->setTitle( $title );

		$japp =& JFactory::getApplication();
		$pathway =& $japp->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem( JText::_(strtoupper($this->_name)), 'index.php?option='.$this->_option );
		}
		$pathway->addItem( JText::_('MW_ACCESS_DENIED'), 'index.php?option='.$this->_option.a.'task='.$this->_task );

		// Output HTML
		echo MwHtml::accessdenied( $this->_option, $this->getError() );
	}
	
	//-----------
	
	protected function quotaexceeded() 
	{
		$juser =& JFactory::getUser();
		
		// Build the page title
		$title  = JText::_(strtoupper($this->_name));
		$title .= ': '.JText::_('MW_QUOTA_EXCEEDED');
		
		// Set the page title
		$document =& JFactory::getDocument();
		$document->setTitle( $title );
		
		$japp =& JFactory::getApplication();
		$pathway =& $japp->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem( JText::_(strtoupper($this->_name)), 'index.php?option='.$this->_option );
		}
		$pathway->addItem( JText::_('MW_QUOTA_EXCEEDED'), 'index.php?option='.$this->_option.a.'task='.$this->_task );
		
		// Check if the user is an admin.
		$authorized = $this->_authorize();
		
		// Get the middleware database
		$mwdb =& MwUtils::getMWDBO();
		
		// Get the user's sessions
		$ms = new MwSession( $mwdb );
		$sessions = $ms->getRecords( $juser->get('username'), '', $authorized );
		
		// Output HTML
		echo MwHtml::quotaexceeded( $this->_option, $sessions, $authorized );
	}
	
	//-----------
	
	protected function storage( $exceeded=false )
	{
		// Build the page title
		$title  = JText::_(strtoupper($this->_name));
		$title .= ': '.JText::_('MW_STORAGE_MANAGEMENT');
		
		// Set the page title
		$document =& JFactory::getDocument();
		$document->setTitle( $title );
		
		//  Are we browsing files?
		$browse = JRequest::getInt( 'browse', 0 );
		
		// Set the pathway
		$japp =& JFactory::getApplication();
		$pathway =& $japp->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem( JText::_(strtoupper($this->_name)), 'index.php?option='.$this->_option );
		}
		$pathway->addItem( JText::_('MW_STORAGE_MANAGEMENT'), 'index.php?option='.$this->_option.a.'task=storage' );
		
		// output from purging
		$output = $this->__get('output');
			
		// Get their disk space usage
		$percentage = 0;
		if ($this->config->get('show_storage')) {
			$this->getDiskUsage();
			$percentage = $this->percent;
		}
		
			
		// Output HTML
		echo MwHtml::storage( $this->_option, $this->banking, $exceeded, $output, $this->_error, $percentage, $browse);
	}
	
	//-----------

	protected function invoke()
	{
		// Needed objects
		$juser =& JFactory::getUser();
		$xhub =& XFactory::getHub();
		$url = $_SERVER['REQUEST_URI'];
		$xlog =& XFactory::getLogger();

		// Incoming
		$app = array();
		$app['name']    = JRequest::getVar( 'sess', '' );
		$app['name']    = str_replace(':','-',$app['name']);
		$app['number']  = 0;
		$app['version'] = JRequest::getVar( 'version', 'dfault' );

		// Make sure we have an app to invoke
		if (trim($app['name']) == '') {
			$this->_redirect = JRoute::_( 'index.php?option=com_myhub' );
			return;
		}
		
		// Get the user's IP address
		$ip = JRequest::getVar( 'REMOTE_ADDR', '', 'server' );
		
		// Get the parent toolname (appname without any revision number "_r423")
		$database =& JFactory::getDBO();
		include_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_contribtool'.DS.'contribtool.version.php' );
		$tv = new ToolVersion( $database );
		$parent_toolname = $tv->getToolname($app['name']);
		$toolname = ($parent_toolname) ? $parent_toolname : $app['name'];
		
		// Check of the toolname has a revision indicator
		$bits = explode('_',$app['name']);
		$r = end($bits);
		if (substr($r,0,1) != 'r' && substr($r,0,3) != 'dev') {
			$r = '';
		}
		// No version passed and no revision
		if ((!$app['version'] || $app['version'] == 'default') && !$r) {
			// Get the latest version
			$app['version'] = $tv->getCurrentVersionProperty( $toolname, 'revision' );
			$app['name'] = $toolname.'_r'.$app['version'];
		}
		
		// Get the caption/session title
		$tv->loadFromInstance( $app['name'] );
		$app['caption'] = stripslashes($tv->title);

		// Check if they have access to run this tool
		$hasaccess = acc_gettoolaccess($app['name']);
		$hasaccess2 = $this->_getToolAccess($app['name']);
		
		$status1 = (!$hasaccess) ? "PASSED" : "FAILED";
		$status2 = ($hasaccess2) ? "PASSED" : "FAILED";

		$xlog->logDebug("mw::invoke URL:" . $url);
		$xlog->logDebug("mw::invoke REFERER:" . $_SERVER['HTTP_REFERER']);
		$xlog->logDebug("mw::invoke " . $app['name'] . " by " . $juser->get('username') . " from " . $ip . " acc_gettoolaccess " . $status1 . " _getToolAccess " . $status2);

		if ($this->getError()) {
			echo '<!-- '.$this->getError().' -->';
		}
		if ($hasaccess) {
			$this->_redirect = JRoute::_('index.php?option='.$this->_option.a.'task=accessdenied');
			return;
		}

		// Check authorization
		$authorized = $this->_authorize();

		// Log the launch attempt
		$this->recordUsage($toolname, $juser->get('id'));
		
		// Get the middleware database
		$mwdb =& MwUtils::getMWDBO();
		
		// Find out how many sessions the user is running.
		$ms = new MwSession( $mwdb );
		$appcount = $ms->getCount( $juser->get('username') );

		// Find out how many sessions the user is ALLOWED to run.
		$xuser =& XFactory::getUser();
		$remain = $xuser->get('jobs_allowed') - $appcount;

		// Have they reached their session quota?
		if ($remain <= 0) {
			$this->_redirect = JRoute::_('index.php?option='.$this->_option.a.'task=quotaexceeded');
			return;
		}
		
		// Get their disk space usage
		$app['percent'] = 0;
		if ($this->config->get('show_storage')) {
			$this->getDiskUsage();
			$app['percent'] = $this->percent;
		}
		
		// We've passed all checks so let's actually start the session
		$sess = $this->middleware("start user=" . $juser->get('username') . " ip=$ip app=".$app['name']." version=".$app['version'], $output);

		// Get a count of the number of sessions of this specific tool
		$appcount = $ms->getCount( $juser->get('username'), $app['name'] );
		// Do we have more than one session of this tool?
		if ($appcount > 1) {
			// We do, so let's append a number to the caption
			//$appcount++;
			$app['caption'] .= ' ('.date("g:i a").')';
		}

		// Save the changed caption
		$ms->load( $sess );
		$ms->sessname = $app['caption'];
		if (!$ms->store()) {
			echo $ms->getError();
		}
		
		$app['sess'] = $sess;
		$app['ip'] = $ip;
		$app['username'] = $juser->get('username');
		
		// Build and display the HTML
		$this->session( $app, $authorized, $output, $toolname );
	}

	//-----------

	protected function share()
	{
		$mwdb =& MwUtils::getMWDBO();
	    $juser =& JFactory::getUser();
		
		// Incoming
		$sess     = JRequest::getVar( 'sess', '' );
		$username = trim(JRequest::getVar( 'username', '' ));
		$readonly = JRequest::getVar( 'readonly', '' );
		
		$users = array();
		if (strstr($username,',')) {
			$users = explode(',',$username);
			$users = array_map('trim',$users);
		} elseif (strstr($username,' ')) {
			$users = explode(' ',$username);
			$users = array_map('trim',$users);
		} else {
			$users[] = $username;
		}
		
		// Check authorization
		$authorized = $this->_authorize();
		
		// Double-check that the user can access this session.
		$ms = new MwSession( $mwdb );
		$row = $ms->checkSession( $sess, $juser->get('username') );
		
		// Ensure we found an active session
		if (!$row->sesstoken) {
			echo MwHtml::error( JText::_('MW_ERROR_SESSION_NOT_FOUND').': '.$sess );
			return;
		}

		//$row = $rows[0];
		$owner = $row->viewuser;

		if ($readonly != 'Yes') {
			$readonly = 'No';
		}

		$mv = new MwViewperm( $mwdb );
		$rows = $mv->loadViewperm( $sess, $owner );
		if (count($rows) != 1) {
			echo MwHtml::error('Unable to get entry for '.$sess.', '.$owner);
			break;
		}

		foreach ($users as $user) 
		{
			// Check for invalid characters
			if (!eregi("^[0-9a-zA-Z]+[_0-9a-zA-Z]*$", $user)) {
				$this->setError( JText::_('MW_ERROR_INVALID_USERNAME').': '.$user );
				continue;
			}
			
			// Check that the user exist
			$zuser =& JUser::getInstance( $user );
			if (!$zuser || !is_object($zuser) || !$zuser->get('id')) {
				$this->setError( JText::_('MW_ERROR_INVALID_USERNAME').': '.$user );
				continue;
			}
			
			$mv = new MwViewperm( $mwdb );
			$checkrows = $mv->loadViewperm( $sess, $user );

			// If there are no matching entries in viewperm, add a new entry,
			// Otherwise, update the existing entry (e.g. readonly).
			if (count($checkrows) == 0) {
				$mv->sessnum   = $sess;
				$mv->viewuser  = $user;
				$mv->viewtoken = md5(rand());
				$mv->geometry  = $rows[0]->geometry;
				$mv->fwhost    = $rows[0]->fwhost;
				$mv->fwport    = $rows[0]->fwport;
				$mv->vncpass   = $rows[0]->vncpass;
				$mv->readonly  = $readonly;
				$mv->insert();
			} else {
				$mv->sessnum   = $checkrows[0]->sessnum;
				$mv->viewuser  = $checkrows[0]->viewuser;
				$mv->viewtoken = $checkrows[0]->viewtoken;
				$mv->geometry  = $checkrows[0]->geometry;
				$mv->fwhost    = $checkrows[0]->fwhost;
				$mv->fwport    = $checkrows[0]->fwport;
				$mv->vncpass   = $checkrows[0]->vncpass;
				$mv->readonly  = $readonly;
				$mv->update();
			}

			if ($mv->getError()) {
				echo MwHtml::error( $mv->getError() );
			}
		}

		// Drop through and re-view the session...
		$this->view();
	}
	
	//-----------
	
	protected function unshare()
	{
		// Needed objects
		$mwdb =& MwUtils::getMWDBO();
	    $juser =& JFactory::getUser();
		
		// Incoming
		$sess = JRequest::getVar( 'sess', '' );
		$user = JRequest::getVar( 'username', '' );
		
		// If a username is given, check that the user owns this session.
		if ($user != '') {
			$ms = new MwSession( $mwdb );
			$ms->load( $sess, $juser->get('username') );

			if (!$ms->sesstoken) {
				echo MwHtml::error( JText::_('MW_ERROR_SESSION_NOT_FOUND').': '.$sess );
				return;
			}
		} else {
			// Otherwise, assume that the user wants to disconnect a session that's been shared with them.
			$user = $juser->get('username');
		}

		// Delete the viewperm
		$mv = new MwViewperm( $mwdb );
		$mv->deleteViewperm( $sess, $user );
		
		if ($user == $juser->get('username')) {
			// Take us back to the main page...
			$this->_redirect = JRoute::_( 'index.php?option=com_myhub' );
			return;
		}
		
		// Drop through and re-view the session...
		$this->view();
	}
	
	//-----------
	
	protected function view()
	{
		// Incoming
		$app = array();
		$app['sess'] = JRequest::getVar( 'sess', '' );
		
		// Make sure we have an app to invoke
		if (trim($app['sess']) == '') {
			$this->_redirect = JRoute::_( 'index.php?option=com_myhub' );
			return;
		}
		
		// Get the user's IP address
		$ip = JRequest::getVar( 'REMOTE_ADDR', '', 'server' );
		
		// Check authorization
		$authorized = $this->_authorize();
		
		// Double-check that the user can view this session.
		$mwdb =& MwUtils::getMWDBO();
		
		$ms = new MwSession( $mwdb );
		$row = $ms->loadSession( $app['sess'], $authorized );

		if (strstr($row->appname,'_')) {
			$bits = explode('_',$row->appname);
			$v = str_replace('r','',end($bits));
			JRequest::setVar( 'version', $v );
		}
		
		// Get parent tool name - to write correct links
		$database =& JFactory::getDBO();
		include_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_contribtool'.DS.'contribtool.version.php' );
		$tv = new ToolVersion( $database );
		$parent_toolname = $tv->getToolname($row->appname);
		$toolname = ($parent_toolname) ? $parent_toolname : $row->appname;

		// Ensure we found an active session
		if (!$row->sesstoken) {
			echo MwHtml::error( JText::_('MW_ERROR_SESSION_NOT_FOUND').': '.$app['sess'].'. '.JText::_('MW_SESSION_NOT_FOUND_EXPLANATION') );
			return;
		}
		
		// Get their disk space usage
		$app['percent'] = 0;
		if ($this->config->get('show_storage')) {
			$this->getDiskUsage();
			$app['percent'] = $this->percent;
		}
		
		// Build the view command
		if ($authorized === 'admin') {
			$command = "view user=$row->username ip=$ip sess=".$app['sess'];
		} else {
			$juser =& JFactory::getUser();
			
			$command = "view user=" . $juser->get('username') . " ip=$ip sess=".$app['sess'];
		}

		// Check if we have access to run this tool.
		// If not, force view to be read-only.
		// This will happen in the event of sharing.
		$noaccess = acc_gettoolaccess($row->appname);
		$noaccess2 = $this->_getToolAccess($row->appname);
		if ($this->getError()) {
			echo '<!-- '.$this->getError().' -->';
		}
		if ($noaccess != '') {
		//if (!$noaccess) {
			$command .= " readonly=1";
		}
		
		$app['caption'] = $row->sessname;
		$app['name'] = $row->appname;
		$app['ip'] = $ip;
		$app['username'] = $row->username;
		
		// Call the view command
		$status = $this->middleware($command, $output);

		// Build and display the HTML
		$this->session( $app, $authorized, $output, $toolname );
	}
	
	//-----------

	private function session( $app, $authorized, $output, $toolname ) 
	{
		// Build the page title
		$title  = JText::_(strtoupper($this->_name));
		$title .= ($this->_task) ? ': '.JText::_(strtoupper($this->_task)) : '';
		$title .= ($app['caption']) ? ': '.$app['caption'] : $app['name'];
		
		// Set the page title
		$document =& JFactory::getDocument();
		$document->setTitle( $title );
		
		$japp =& JFactory::getApplication();
		$pathway =& $japp->getPathway();
		if (count($pathway->getPathWay()) <= 0) {
			$pathway->addItem( JText::_(strtoupper($this->_name)), 'index.php?option='.$this->_option );
		}
		if ($this->_task) {
			$pathway->addItem( JText::_(strtoupper('view')), 'index.php?option='.$this->_option.a.'task=view'.a.'sess='.$app['sess'] );
		}
		$t = ($app['caption']) ? $app['caption'] : $app['name'];
		$pathway->addItem( $t, 'index.php?option='.$this->_option.a.'task=view'.a.'sess='.$app['sess'] );
		
		// Get plugins
		JPluginHelper::importPlugin( 'mw' );
		$dispatcher =& JDispatcher::getInstance();
		
		// Get the active tab (section)
		$tab = JRequest::getVar( 'active', 'session' );
		
		// Trigger the functions that return the areas we'll be using
		$cats = $dispatcher->trigger( 'onMwAreas', array($authorized) );
		
		$workspaces = array('workspace','workspace-med','workspace-big');
		if (in_array($toolname,$workspaces)) {
			$toolname = 'workspace';
		}
		
		// Get the sections
		$sections = $dispatcher->trigger( 'onMw', array($toolname, $this->_option, $authorized, array($tab)) );

		// Add the default "Profile" section to the beginning of the lists
		$body = '';
		if ($tab == 'session') {
			$body = MwHtml::session( $app['sess'], $output, $this->_option, $app, $toolname, $authorized, $this->config );
		}
		
		$cat = array();
		$cat['session'] = JText::_('MW_SESSION');
		array_unshift($cats, $cat);
		array_unshift($sections, array('html'=>$body,'metadata'=>''));

		// Output the HTML
		echo MwHtml::view( $app, $authorized, $this->_option, $cats, $sections, $tab, $this->config );
	}

	//-----------

	protected function stop() 
	{
		// Incoming
		$sess = JRequest::getVar( 'sess', '' );

		// Ensure we have a session
		if (!$sess) {
			$this->_redirect = JRoute::_('index.php?option=com_myhub');
			return;
		}

		// Check the authorization
		$authorized = $this->_authorize();
		
		// Double-check that the user owns this session.
		$mwdb =& MwUtils::getMWDBO();
		
		$ms = new MwSession( $mwdb );
		if ($authorized === 'admin') {
			$ms->load( $sess );
		} else {
			$juser =& JFactory::getUser();
			$ms->load( $sess, $juser->get('username') );
		}
		
		// Did we get a result form the database?
		if (!$ms->username) {
			$this->_redirect = JRoute::_('index.php?option=com_myhub');
			return;
		}
		
		// Stop the session
		$status = $this->middleware("stop $sess", $output);
		if ($status == 0) {
			echo '<p>Stopping '.$sess.'<br />';
			foreach ($output as $line) 
			{
				echo $line.n;
			}
			echo '</p>'.n;
		}

		// Take us back to the main page...
		$this->_redirect = JRoute::_('index.php?option=com_myhub');
	}

	//-----------

	protected function purge()
	{
		//$no_html = JRequest::getInt( 'no_html', 0 );
		$shost = $this->config->get('storagehost');
		
		if (!$shost) {
			$this->_redirect = JRoute::_('index.php?option=com_myhub' );
		}
		
		$juser =& JFactory::getUser();
		
		$degree = JRequest::getVar('degree','default');
		
		$info = array();
		$msg = '';
		$fp = stream_socket_client($shost, $errno, $errstr, 30);
		if (!$fp) {
			$info[] = "$errstr ($errno)\n";
			$this->setError( "$errstr ($errno)\n" );
		} else {
			
			fwrite($fp, "purge user=". $juser->get('username') .",degree=$degree \n");
			while (!feof($fp)) 
			{
				//$msg .= fgets($fp, 1024)."\n";
				$info[] = fgets($fp, 1024)."\n";
			}
			fclose($fp);
		}
		
		foreach($info as $line) {
			if(trim($line) !='') {
				$msg .= $line.'<br />';
			}
		}
	
		// Output HTML
		$this->__set('output', $msg);
		$this->storage();
		
		// Take us back to the main page...
		//$this->_redirect = JRoute::_('index.php?option=com_myhub' );
	}
	
	//-----------

	private function getDiskUsage() 
	{
		$juser =& JFactory::getUser();
	
		bcscale(6);
	
		$du = MwUtils::getDiskUsage($juser->get('username'));
		if (isset($du['space'])) {
			$val = ($du['softspace'] != 0) ? bcdiv($du['space'], $du['softspace']) : 0;
		} else {
			$val = 0;
		}
		$percent = round( $val * 100 );
		$percent = ($percent > 100) ? 100 : $percent;
		
		$this->remaining = (isset($du['remaining'])) ? $du['remaining'] : 0;
		$this->percent = $percent;
		
		if ($this->percent >= 100 && $du['remaining']==0) {
			$this->_redirect = JRoute::_('index.php?option='.$this->_option.a.'task=storageexceeded');
		}
	}

	//----------------------------------------------------------
	// Views called through AJAX
	//----------------------------------------------------------
	
	protected function renames()
	{
		$mwdb =& MwUtils::getMWDBO();

		$id = JRequest::getInt( 'id', 0 );
		$name = trim(JRequest::getVar( 'name', '' ));
		
		if ($id && $name) {
			$ms = new MwSession( $mwdb );
			$ms->load( $id );
			$ms->sessname = $name;
			$ms->store();
		}
		
		echo $name;
	}

	//-----------

	protected function diskusage()
	{
		$msgs = JRequest::getInt( 'msgs', 0 );
		
		$juser =& JFactory::getUser();
		
		$du = MwUtils::getDiskUsage( $juser->get('username') );
		if (count($du) <=1) {
			// error
			$percent = 0;
		} else {
			bcscale(6);
			$val = ($du['softspace'] != 0) ? bcdiv($du['space'], $du['softspace']) : 0;
			$percent = round( $val * 100 );
		}

		$amt = ($percent > 100) ? '100' : $percent;
		
		echo MwHtml::writeMonitor( $amt, $du, $percent, $msgs, 1 );
	}

	//----------------------------------------------------------
	// Record the usage of a tool
	//----------------------------------------------------------

	private function recordUsage( $app, $uid ) 
	{
		$database =& JFactory::getDBO();
		
		include_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_contribtool'.DS.'contribtool.version.php' );
		$tool = new ToolVersion( $database );
		$tool->loadFromName( $app );
		
		// Ensure a tool is published before recording it
		//if ($tool->state == 1) {
			$created = date( 'Y-m-d H:i:s', time() );
			
			// Get a list of all their recent tools
			$rt = new RecentTool( $database );
			$rows = $rt->getRecords( $uid );

			$thisapp = 0;
			for ($i=0, $n=count( $rows ); $i < $n; $i++) 
			{
				if ($app == trim($rows[$i]->tool)) {
					$thisapp = $rows[$i]->id;
				}
			}
			
			// Get the oldest entry. We may need this later.
			$oldest = end($rows);
		
			// Check if any recent tools are the same as the one just launched
			if ($thisapp) {
				// There was one, so just update its creation time
				$rt->id = $thisapp;
				$rt->uid = $uid;
				$rt->tool = $app;
				$rt->created = $created;
			} else {
				// Check if we've reached 5 recent tools or not
				if (count($rows) < 5) {
					// Still under 5, so insert a new record
					$rt->uid = $uid;
					$rt->tool = $app;
					$rt->created = $created;
				} else {
					// We reached the limit, so update the oldest entry effectively replacing it
					$rt->id = $oldest->id;
					$rt->uid = $uid;
					$rt->tool = $app;
					$rt->created = $created;
				}
			}

			if (!$rt->store()) {
				echo MwHtml::alert( $rt->getError() );
				exit();
			}
		//}
	}
	
	//----------------------------------------------------------
	// Invoke the Python script to do real work.
	//----------------------------------------------------------

	protected function middleware( $comm, &$fnoutput ) 
	{
		$retval = 1; // Assume success.
		$fnoutput = array();

		exec("/bin/sh components/".$this->_option."/mw $comm 2>&1 </dev/null",$output,$status);

		$outln = 0;
		if ($status != 0) {
			$retval = 0;
		}

		// Print out the applet tags or the error message, as the case may be.
		foreach ($output as $line) 
		{
			// If it's a new session, catch the session number...
			if (($retval == 1) && preg_match("/^Session is ([0-9]+)/",$line,$sess)) {
				$retval = $sess[1];
			} else {
				if ($status != 0) {
					$fnoutput[$outln] = $line;
				} else {
					$fnoutput[$outln] = $line;
				}
				$outln++;
			}
		}
		
		return $retval;
	}

	//----------------------------------------------------------
	// Authorization checks
	//----------------------------------------------------------

	private function _authorize($uid=0)
	{
		// Check if they are logged in
		$juser =& JFactory::getUser();
		if ($juser->get('guest')) {
			return false;
		}
		
		// Check if they're a site admin (from Joomla)
		if ($juser->authorize($this->_option, 'manage')) {
			return 'admin';
		}
		
		//$xuser =& XFactory::getUser();
		$xuser = XProfile::getInstance();
		if (is_object($xuser)) {
			// Check if they're a site admin (from LDAP)
			$app =& JFactory::getApplication();
			if (in_array(strtolower($app->getCfg('sitename')), $xuser->get('admin'))) {
				return 'admin';
			}
		}

		// Check if they're the member
		if ($juser->get('id') == $uid) {
			return true;
		}

		return false;
	}
	
	//-----------
	
	private function _getToolAccess($tool, $login='') 
	{
		$xhub =& XFactory::getHub();
		$xlog =& XFactory::getLogger();
	    
		// Ensure we have a tool
		if (!$tool) {
			$this->setError('No tool provided.');
			$xlog->logDebug("mw::_getToolAccess($tool,$login) FAILED null tool check");
			return false;
		}
		//echo '<!-- Tool: '.$tool.' -->';
		// Ensure we have a login
		if ($login == '') {
			$juser =& JFactory::getUser();
			$login = $juser->get('username');
		}
		
		$database =& JFactory::getDBO();
		
		include_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.$this->_option.DS.'mw.license.php' );
		
		// Get all the licenses on this tool
		$lt = new LicenseTool( $database );
		$tlicenses = $lt->getLicenses( $tool );
		//echo '<!-- Tool licenses: ';
		//print_r($tlicenses);
		//echo '-->';
		if (!$tlicenses) {
			$xlog->logDebug("mw::_getToolAccess($tool,$login) FAILED tool license check, no tool licenses");
			//return false;
		}
		
		// Get all the licenses this user has access to
		$lu = new LicenseUser( $database );
		$ulicenses = $lu->getLicenses( $juser->get('id') );
		//echo '<!-- User licenses: ';
		//print_r($ulicenses);
		//echo '-->';
		if (!$ulicenses) {
			$xlog->logDebug("mw::_getToolAccess($tool,$login) FAILED user license check, no user licenses");
			//return false;
		}
		// See if any of the user's licenses and tool's licenses match
		$ulids = array();
		if ($ulicenses) {
			foreach ($ulicenses as $ulicense) 
			{
				$ulids[] = $ulicense->license_id;
			}
		}
		
		$licensed = false;
		if ($tlicenses) {
			foreach ($tlicenses as $tlicense) 
			{
				if (in_array($tlicense->license_id, $ulids)) {
					$licensed = true;
				}
			}
		}
		
		$admin = false;
		
		include_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_contribtool'.DS.'contribtool.version.php' );
		$tv = new ToolVersion( $database );
		$tv->loadFromInstance( $tool );

		// If not licensed, check the user groups to see if they're in a group that should have access
		if (!$licensed) {
			// Check if the user is in any groups for this app
			include_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_contribtool'.DS.'contribtool.toolgroup.php' );
			$tg = new ToolGroup( $database );
			$database->setQuery( "SELECT * FROM ".$tg->getTableName()." WHERE toolid=".$tv->toolid );
			$toolgroups = $database->loadObjectList();

			ximport('xuserhelper');
			$xgroups = XUserHelper::getGroups($juser->get('id'), 'members');
			$groups = array();
			if ($xgroups) {
				foreach ($xgroups as $xgroup) 
				{
					$groups[] = $xgroup->cn;
				}
				if ($toolgroups) {
					foreach ($toolgroups as $toolgroup) 
					{
						if (in_array($toolgroup->cn, $groups)) {
							$licensed = true;
							break;
						}
					}
				}
			}
			
			// Check if the user is in the admin group
			if (!$licensed) {
				$ctconfig =& JComponentHelper::getParams( 'com_contribtool' );
				if ($ctconfig->get('admingroup') != '' && in_array($ctconfig->get('admingroup'), $groups)) {
					$licensed = true;
					$admin = true;
				}
			}
		}
		
		// Check if the tool version is published
		if ($tv->state != 1) {
			// Check if they're either a licensed user to and this is a dev version or they're an admin
			if (($tv->state == 3 && $licensed) || $admin) {
				// Do nothing. They have access.
			} else {
				//if (!$admin && !$licensed && $tv->state == 3) {
				$this->setError('This tool version is not published.');
				$xlog->logDebug("mw::_getToolAccess($tool,$login) FAILED license check, tool version is not published");
				return false;
			}
		}
		
		if ($licensed) {
			//echo '<!-- Licensed: true -->';
			ximport('xgeoutils');
			
			include_once( JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_contribtool'.DS.'contribtool.tool.php' );
			$t = new ToolVersion( $database );
			$t->loadFromInstance( $tool );
			$t->exportControl = strtolower($t->exportControl);
			//echo '<!-- Export control: '.$t->exportControl.' -->';
			switch ($t->exportControl) 
			{
				case 'us':
					if (GeoUtils::ipcountry($_SERVER['REMOTE_ADDR']) == 'us') {
						return true;
					} else {
						$this->setError('This tool may only be accessed from within the U.S. Your current location could not be confirmed.');
						$xlog->logDebug("mw::_getToolAccess($tool,$login) FAILED US export control check");
						return false;
					}
				break;
				
				case 'd1':
					if (GeoUtils::is_d1nation(GeoUtils::ipcountry($_SERVER['REMOTE_ADDR']))) {
						$this->setError('This tool may not be accessed from your current location due to export restrictions.');
						$xlog->logDebug("mw::_getToolAccess($tool,$login) FAILED D1 export control check");
						return false;
					} else {
						return true;
					}
				break;
				
				case 'pu':
					if (GeoUtils::is_iplocation($_SERVER['REMOTE_ADDR'], $t->exportControl)) {
						return true;
					} else {
						$this->setError('This tool may only be accessed by authorized users while on the West Lafayette campus of Purdue University due to license restrictions.');
						$xlog->logDebug("mw::_getToolAccess($tool,$login) FAILED PURDUE export control check");
						return false;
					}
				break;
				
				default:
					return true;
				break;
			}
		//} else {
		//	echo '<!-- Licensed: false -->';
		}
		
		$xlog->logDebug("mw::_getToolAccess($tool,$login) FAILED license check");
		return false;
	}
	
	//-----------
 
	protected function listfiles() 
	{
			
		// Get the app
		$app =& JFactory::getApplication();
		
		$listdir = JRequest::getVar( 'listdir', '' );

		// Build the path
		$path = $this->buildUploadPath( $listdir);

		$d = @dir($path);
		
		$juser =& JFactory::getUser();
		
		// Get the configured upload path
		$base_path  = $this->config->get('storagepath') ? $this->config->get('storagepath') : 'webdav'.DS.'home';
		$base_path .= DS.$juser->get('username');
		
		MwHtml::pageTop( $this->_option, $app, $path );
		
		$dirtree = array();
		$subdir = $listdir;	
		
		if ($subdir) {
			// Make sure the path doesn't end with a slash
			if (substr($subdir, -1) == DS) { 
				$subdir = substr($subdir, 0, strlen($subdir) - 1);
			}
			//Make sure the path doesn't start with a slash
			if (substr($subdir, 0, 1) == DS) { 
				$subdir = substr($subdir, 1, strlen($subdir));
			}
			
			$dirtree = explode(DS, $subdir);
		}
		
		MwHtml::showPath( $this->_option, $dirtree);

		if ($d) {
			$images  = array();
			$folders = array();
			$docs    = array();
	
			while (false !== ($entry = $d->read())) 
			{
				$img_file = $entry; 

				if (is_file($path.DS.$img_file) && substr($entry,0,1) != '.' && strtolower($entry) !== 'index.html') {
					if (eregi( "bmp|gif|jpg|png", $img_file )) {
						$images[$entry] = $img_file;
					} else {
						$docs[$entry] = $img_file;
					}
				} else if (is_dir($path.DS.$img_file) && substr($entry,0,1) != '.' && strtolower($entry) !== 'cvs') {
					$folders[$entry] = $img_file;
				}
			}
			$d->close();	

			MwHtml::imageStyle( $listdir );	

			if (count($images) > 0 || count($folders) > 0 || count($docs) > 0) {	
				ksort($images);
				ksort($folders);
				ksort($docs);

				MwHtml::draw_table_header();

				for ($i=0; $i<count($folders); $i++) 
				{
					$folder_name = key($folders);
					$ffiles = JFolder::files($path.DS.$folder_name, '.', false, true, array());
					$num_files = count($ffiles);	
	
					MwHtml::show_dir( $this->_option, $base_path, DS.$folders[$folder_name], $folder_name, $listdir, $num_files);
					next($folders);
				}
				for ($i=0; $i<count($docs); $i++) 
				{
					$doc_name = key($docs);	
					$icon = '';
					$icon = DS.'templates'.DS. $app->getTemplate() .DS.'images'.DS.'icons'.DS.'16x16'.DS.substr($doc_name,-3).'.png';
					if (!file_exists($icon))	{
						$icon = DS.'templates'.DS. $app->getTemplate() .DS.'images'.DS.'icons'.DS.'16x16'.DS.'unknown.png';
					}
					MwHtml::show_doc( $docs[$doc_name], $icon, $this->_option, $listdir );
					next($docs);
				}
				for ($i=0; $i<count($images); $i++) 
				{
					$image_name = key($images);
					$icon = '';
					/*$icon = 'components/'.$this->_option.'/images/'.substr($image_name,-3).'.png';
					if (!file_exists($icon))	{
						$icon = 'components/'.$this->_option.'/images/unknown.png';
					}*/
					MwHtml::show_doc( $images[$image_name], $icon, $this->_option, $listdir );
					next($images);
				}
				MwHtml::draw_table_footer();
			} else {
				MwHtml::draw_no_results();
			}
		} else {
			MwHtml::draw_no_results();
		}
		
		MwHtml::pageBottom();
	}
	
	//-----------
	
	private function buildUploadPath( $listdir, $subdir='' ) 
	{
		if ($subdir) {
			// Make sure the path doesn't end with a slash
			if (substr($subdir, -1) == DS) { 
				$subdir = substr($subdir, 0, strlen($subdir) - 1);
			}
			// Ensure the path starts with a slash
			if (substr($subdir, 0, 1) != DS) { 
				$subdir = DS.$subdir;
			}
		}
		
		$juser =& JFactory::getUser();
		 
		// Get the configured upload path
		$base_path = $this->config->get('storagepath') ? $this->config->get('storagepath') : 'webdav'.DS.'home';
		$base_path .= DS.$juser->get('username');
		
		if ($base_path) {
			// Make sure the path doesn't end with a slash
			if (substr($base_path, -1) == DS) { 
				$base_path = substr($base_path, 0, strlen($base_path) - 1);
			}
			// Ensure the path starts with a slash
			if (substr($base_path, 0, 1) != DS) { 
				$base_path = DS.$base_path;
			}
		}
		
		// Make sure the path doesn't end with a slash
		if (substr($listdir, -1) == DS) { 
			$listdir = substr($listdir, 0, strlen($listdir) - 1);
		}
		// Ensure the path starts with a slash
		if (substr($listdir, 0, 1) != DS) { 
			$listdir = DS.$listdir;
		}
		// Does the beginning of the $listdir match the config path?
		if (substr($listdir, 0, strlen($base_path)) == $base_path) {
			// Yes - ... this really shouldn't happen
		} else {
			// No - append it
			$listdir = $base_path.$listdir;
		}

		// Build the path
		return $listdir.$subdir;
	}
	
	//-----------

	protected function deletefolder() 
	{
		// Incoming directory (this should be a path built from a resource ID and its creation year/month)
		$listdir = JRequest::getVar( 'listdir', '' );
		if (!$listdir) {
			$this->setError( JText::_('Directory not found.') );
			$this->media();
		}
		
		// Build the path
		$path = $this->buildUploadPath( $listdir);
		
		// Incoming directory to delete
		$folder = JRequest::getVar( 'delFolder', '' );
		if (!$folder) {
			$this->setError( JText::_('Directory not found.') );
			$this->media();
		}
		
		if (substr($folder,0,1) != DS) {
			$folder = DS.$folder;
		}
		
		// Check if the folder even exists
		if (!is_dir($path.$folder) or !$folder) { 
			$this->setError( JText::_('Directory not found.') ); 
		} else {
			// Attempt to delete the file
			jimport('joomla.filesystem.folder');
			if (!JFolder::delete($path.$folder)) {
				$this->setError( JText::_('Unable to delete directory.') );
			}
		}
		
		// Push through to the media view
		$this->listfiles();
	}

	//-----------

	protected function deletefile() 
	{
		// Incoming directory (this should be a path built from a resource ID and its creation year/month)
		$listdir = JRequest::getVar( 'listdir', '' );
		if (!$listdir) {
			$this->setError( JText::_('Directory not found.') );
			$this->media();
		}
		
		// Build the path
		$path = $this->buildUploadPath( $listdir );
		
		// Incoming file to delete
		$file = JRequest::getVar( 'delFile', '' );
		if (!$file) {
			$this->setError( JText::_('File not found.') );
			$this->media();
		}
		
		// Check if the file even exists
		if (!file_exists($path.DS.$file) or !$file) { 
			$this->setError( JText::_('File not found.') ); 
		} else {
			// Attempt to delete the file
			jimport('joomla.filesystem.file');
			if (!JFile::delete($path.DS.$file)) {
				$this->setError( JText::_('Unable to delete file') );
			}
		}
		
		// Push through to the media view
		$this->listfiles();
	}
}
?>
