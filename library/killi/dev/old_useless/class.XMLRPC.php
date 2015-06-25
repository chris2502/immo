<?php

/**
 *
 *  @class XMLRPC
 *  @Revision $Revision: 4457 $
 *
 */

include(KILLI_DIR . '/library/xmlrpc_lib.php');

class XMLRPC
{
	private $_openerp_user_id = NULL;
	private $_timeout		 = 300;

	//.....................................................................
	private function _connect(&$msg, &$link)
	{
		//---Si on ne connait pas le openerp_user_id
		if ($this->_openerp_user_id==NULL)
		{
			$link = new xmlrpc_client("http://".XMLRPC_SERVER.":".XMLRPC_PORT."/xmlrpc/common");
			$msg = new xmlrpcmsg('login');
			$msg->addParam(new xmlrpcval(OPENERP_DATABASE, "string"));
			$msg->addParam(new xmlrpcval(OPENERP_USER, "string"));
			$msg->addParam(new xmlrpcval(OPENERP_PASSWORD, "string"));
			$resp   =  $link->send($msg);

			if ($resp->errstr!="")
			{
				throw new Exception(nl2br($resp->errstr));
			}

			$val	= $resp->value();
			$this->_openerp_user_id  = $val->scalarval();
		}

		$link = new xmlrpc_client("http://".XMLRPC_SERVER.":".XMLRPC_PORT."/xmlrpc/object");
		$msg  = new xmlrpcmsg('execute');

		//---Auth
		$msg->addParam(new xmlrpcval(OPENERP_DATABASE, "string"));
		$msg->addParam(new xmlrpcval($this->_openerp_user_id, "int"));
		$msg->addParam(new xmlrpcval(OPENERP_PASSWORD, "string"));

		return TRUE;
	}
	//.....................................................................
	public static function deleteCollection($table, $id = NULL)
	{
		return TRUE;
	}
	//.....................................................................
	public function search($table, array $key, array &$id_list, $offset=0, $limit=NULL, $order=NULL, $count=FALSE)
	{
		$this->_connect($msg, $link);

		$msg->addParam(new xmlrpcval($table, "string"));

		//---Prepare key
		$formated_key = array();
		foreach($key as $v)
		{
			$nk = array();
			foreach($v as $k1 => $v1)
			{
				if (is_int($v1))
					$nk[$k1] = new xmlrpcval($v1,'int');
				else
					$nk[$k1] = new xmlrpcval($v1,'string');
			}

			$formated_key[] = new xmlrpcval($nk,'array');
		}

		//---Query
		$msg->addParam(new xmlrpcval("search", "string"));
		$msg->addParam(new xmlrpcval($formated_key, "array"));
		$msg->addParam(new xmlrpcval($offset*$limit, "int"));
		$msg->addParam(new xmlrpcval($limit, "int"));
		$msg->addParam(new xmlrpcval($order, "string"));
		$msg->addParam(new xmlrpcval(NULL, "array"));
		$msg->addParam(new xmlrpcval($count, "boolean"));

		$resp = $link->send($msg);

		if ($resp->errstr!="")
		{
			throw new Exception(nl2br($resp->errstr));
		}

		$result = $resp->value()->scalarval();
		$id_list = array();

		if(is_array($result))
		{
			foreach($result as $r)
			{
				$id_list[] = $r->getVal();
			}

			return TRUE;
		}
		return $result;
	}
	//.....................................................................
	public function read($table, array $id_list, array $data_list, array &$object_list)
	{
		$this->_connect($msg, $link);

		$msg->addParam(new xmlrpcval($table, "string"));

		//---Prepare ids list
		$formated_key = array();
		foreach($id_list as $k1 => $v1)
		{
			$formated_key[] = new xmlrpcval($v1, "int");
		}

		//---Prepare data list
		$formated_data = array();
		foreach($data_list as $k1 => $v1)
		{
			$formated_data[$k1] = new xmlrpcval($v1, "string");
		}

		//---Query
		$msg->addParam(new xmlrpcval("read", "string"));
		$msg->addParam(new xmlrpcval($formated_key, "array"));
		$msg->addParam(new xmlrpcval($formated_data, "array"));

		$resp = $link->send($msg);

		if ($resp->errstr!="")
		{
			throw new Exception(nl2br($resp->errstr));
		}

		$result = $resp->value()->scalarval();

		foreach($result as $r)
		{
			$v = $r->getVal();
			$reference = array();
			foreach($v as $key=>$value)
			{
				if (is_array($value))
				{
					$v[$key] = array();
					foreach($value as $k1=>$v1)
					{
						if (isset($v1->me['int']))
						{
							if(!isset($value[1]->me['string']))
								$v[$key][] = $v1->me['int'];
							else
								$v[$key] = $v1->me['int'];
						}

						if (isset($value[1]->me['string']))
							$reference[$key] = $value[1]->me['string'];
					}
				}
			}

			$t = array();
			foreach($v as $k1=>$v1)
			{
					$field_infos = array();
					if(isset($fields_infos[$k1]))
						$field_infos = $fields_infos[$k1]->getVal();

					if(is_array($v1))
					{
						$t[$k1]['value'] = array_map('utf8_encode', $v1);
					}
					else
					{
						$t[$k1]['value'] = utf8_encode($v1);
					}

				$t[$k1]['editable'] = TRUE;

				if (isset($reference[$k1]))
					$t[$k1]['reference'] = $reference[$k1];
			}

			$object_list[$v['id']] = $t;
		}

		return TRUE;
	}
	//.....................................................................
	public function create($table,array $data_list, array &$id_list)
	{
		foreach($data_list as  $data)
		{
			$this->_connect($msg, $link);
			$msg->addParam(new xmlrpcval($table, "string"));

			//---Prepare data list
			$formated_data = array();
			foreach($data as $k1 => $v1)
			{
				if (is_int($v1))
					$formated_data[$k1] = new xmlrpcval($v1, "int");
				elseif (is_array($v1))
				{
					$av1 = array();
					foreach($v1 as $av)
						$av1[] = new xmlrpcval($av, "int");

					$formated_data[$k1] = new xmlrpcval(array(new xmlrpcval(array(new xmlrpcval(6, "int"), new xmlrpcval(0, "int"), new xmlrpcval($av1, "array")), "array")), "array");
				}
				else
					$formated_data[$k1] = new xmlrpcval($v1, "string");
			}

			//---Query
			$msg->addParam(new xmlrpcval("create", "string"));
			$msg->addParam(new xmlrpcval($formated_data, "struct"));
			$resp = $link->send($msg);

			if ($resp->errstr!="")
			{
				throw new Exception(nl2br($resp->errstr));
			}

			$id_list[] = $resp->val->me['int'];
		}

		return TRUE;
	}
	//.....................................................................
	public function unlink($table,array $id_list)
	{
		foreach($id_list as $data)
		{
			$this->_connect($msg, $link);
			$msg->addParam(new xmlrpcval($table, "string"));

			//---Query
			$msg->addParam(new xmlrpcval("unlink", "string"));
			$msg->addParam(new xmlrpcval(array(new xmlrpcval($data, "int")), "array"));
			$resp = $link->send($msg);

			if ($resp->errstr!="")
			{
				throw new Exception(nl2br($resp->errstr));
			}
		}

		return TRUE;
	}
	//.....................................................................
	public function write($table,array $data_list, $id)
	{
		$this->_connect($msg, $link);

		$msg->addParam(new xmlrpcval($table, "string"));

		//---Prepare data list
		$formated_data = array();
		foreach($data_list as $k1 => $v1)
		{
			if (is_bool($v1))
				$formated_data[$k1] = new xmlrpcval($v1, "boolean");
			elseif (is_array($v1))
			{
				$av1 = array();
				foreach($v1 as $av)
					$av1[] = new xmlrpcval($av, "int");

				$formated_data[$k1] = new xmlrpcval(array(new xmlrpcval(array(new xmlrpcval(6, "int"), new xmlrpcval(0, "int"), new xmlrpcval($av1, "array")), "array")), "array");
			}
			else
			{
				$formated_data[$k1] = new xmlrpcval($v1, "string") ;
			}
		}

		//---Query
		$msg->addParam(new xmlrpcval("write", "string"));

		if( is_array( $id ) === true )
		{
			$id_liste = array() ;
			for( $i = 0 ; $i < count( $id  ) ; $i++ )
			{
				$id_liste[] = new xmlrpcval( $id[ $i ] , "int" ) ;
			}

			$msg->addParam( new xmlrpcval( $id_liste, "array" ) ) ;
		}
		else
		{
			$msg->addParam(new xmlrpcval(array(new xmlrpcval($id,"int")), "array"));
		}

		$msg->addParam(new xmlrpcval($formated_data, "struct"));

		$resp = $link->send($msg);

		if ($resp->errstr!="")
		{
			throw new Exception(nl2br($resp->errstr));
		}

		return TRUE;
	}
	//.....................................................................
	public function fields_get($table, array $data_list, & $object_list)
	{
		$this->_connect($msg,$link);
		$msg->addParam(new xmlrpcval($table, "string"));
		$msg->addParam(new xmlrpcval("fields_get", "string"));

		if(count($data_list))
		{
			$formated_data = array();
			foreach($data_list as $k1 => $v1)
			{
				$formated_data[$k1] = new xmlrpcval($v1, "string");
			}

			$msg->addParam(new xmlrpcval($formated_data, "array"));
		}

		$resp = $link->send($msg);

		if ($resp->errstr!="")
		{
			throw new Exception(nl2br($resp->errstr));
		}

		$result = $resp->value()->scalarval();

			foreach($result as $key => $datas) {
				$field_infos = $datas->getVal();
			if($field_infos['type'] == 'selection') {
				foreach(array_keys($object_list) as $id) {
					if(isset($object_list[$id][$key]) && !isset($object_list[$id][$key]['reference']))
					{
						$object_list[$id][$key]['reference'] = array();

						foreach($field_infos['selection'] as $selection) {
							$opt = $selection->getVal();
							$object_list[$id][$key]['reference'][$opt[0]] = utf8_encode($opt[1]);
						}
					}
				}
			}
		}

		return TRUE;
	}
	//.....................................................................
	public function query($table,$action,array $data,array & $result_list)
	{
		$this->_connect($msg, $link);
		$msg->addParam(new xmlrpcval($table, "string"));

		//---Query
		$msg->addParam(new xmlrpcval($action, "string"));

		foreach($data as $key=>$value)
		{
			if (is_int($value))
				$msg->addParam(new xmlrpcval($value, "int"));
			else if (is_bool($value))
				$msg->addParam(new xmlrpcval($value, "boolean"));
			else if (is_array($value))
			{
				$tmp = array();
				foreach($value as $v)
					$tmp[] = new xmlrpcval($v, "int");

				$msg->addParam(new xmlrpcval($tmp, "array"));
			}
			else
				$msg->addParam(new xmlrpcval($value, "string"));
		}

		$resp = $link->send($msg,$this->_timeout);

		if ($resp->errstr!="")
		{
			throw new Exception(nl2br($resp->errstr));
		}

		$result = $resp->value()->scalarval();

		if (is_array($result))
		{
			foreach($result as $r)
			{
				$v = $r->getVal();

				foreach($v as $key=>$value)
				{
					if (is_array($value))
					{
						$v[$key] = $value[0]->me['int'];
					}
				}

				$result_list[] = $v;
			}
		}
		else
		{
			$result_list[] = $result;
		}

		return TRUE;
	}
	//.....................................................................
	public function browse($table, array $key, array $fields, array &$data_list)
	{
		$id_list = array();
		$this->search($table,$key,$id_list);
		$this->read($table, $id_list, $fields, $data_list);

		return TRUE;
	}
	//.....................................................................



}

