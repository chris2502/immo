<?php

abstract class KilliObjectMethod extends Common
{
	public function getAttributeList(&$datasrc)
	{
		$hInstance = ORM::getObjectInstance($_GET['primary_key']);

		foreach($hInstance as $attribute_name=>$attribute)
		{
			if ($attribute instanceof FieldDefinition)
			{
				$attribute_data = array();

				$attribute_data['attr_name']['value']	  =
				$attribute_data['attr_name']['reference'] = $attribute_name;

				$attribute_data['nom']['value']			= $attribute->name;
				$attribute_data['type']['value']		= is_array($attribute->type) ? 'Array' : $attribute->type;
				$attribute_data['required']['value']	= $attribute->required;

				Rights::getRightsByAttribute($_GET['primary_key'], $attribute_name, $attribute_data['read']['value'], $attribute_data['write']['value'], array($_GET['profil/killi_profil_id']));

				$attribute_data['read']['editable']  = TRUE;
				$attribute_data['write']['editable'] = $attribute->editable;

				if (ADMIN_PROFIL_ID == $_GET['profil/killi_profil_id'])
				{
					$attribute_data['read']['editable']  = FALSE;
					$attribute_data['write']['editable'] = FALSE;
				}

				if(READONLY_PROFIL_ID!==NULL && READONLY_PROFIL_ID == $_GET['profil/killi_profil_id'])
				{
					$attribute_data['write']['editable'] = FALSE;
				}

				$datasrc[] = $attribute_data;
			}
		}
	}
	//.....................................................................
	public function edit($view,&$data,&$total_object_list,&$template_name=NULL)
	{
		$data['object'][$_GET['primary_key']]['nom']['value'] = $_GET['primary_key'];

		//---On recup le profil
		$data['profil'] = array();
		ORM::getORMInstance('profil')->read(array($_GET['profil/killi_profil_id']),$data['profil'],array('nom'));

		$data['profil'][$_GET['profil/killi_profil_id']]['current_object']['value'] = $_GET['primary_key'];

		return True;
	}
	//.....................................................................
	public function write($data)
	{
		if($data['profil/killi_profil_id']==ADMIN_PROFIL_ID)
		{
			return True;
		}

		//---On parcour les $_POST
		foreach($data as $key=>$value)
		{
			if (mb_substr($key,0,10)==="attribute/")
			{
				list($null, $attr, $attr_name) = explode("/",$key);

				if(!in_array($attr,array('read','write')))
				{
					continue;
				}

				$this->_hDB->db_execute("insert into ".RIGHTS_DATABASE.".killi_attributes_rights
						  set object_name=\"".Security::secure($data['object/nom'])."\",
						  profil_id=\"".Security::secure($data['profil/killi_profil_id'])."\",
						  `$attr`=\"".Security::secure($value)."\",
						  attribute_name=\"".Security::secure($attr_name)."\"
						  on duplicate key update `$attr`=\"".Security::secure($value)."\"");
			}
		}

		return True;
	}
}
