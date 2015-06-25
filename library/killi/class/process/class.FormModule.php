<?php

/**
 *  @class FormModule
 *  @Revision $Revision: 3647 $
 *
 */

/*
Exemple de format de configuration du module Form :
{
	"template": "2columns.xml",
	"structure": {
		"left": [
			"title",
			"contact/firstname",
			"contact/lastname"
		],
		"right": [
			"contact/birthdate"
		]
	},
	"nodes": {
		"contact/firstname": {
			"XMLNode": "field",
			"type": "text",
			"label": "Quel est votre prénom ?",
			"placeholder": "Entrez ici votre prénom"
		},
		"contact/lastname": {
			"XMLNode": "field",
			"type": "text",
			"label": "Quel est votre nom ?",
			"placeholder": "Entrez ici votre nom"
		},
		"title": {
			"XMLNode": "title",
			"type": "title",
			"string": "Ajout d'un contact"
		},
		"contact/birthdate": {
			"XMLNode": "field",
			"type": "date",
			"label": "Quel est votre date de naissance ?",
			"placeholder": "Entrez ici votre date de naissance"
		}
	}
}
*/

class FormModule extends Module
{
	public function onPrev()
	{
		return $this->goPrev();
	}

	public function onNext()
	{
		/**
		 * Vérification des entrées utilisateurs.
		 */
		$fields = $this->getModuleData();

		$form_data = array();
		if(isset($fields['nodes']))
		{
			foreach($fields['nodes'] AS $field_attr => $field)
			{
				$set = str_replace('.', '_', $field_attr);
				$attrs = explode('.', $field_attr);
				$f = &$form_data;
				if(!empty($field['XMLNode']) && ($field['XMLNode'] == 'field'))
				{
					if(empty($_POST[$set]) && !empty($field['required']) && $field['required'] == TRUE)
					{
						Alert::error('Champ manquant !', 'Vous devez remplir le champ "'.$field['label'].'" !');
						$this->_setDataToSession(array_keys($fields['nodes']), $_POST);
						return FALSE;
					}

					if (isset($field['constraints']) && count($field['constraints']['values']) > 0)
					{
						$msg_errors = array();
						$this->_checkConstraints($field_attr, $field, $_POST[$set], $msg_errors);
						if($msg_errors)
						{
							Alert::error('Contraintes non respectées', implode('<br />', $msg_errors));
							$this->_setDataToSession(array_keys($fields['nodes']), $_POST);
							return FALSE;
						}
					}

					//$form_data[$field_attr] = $_POST[$set];
					/** WOUHOU !!! VIVE LES POINTEURS !! **/
					foreach($attrs AS $attr)
					{
						$f = &$f[$attr];
					}
					$f = $_POST[$set];
				}
				
				if(!empty($field['XMLNode']) && $field['XMLNode'] == 'docuploader')
				{
					if(empty($_FILES['docupload_' . $set]['name']) && !empty($field['required']) && $field['required'] == TRUE)
					{
						Alert::error('Champ manquant !', 'Vous devez remplir le champ "'.$field['label'].'" !');
						return FALSE;
					}
					
					$token_id = $this->getTokenId();

					$files = $_FILES['docupload_' . $set]['tmp_name'];

					$id_document = array();
					foreach ($files as $key => $filepath)
					{
						// Enregistrement des fichiers dans la table documents
						DocumentLocal::store($filepath, $field['document_type_filter'], 'processtoken', $token_id, $id_document[], FALSE, TRUE);
					}
					
					foreach($attrs AS $attr)
					{
						$f = &$f[$attr];
					}
					foreach ($id_document as $key => $value)
					{
						$f = $id_document;
					}
				}
			}
		}

		/**
		 * Sauvegarde des données du formulaire.
		 */
		$this->saveData($form_data);

		/**
		 * Déplacement du token à l'étape suivante.
		 */
		return $this->goNext();
	}

	public function render()
	{
		?><input type="hidden" id="__token" name="token" value="<?= $_SESSION['_TOKEN']; ?>"/><?php
		?><div style="position:relative;" class="ui-tabs-panel ui-widget-content ui-corner-bottom"><?php

			$fields = $this->getModuleData();
			
			$template = NULL;
			if(empty($fields['template']))
			{
				$template = 'simple.xml';
				Alert::error('Template inexistant', 'Aucun template n\'a été défini pour ce formulaire !');
				AlertXMLNode::show_alerts('global');
			}
			else
			{
				$template = $fields['template'];
			}

			if (isset($_SESSION['_POST']))
			{
				foreach($_SESSION['_POST'] as $attr => $value)
				{
					$_GET[$attr] = $value;
				}
				unset($_SESSION['_POST']);
			}
			else
			{
				$data = $this->getData();
				if(isset($data[$this->_process_internal_name]))
				{
					foreach($data[$this->_process_internal_name] AS $attr => $value)
					{
						foreach($value AS $k => $v)
						{
							$_GET[$attr . '.'. $k] = $v;
						}
					}
				}
			}
			
			$_GET['mode'] = 'edition'; // Force mode edition
			$hUI = new UI();
			$hUI->load('./library/killi/process/template/'.$template);
			$hUI->renderNodeName('process', $fields);

			?>
			<div class="wizard-btn-container">
					<?php
					if($this->hasPrev())
					{
						?><button class="wizard-btn wizard-btn-validation wizard-btn-prev" type="submit" name="submit" value="preceding">Précédent</button><?php
					}

					if($this->hasNext())
					{
						?><button class="wizard-btn wizard-btn-validation wizard-btn-next" type="submit" name="submit" value="following">Suivant</button><?php
					}
					else
					{
						?><button class="wizard-btn wizard-btn-validation wizard-btn-end" type="submit" name="submit" value="terminate">Terminer</button><?php
					}
					?>
				</div>
			</div>
		<?php
	}

	protected function _setDataToSession($fields, $post)
	{
		if (!empty($fields) && !empty($post))
		{
			foreach ($fields as $k)
			{
				$set = str_replace('.', '_', $k);
				if (isset($post[$set]))
				{
					$_SESSION['_POST'][$k] = $post[$set];
				}
			}
		}
	}

	protected function _checkConstraints($field_attr, $field, $value, &$msg_errors)
	{
		$msg_errors = array();
		switch($field['type'])
		{
			case("price"):
			case("int"):
				if (!is_numeric($value))
				{
					$msg_errors[] = 'Le champ ' . $field['label'] . ' n\'est pas numérique !';
					return FALSE;
				}
				break;

			case("many2many"):
				if (!is_array($value))
				{
					$msg_errors[] = $field['label'] . ' must be array !';
					return FALSE;
				}
				break;

			case("many2one"):
				if (!is_numeric($value))
				{
					$msg_errors[] = 'La référence de ' . $field['label'] . ' n\'est pas valide !';
					return FALSE;
				}
				break;

			case("text"):
			case("selection"):
			case("password"):
				if (!is_string($value))
				{
					$msg_errors[] = 'Le champ ' . $field['label'] . ' n\'est pas une chaîne de caractères !';
					return FALSE;
				}
				break;

			case 'csscolor':
				if(!preg_match('/^#[A-F0-9]{6}$/', $value))
				{
					$msg_errors[] = 'Le champ ' . $field['label'] . ' n\'est pas une couleur CSS !';
					return FALSE;
				}
				break;

			case 'date':
				if(!preg_match('#^([0-9]{2})/([0-9]{2})/([0-9]{4})\s*(([0-9]{2}):([0-9]{2}):([0-9]{2}))?$#', $value, $date_parts))
				{
					$msg_errors[] = 'La date du champ ' . $field['label'] . 'doit être au format "JJ/MM/AAAA" ou "JJ/MM/AAAA HH:MM:SS" : '.$value;
					return FALSE;
				}
			break;
		}

		foreach($field['constraints']['values'] as $constraint)
		{
			//---Decoupe Constraints::checkSize(3,16)
			$raw = explode('::',$constraint);
			$class = $raw[0];

			preg_match_all('/^([a-zA-Z]+)\((.*)\)$/', $raw[1], $raw2);
			if(array_key_exists(0, $raw2[1]))
			{
				$params = array();
				$params[] = $value;

				foreach(explode(',',$raw2[2][0]) as $param)
				{
					if ($param != NULL)
					{
						$params[] = $param;
					}
				}

				$msg_errors = NULL;
				$params[] = &$msg_errors;

				call_user_func_array(array($raw[0], $raw2[1][0]),$params);

				if ($msg_errors)
				{
					foreach ($msg_errors as $k => $v)
					{
						$msg_errors[$k] = '"' . $field['label'] . '" : ' . $msg_errors[$k];
					}
				}
			}
		}
		return TRUE;
	}
}
