<?php
    class XMLRPC
    {
        private $_openerp_user_id = NULL;
		private $_client		  = NULL;
		private $_fault			  = NULL;

        //.....................................................................
        public function __construct()
        {
            //---Si on ne connait pas le openerp_user_id
			//Sxmlrpc::setDebug(1);
			$this->_client = new SxmlrpcClient();
			$parameters = array(OPENERP_DATABASE, OPENERP_USER, OPENERP_PASSWORD);
			$this->_openerp_user_id = $this->_client->request("http://" . XMLRPC_SERVER . ":" . XMLRPC_PORT . "/xmlrpc/common", "login", $parameters, TRUE);
            $this->_fault = Sxmlrpc::getFault();

			if ($this->_fault["code"] != 0)
            {
				$_SESSION['_ERROR_LIST']['xmlrpc'] = $this->_fault["message"];
				return TRUE;
            }
    	
            return TRUE;
        }

		public function __destruct()
		{
			unset($this->_client);
		}

		private function initCollections($table)
		{
			if(!isset($_SESSION['xmlrpc_request_collections'][$table])) {
				$_SESSION['xmlrpc_request_collections'][$table] = array();
			}
			
			$_SESSION['xmlrpc_request_collections'][$table]['count'] = "";
			$_SESSION['xmlrpc_request_collections'][$table]['fields'] = "";
		}

		public static function deleteCollection($table, $id = NULL)
		{
			if(isset($_SESSION['xmlrpc_request_collections'][$table]))
			{
				unset($_SESSION['xmlrpc_request_collections'][$table]['count']);
				unset($_SESSION['xmlrpc_request_collections'][$table]['fields']);

				if(!is_null($id) && isset($_SESSION['xmlrpc_request_collections'][$table][$id]))
				{
					Sxmlrpc::deleteCache($_SESSION['xmlrpc_request_collections'][$table][$id]);
					unset($_SESSION['xmlrpc_request_collections'][$table][$id]);
				}
				else
				{
					foreach($_SESSION['xmlrpc_request_collections'][$table] as $id => $cached_key)
					{
						Sxmlrpc::deleteCache($cached_key);
						unset($_SESSION['xmlrpc_request_collections'][$table][$id]);
					}
				}
			}
		}

		//.....................................................................
		private function doRequest($table, $parameters, $cache = FALSE)
		{
			$pref_parameters = array(OPENERP_DATABASE, $this->_openerp_user_id, OPENERP_PASSWORD, $table);
			$final_parameters = array_merge($pref_parameters, $parameters);

			return $this->_client->request("http://" . XMLRPC_SERVER . ":" . XMLRPC_PORT . "/xmlrpc/object", "execute", $final_parameters, $cache);
		}
        //.....................................................................
        public function search($table, array $key, array &$id_list, $offset=0, $limit=NULL, $order=NULL, $count=FALSE)
        {
			if($this->_fault["code"] != 0)
			{
				return array();
			}

			$result = $this->doRequest($table, array("search", $key, $offset*$limit, $limit, $order, array(), $count), TRUE);
			
			$this->_fault = Sxmlrpc::getFault();
			if ($this->_fault["code"] != 0)
			{
				$_SESSION['_ERROR_LIST']['xmlrpc'] = $this->_fault["message"];
				return array();
			}
			

			$id_list = array();

			if($count == FALSE)
			{
				$id_list = $result;

				if(Sxmlrpc::getLastCachedKey())
				{
					$this->initCollections($table);
					foreach($id_list as $id)
					{
						if(!isset($_SESSION['xmlrpc_request_collections'][$table][$id]) || !is_array($_SESSION['xmlrpc_request_collections'][$table][$id]))
							$_SESSION['xmlrpc_request_collections'][$table][$id] = array();

						if(!in_array(Sxmlrpc::getLastCachedKey(), $_SESSION['xmlrpc_request_collections'][$table][$id]))
							$_SESSION['xmlrpc_request_collections'][$table][$id][] = Sxmlrpc::getLastCachedKey();
					}
				}
			}
			else
			{
				$this->initCollections($table);
			
				if(!$_SESSION['xmlrpc_request_collections'][$table]['count'] && Sxmlrpc::getLastCachedKey()) {
					$_SESSION['xmlrpc_request_collections'][$table]['count'] = Sxmlrpc::getLastCachedKey();
				}
				
				return $result;
			}

            return TRUE;
        }
        //.....................................................................
        public function read($table, array $id_list, array $data_list, array &$object_list)
        {
			if($this->_fault["code"] != 0)
			{
				return array();
			}

			$result = $this->doRequest($table, array("read", array_map('intval', $id_list),$data_list), TRUE);

			$this->_fault = Sxmlrpc::getFault();
			if($this->_fault["code"] != 0)
			{
				$_SESSION['_ERROR_LIST']['xmlrpc'] = $this->_fault["message"];
				return array();
			}

			foreach($result as $r)
			{
				$v = $r;
				$reference = array();

				foreach($v as $key=>$value)
				{
					if(is_array($value))
					{
						if(isset($value[1]) && !is_numeric($value[1]))
						{
							$reference[$key] = $value[1];
							unset($value[1]);
						}

						if(isset($value[0]) && !isset($value[1]))
							$v[$key] = $value[0];
					}
				}

				$t = array();
				foreach($v as $k1=>$v1)
				{
					if(is_array($v1))
					{
						$t[$k1]['value'] = array();

						foreach($v1 as $v2)
						{
							$t[$k1]['value'][]= utf8_encode($v2);
						}
					}
					else
					{
						$t[$k1]['value'] = utf8_encode($v1);
					}

					$t[$k1]['value'] = $v1;

					$t[$k1]['editable'] = TRUE;

					if (isset($reference[$k1]))
						$t[$k1]['reference'] = $reference[$k1];
				}

				$object_list[$v['id']] = $t;
			}

			if(Sxmlrpc::getLastcachedKey())
			{
				$this->initCollections($table);
				foreach($id_list as $id)
				{
					if(!isset($_SESSION['xmlrpc_request_collections'][$table][$id]) || !is_array($_SESSION['xmlrpc_request_collections'][$table][$id]))
						$_SESSION['xmlrpc_request_collections'][$table][$id] = array();

					if(!in_array(Sxmlrpc::getLastCachedKey(), $_SESSION['xmlrpc_request_collections'][$table][$id]))
						$_SESSION['xmlrpc_request_collections'][$table][$id][] = Sxmlrpc::getLastCachedKey();
				}

			}

            return TRUE;
        }
        //.....................................................................
        public function create($table,array $data_list, array &$id_list)
        {
			if($this->_fault["code"] != 0)
			{
				return 0;
			}

			$id_list = $this->doRequest($table, array("create", $data_list, $id_list));
			$this->_fault = Sxmlrpc::getFault();
			if($this->_fault["code"] != 0)
			{
				$_SESSION['_ERROR_LIST']['xmlrpc'] = $this->_fault["message"];
				return 0;
			}
			
            return TRUE;
        }
        //.....................................................................
        public function unlink($table,array $id_list)
        {
			if($this->_fault["code"] != 0)
			{
				return FALSE;
			}

			$request = $this->doRequest($table, array("unlink", array_map('intval', $id_list)));
			$this->_fault = Sxmlrpc::getFault();
			if($this->_fault["code"] != 0)
			{
				$_SESSION['_ERROR_LIST']['xmlrpc'] = $this->_fault["message"];
			}
            
            return TRUE;
        }
        //.....................................................................
        public function write($table,array $data_list, $id)
        {
			if($this->_fault["code"] != 0)
			{
				return FALSE;
			}

			$request = $this->doRequest($table, array("write", $id, $data_list));
			$this->_fault = Sxmlrpc::getFault();
			if($this->_fault["code"] != 0)
			{
				$_SESSION['_ERROR_LIST']['xmlrpc'] = $this->_fault["message"];
			}
                
            return TRUE;
        }
        //.....................................................................
        public function fields_get($table, array $data_list, & $object_list)
        {
			if($this->_fault["code"] != 0)
			{
				return array();
			}

			$result = $this->doRequest($table, array("fields_get", $data_list), TRUE);

			$this->_fault = Sxmlrpc::getFault();
			if($this->_fault["code"] != 0)
			{
				$_SESSION['_ERROR_LIST']['xmlrpc'] = $this->_fault["message"];
				return array();
			}


			foreach($result as $key => $datas) {
				if($datas['type'] == 'selection') {
					foreach($object_list as $id => $value) {
						if(isset($object_list[$id][$key]) && !isset($object_list[$id][$key]['reference']))
						{
							$object_list[$id][$key]['reference'] = array();

							foreach($datas['selection'] as $selection) {
								$opt = $selection;
								$object_list[$id][$key]['reference'][$opt[0]] = utf8_encode($opt[1]);
							}
						}
					}
				}
			}

			if(!$_SESSION['xmlrpc_request_collections'][$table]['fields'] && Sxmlrpc::getLastCachedKey()) {
				$this->initCollections($table);
				$_SESSION['xmlrpc_request_collections'][$table]['fields'] = Sxmlrpc::getLastCachedKey();
			}

			return TRUE;
		}
		//.....................................................................
		public function query($table,$action,array $data,array & $result_list)
		{
			if($this->_fault["code"] != 0)
			{
				return FALSE;
			}

			$request = $this->doRequest($table, array($action, $data));
			$this->_fault = Sxmlrpc::getFault();
			if($this->_fault["code"] != 0)
			{
				$_SESSION['_ERROR_LIST']['xmlrpc'] = $this->_fault["message"];
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
    } 

