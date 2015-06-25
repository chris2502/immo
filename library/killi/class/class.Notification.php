<?php

/**
 *  @class Notification
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliNotification
{
	public $table		= 'killi_notification';
	public $primary_key = 'killi_notification_id';
	public $database	= RIGHTS_DATABASE;
	public $reference	= 'subject';
	public $order		= array('notification_date desc');
	//-------------------------------------------------------------------------
	public function setDomain()
	{
		if(isset($_SESSION['_USER']))
		{
			$this->domain_with_join = array(
					'table'  => array('killi_notification_user'),
					'join'   => array('killi_notification.killi_notification_id=killi_notification_user.killi_notification_id'),
					'filter' =>  array(
							array('killi_notification_user.killi_user_id', '=', $_SESSION['_USER']['killi_user_id']['value'])
					)
			);

			if(isset($_POST['search/notification/killi_notification_read']) && $_POST['search/notification/killi_notification_read']!='')
			{
				$this->domain_with_join['filter'][]=array('killi_notification_user.killi_notification_read', '=', $_POST['search/notification/killi_notification_read']);
			}
		}
	}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->killi_notification_id = new PrimaryFieldDefinition();

		$this->subject = TextFieldDefinition::create(128)
				->setLabel('Sujet')
				->setRequired(TRUE);

		$this->message = TextareaFieldDefinition::create()
				->setLabel('Message')
				->setRequired(TRUE);

		$this->killi_priority_id = Many2oneFieldDefinition::create('Priority')
				->setLabel('Priorité')
				->setRequired(TRUE);

		$this->notification_date = DatetimeFieldDefinition::create()
				->setEditable(FALSE)
				->setLabel('Date');

		$this->killi_notification_read = BoolFieldDefinition::create()
				->setLabel('Lu')
				->setEditable(FALSE)
				->setFunction('Notification', 'setRead');
	}
}
