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
if (isset($_SESSION['superadmin']) &&
	isset($_GET['id']) &&
	isset($_GET['did']) &&
	$_SESSION['superadmin']=='y'||
	$_SESSION['admin']=='y' &&
	isset($_GET['id']) &&
	isset($_GET['did']) &&
	$access_domain )
{
	$sql=sprintf("SELECT * FROM domains WHERE id='%s'",
		$db->escapeSimple($_GET['did']));
	$result=&$db->query($sql);
	$data=$result->fetchrow(DB_FETCHMODE_ASSOC);
	$dnsname=$data['dnsname'];
	$domain_id=$_GET['did'];
	
	
	$smarty->assign('domain',$data['dnsname']);
	$dnsname=$data['dnsname']; // dnsname 
	$smarty->assign('if_imap', $data['disableimap']);
	$smarty->assign('if_pop3', $data['disablepop3']);
	$smarty->assign('if_webmail', $data['disablewebmail']);
	$sql=sprintf("SELECT * FROM users WHERE id='%s'",
		$db->escapeSimple($_GET['id']));
	$result=&$db->query($sql);
	$edata=$result->fetchrow(DB_FETCHMODE_ASSOC);
	
	
	
	$full_email=$edata['email'];
	$smarty->assign('full_email', $full_email);
	$smarty->assign('full_name', $edata['full_name']);
	$smarty->assign('if_imapdisable', $edata['disableimap']);
	$smarty->assign('if_pop3disable', $edata['disablepop3']);
	$smarty->assign('if_webmaildisable', $edata['disablewebmail']);
	
	
	/* Save autoresponder begin */
	if (isset($_POST['autoresponder'])) {
		$error=false;
		if (empty($_POST['esubject']))
		{
		$smarty->assign('error_msg','y');
		$smarty->assign('if_error_autores_subject_empty', 'y');
		$error=true;
		}
		else if (empty($_POST['msg']))
		{
		$smarty->assign('error_msg','y');
		$smarty->assign('if_error_autores_msg_empty', 'y');
		$error=true;
		}
		else if(strlen($_POST['esubject']) > 50)
		{
		$smarty->assign('error_msg','y');
		$smarty->assign('if_error_autores_subject_to_long', 'y');
		$error=true;
		}
		else
		{
		save_autoresponder($_GET['id'],
			$_POST['autoresponder_active'],
			$_POST['esubject'],
			$_POST['msg']);
		run_systemscripts();
		}
	
	}
	/* Save autoresponder end */
	
	
	/*  Autoresponder begin */
	$sql=sprintf("SELECT id,email,active,msg,esubject FROM autoresponder WHERE email='%d'",
	$db->escapeSimple($_GET['id']));
	$result=&$db->query($sql);
	if ($result->numRows()==1)
	{
		$data=$result->fetchrow(DB_FETCHMODE_ASSOC);
		$active=$data['active'];
		$msg=$data['msg'];
		$esubject=$data['esubject'];
		$id=$data['id'];
	}
	elseif($error)
	{
		$active=$_POST['autoresponder_active'];
		$msg=$_POST['msg'];
		$esubject=$_POST['esubject'];
	}
	else
	{
		$active='n';
		$msg=$config_autores_msg;
		$esubject=$config_autores_subject;
	}
	$smarty->assign('esubject', $esubject);
	$smarty->assign('autoresponder_active', $active);
	$smarty->assign('id', $id);
	$smarty->assign('msg', $msg);
	$smarty->assign('email', $_SESSION['email']);
	/*  Autoresponder end */
	
	/* foward option save begin */
	if (isset($_POST['fwdmail_submit'])) {
		update_mailfilter('mail_forward',
			$_GET['id'],$_POST['forwardaddress'],
			$_POST['delete_forward'],
			$_POST['save_local']);
		run_systemscripts();
	}
	/* foward option save begin */
	
	/* options save begin */
	if (isset($_POST['virus_submit'])) {
	if (is_numeric($_POST['del_virus_notifi']) && $_POST['del_virus_notifi']==1)
	{
		$del_virus_notifi=1;
	}
	else
	{
		$del_virus_notifi=0;
	}
	$sql=sprintf("SELECT id,conf,extra,options FROM email_options WHERE email='%s' AND conf='del_virus_notifi'",
		$db->escapeSimple($_GET['id']));
	$result=&$db->query($sql);
	if ($result->numRows() == 1 ) {
		$sql=sprintf("UPDATE email_options SET options='%s' WHERE email='%s' AND conf='del_virus_notifi'",
			$db->escapeSimple($del_virus_notifi),
			$db->escapeSimple($_GET['id']));
	}
	else if($del_virus_notifi==1)
	{
		$sql=sprintf("INSERT INTO email_options SET options='1', email='%s', conf='del_virus_notifi'",
			$db->escapeSimple($_GET['id']));
	}
	$result=&$db->query($sql);
	update_mailfilter('del_virus_notifi',$_GET['id'], $del_virus_notifi,0,0);
	// activate System-Script
	run_systemscripts();
	
	}
	$sql=sprintf("SELECT id,conf,extra,options FROM email_options WHERE email='%s' AND conf='del_virus_notifi'",
	$db->escapeSimple($_GET['id']));
	
	$result=&$db->query($sql);
	if ($result->numRows() ==1) {
		$data=$result->fetchrow(DB_FETCHMODE_ASSOC);
	}
	else {
		$data['options']=0;
	}
	$smarty->assign('del_virus_notifi', $data['options']);
	/* option save ENDE */
	
	
	/* forward option begin */
	$sql=sprintf("SELECT type,filter FROM mailfilter WHERE email='%d' AND active!=0 AND type LIKE 'forward%%'",
		$db->escapeSimple($_GET['id']));
	$result=&$db->query($sql);
	if ($result->numRows()==1) {
		$data=$result->fetchrow(DB_FETCHMODE_ASSOC);
		if ($data['type']=="forward_cc") { // mit kopie ans lokale postfach
			$smarty->assign('if_forward_cc', '1');
		}
		$smarty->assign('forwardaddress', $data['filter']);
	}
	/* forward option end */
	
	
	

	
	//adde domainadmin:
	if (isset($_SESSION['superadmin']) && $_SESSION['superadmin']=='y'
		&& isset($_POST['adddns']) && is_numeric($_POST['add_domain']) )
	{
		$sql=sprintf("SELECT id FROM admin_access WHERE email='%d' AND domain='%d'",
			$db->escapeSimple($_GET['id']),
			$db->escapeSimple($_POST['add_domain']));
		$res=&$db->query($sql);
		if ($res->numRows() == 0)
		{
			$sql=sprintf("INSERT INTO admin_access SET email='%d', domain='%d'",
				$db->escapeSimple($_GET['id']),
				$db->escapeSimple($_POST['add_domain']));
			$res=&$db->query($sql);
		}
	}
	
	//remove domainadmin: 
	if (isset($_SESSION['superadmin']) && $_SESSION['superadmin']=='y' 
		&& isset($_GET['del']) && is_numeric($_GET['del']))
	{
		$sql=sprintf("DELETE FROM admin_access WHERE id='%d'",
			$db->escapeSimple($_GET['del']));
		$res=&$db->query($sql);
	}
	
	
	// Liste AdminDomains
	if (isset($_SESSION['superadmin']) && $_SESSION['superadmin']=='y' )
	{
		$sql=sprintf("SELECT b.dnsname,a.id FROM admin_access AS a LEFT JOIN domains as b ON b.id=a.domain WHERE a.email='%s'",
			$db->escapeSimple($_GET['id']));
		$res_admin=&$db->query($sql);
		$table_admins= array();
		$smarty->assign('ava_ad_domains',$res_admin->numRows());
		while($data=$res_admin->fetchrow(DB_FETCHMODE_ASSOC))
		{
			array_push($table_admins, array(
				'dnsname' => $data['dnsname'],
				'del_id' => $data['id'])
			);
		} //ENDE WHILE 
		$smarty->assign('table_admins', $table_admins);
		
		$sql=sprintf("SELECT a.dnsname,b.email,a.id FROM domains AS a LEFT JOIN admin_access AS b ON b.domain = a.id");
		$res_dns=&$db->query($sql);
		$table_adddns= array();
		while($data=$res_dns->fetchrow(DB_FETCHMODE_ASSOC))
		{
			if ($data['email'] != $_GET['id'])
			{
				array_push($table_adddns, array(
				'dnsname' => $data['dnsname'],
				'dnsid' => $data['id'])
				);
			}
		}
		if (count($table_adddns)==0) {
			$smarty->assign('if_nodomains_found', 'y');
		}
		$smarty->assign('table_adddns', $table_adddns);
		
	}//ende Liste adminDomains
	
	// wenn submit dann aendere daten:
	if (isset($_POST['submit']))
	{
			if (isset($_POST['imap']) && 
			  $_POST['imap'] == "enable" &&
			check_domain_feature($_GET['id'],'imap',$db))
			{
				$imap="0";
			}
			else
			{
				$imap="1";
			}
			if (isset($_POST['pop3']) && 
			  $_POST['pop3'] == "enable" &&
			  check_domain_feature($_GET['id'],'pop3',$db))
			{
				$pop3="0";
			}
			else
			{
				$pop3="1";
			}
			if (isset($_POST['webmail']) && 
			  $_POST['webmail'] == "enable" &&
			  check_domain_feature($_GET['id'],'webmail',$db))
			{
				$webmail="0";
			}
			else
			{
				$webmail="1";
			}
			if (isset($_POST['password']) && !empty($_POST['password']))
			{
				if (check_passwd_length($_POST['password'])==false)
				{
					$smarty->assign('error_msg', 'y');
					$smarty->assign('if_error_password_long','y');
					
					$smarty->assign('full_name',$_POST['full_name'] );
					$error=true;
				}
				else
				{
					$password=$_POST['password'];
					$cpasswd=crypt($_POST['password']);
				}
			} 
			else
			{
				$password=$edata['passwd'];
				$cpasswd=$edata['cpasswd'];
			}
			if ($config['cleartext_passwd']==1) {
				$cleartext=$password;
			}
			else
			{
				$cleartext="";
			}
			if (!$error)
			{
				$sql=sprintf("UPDATE users SET passwd='%s', full_name='%s', disableimap='%d', disablepop3='%d',disablewebmail='%d',
				cpasswd='%s' WHERE id='%d' ",
					$db->escapeSimple($cleartext),
					$db->escapeSimple($_POST['full_name']),
					$db->escapeSimple($imap),
						$db->escapeSimple($pop3),
					$db->escapeSimple($webmail),
					$db->escapeSimple($cpasswd),
					$db->escapeSimple($_GET['id'])) ;
				$result=&$db->query($sql);
				$smarty->assign('success_msg', 'y');
				$smarty->assign('if_email_data_saved', 'y');
			}
	}//ende update DB

} // ENDE ACCESS OK


$smarty->assign('id',$_GET['id']);
$smarty->assign('domainid',$_GET['did']);
?>