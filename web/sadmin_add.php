<?php
/******************************************************************************
* Copyright (C) 2006 Jonas Genannt <jonas.genannt@brachium-system.net>
* 
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
******************************************************************************/
session_start();
include("config.inc.php");
include("check_access.php");

if (isset($_SESSION['superadmin']) && $_SESSION['superadmin']=='y'
    && isset($_SESSION['manager'])
    && $_SESSION['manager'] =='y'
    && isset($_POST['submit']))
{
	$wrong=0;
	if (empty($_POST['username']) || !isset($_POST['username']))
	{
		$smarty->assign('error_msg','y');
		$smarty->assign('if_error_missing_input', 'y');
		$wrong=1;
	}
	elseif(!ereg("^([a-zA-Z0-9]+)$",$_POST['username']))
	{
		$smarty->assign('error_msg', 'y');
		$smarty->assign('if_sadmin_wrong_char' ,'y');
		$smarty->assign('if_username_wrong','y');
		$wrong=1;
	}
	elseif(check_passwd_length($_POST['passwd']) ==false )
	{
		$smarty->assign('error_msg','y');
		$smarty->assign('if_error_password_long', 'y');
		$wrong=1;
	}
	elseif(adm_user_exits($_POST['username'],0,$db))
	{
		$smarty->assign('error_msg','y');
		$smarty->assign('if_error_sadmim_exits','y');
		$smarty->assign('if_user_exits', 'y');
		$wrong=1;
	}
	else
	{
		if ($_POST['access']=="enable") {
			$access='y'; }
		else {
			$access='n'; }
		if ($_POST['manager']=="enable") {
			$manager='y'; }
		else {
			$manager='n'; }
		
			
		if ($config['cleartext_passwd']==1) {
				$cleartext=$_POST['passwd'];
		}
		else
		{
			$cleartext="";
		}
		
		
		$sql=sprintf("INSERT INTO adm_users SET username='%s', passwd='%s', full_name='%s', access='%s', manager='%s',cpasswd='%s'",
			$db->escapeSimple($_POST['username']),
			$db->escapeSimple($cleartext),
			$db->escapeSimple($_POST['full_name']),
			$db->escapeSimple($access),
			$db->escapeSimple($manager),
			$db->escapeSimple(crypt($_POST['passwd']))
			);
		$res=&$db->query($sql);
		if (!PEAR::isError($res)) {
			$smarty->assign('success_msg', 'y');
			$smarty->assign('if_sadmin_created','y');
		}
	}
	if ($wrong==1)
	{
		$smarty->assign('sausername', $_POST['username']);
		$smarty->assign('full_name', $_POST['full_name']);
	}
	
}
$smarty->assign('template','sadmin_add.tpl');
$smarty->display('structure.tpl');
?>