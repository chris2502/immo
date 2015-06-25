<?php

/**
 *  @class NotificationUser
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliNotificationUser
{
	public $table		= 'killi_notification_user';
	public $primary_key = 'killi_notification_user_id';
	public $database	= RIGHTS_DATABASE;
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->killi_notification_user_id = new PrimaryFieldDefinition();

		$this->killi_notification_id = Many2oneFieldDefinition::create('Notification')
				->setEditable(FALSE);

		$this->killi_user_id = Many2oneFieldDefinition::create('User')
				->setEditable(FALSE);

		$this->killi_notification_read = BoolFieldDefinition::create()
				->setEditable(FALSE);
	}
}
