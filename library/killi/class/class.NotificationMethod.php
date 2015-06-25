<?php

abstract class KilliNotificationMethod extends Common
{
	public function edit($view,&$data,&$total_object_list,&$template_name=NULL)
	{
		parent::edit($view,$data,$total_object_list,$template_name);

		// set lu
		if($view == 'form' && !$data['notification'][$_GET['primary_key']]['killi_notification_read']['value'])
		{
			$hORM = ORM::getORMInstance('notificationuser');
			$notification_user=null;
			
			$hORM->browse($notification_user, $total, array('killi_notification_user_id'), array(array('killi_user_id','=', $_SESSION['_USER']['killi_user_id']['value']), array('killi_notification_id','=', $_GET['primary_key'])));
			$hORM->write(key($notification_user), array('killi_notification_read'=>true), false, $affected);
			
			$data['notification'][$_GET['primary_key']]['killi_notification_read']['value'] = true;
		}

		return TRUE;
	}
	//.....................................................................
	public static function setRead(&$notification_list)
	{
		foreach($notification_list as &$notification)
		{
			$notification['killi_notification_read']['value'] = false;
		}
		
		$notification_user_list=array();
		ORM::getORMInstance('notificationuser')->browse($notification_user_list, $total, array('killi_notification_id','killi_notification_read'), array(array('killi_user_id','=', $_SESSION['_USER']['killi_user_id']['value']), array('killi_notification_id','in', array_keys($notification_list))));
		
		foreach($notification_user_list as $notification_user)
		{
			$notification_list[$notification_user['killi_notification_id']['value']]['killi_notification_read']['value'] = $notification_user['killi_notification_read']['value'];
		}
		
		return TRUE;
	}
	//.....................................................................
	public static function markAsUnread()
	{
		self::mark(FALSE);
		
		return TRUE;
	}
	//.....................................................................
	public static function markAsRead()
	{
		self::mark(TRUE);
		
		return TRUE;
	}
	//.....................................................................
	public static function mark($read)
	{
		if(!empty($_POST['listing_selection']))
		{
			global $hDB;
			
			$hDB->db_execute("update killi_notification_user set killi_notification_read=".($read==TRUE?'1':'0')." where killi_user_id = ".$_SESSION['_USER']['killi_user_id']['value']." and killi_notification_id in (".implode(',',$_POST['listing_selection']).")");
		}

		UI::goBackWithoutBrutalExit();
		
		return TRUE;
	}
	//.....................................................................
	public static function send($subject, $message, $priority, array $user_list)
	{
		ORM::getORMInstance('notification')->create(array(
			'subject'=>$subject,
			'message'=>$message,
			'killi_priority_id'=>$priority
		), $id_notification);
		
		foreach($user_list as $user)
		{
			ORM::getORMInstance('notificationuser')->create(array(
				'killi_notification_id'=>$id_notification,
				'killi_user_id'=>$user
			));
		}
		
		return TRUE;
	}
}
