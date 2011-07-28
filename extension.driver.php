<?php

	Final Class extension_Content_Type_Mappings extends Extension{
		
		const SETTINGS_GROUP = 'content-type-mappings';
		
		const EXT_NAME = 'Content Type Mappings';
		
		/** Extension **/
		
		public function about(){
			return array('name' => self::EXT_NAME,
						 'version' => '1.4',
						 'release-date' => '2011-07-28',
						 'author' => array ( 
							array( 'name' => 'Symphony Team',
								   'website' => 'http://www.symphony21.com',
								   'email' => 'team@symphony21.com'
							),array(
								'name'			=> 'Solutions Nitriques',
								'website'		=> 'http://www.nitriques.com/open-source/',
								'email'			=> 'open-source (at) nitriques.com'
							)),
						'description'	=> __('Allows more control over the Symphony frontend page content type mappings'),
						'compatibility' => array(
							'2.2.1' => true,
							'2.2' => true,
							'2.1.2' => true,
							'2.1.1' => true,
							'2.1.0' => true,
						)
				 	);
		}
		
		public function getSubscribedDelegates(){
			return array(
					array(
						'page' => '/frontend/',
						'delegate' => 'FrontendPreRenderHeaders',
						'callback' => 'setContentType'							
					),
					array(
						'page'		=> '/system/preferences/',
						'delegate'	=> 'AddCustomPreferenceFieldsets',
						'callback'	=> 'addCustomPreferenceFieldsets'
					),
					array(
						'page'      => '/system/preferences/',
						'delegate'  => 'Save',
						'callback'  => 'save'
					)	
				); 
		}

		
		/** Preferences **/
		
		/**
		 * Delegate handle that adds Custom Preference Fieldsets
		 * @param string $page
		 * @param array $context
		 */
		public function addCustomPreferenceFieldsets($context) {
			$mappings = Symphony::Configuration()->get();
			$mappings = $mappings[self::SETTINGS_GROUP];
			
			// creates the field set
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', self::EXT_NAME));

			// create a paragraph for short intructions
			$p = new XMLElement('p', __('Define here content type you wanna use: You can delete them by setting no value in the input'), array('class' => 'help'));

			// append intro paragraph
			$fieldset->appendChild($p);

			// outter wrapper
			$out_wrapper = new XMLElement('div');

			// create a wrapper
			$wrapper = new XMLElement('div');
			
			foreach($mappings as $type => $content_type){
				$wrapper->appendChild($this->generateField($type, $type, $content_type));
			}
			
			$out_wrapper->appendChild($wrapper);
			
			// new content type form
			// create a wrapper
			$wrapper_new = new XMLElement('div');
			$wrapper_new->setAttribute('class', 'group');

			$wrapper_new->appendChild($this->generateField('new-key', 'Page Type'));
			$wrapper_new->appendChild($this->generateField('new-value', 'Mime type'));
			
			$title_new = new XMLElement('h4');
			$title_new->setValue(__('Create a new content type mapping'));
			
			$out_wrapper->appendChild($title_new);
			$out_wrapper->appendChild($wrapper_new);

			// wrapper into fieldset
			$fieldset->appendChild($out_wrapper);

			// adds the field set to the wrapper
			$context['wrapper']->appendChild($fieldset);
		}
		
		/**
		 * Quick utility function to make a input field+label
		 * @param string $settingName
		 * @param string $text
		 * @param string $value @optional
		 * @param string $type @optional
		 */
		public function generateField($settingName, $text, $value='', $type = 'text') {
			$inputAttr = array();
			$inputText = $value;
			
			switch ($type) {
				case 'checkbox':
					if ($inputText == 'on') {
						$inputAttr['checked'] = 'checked';
					}
					$inputText = '';
					break;
			}

			// create the label and the input field
			$wrap = new XMLElement('div');
			$label = Widget::Label();
			$input = Widget::Input(
						'settings[' . self::SETTINGS_GROUP . '][' . $settingName .']',
						$inputText,
						$type,
						$inputAttr
					);

			// set the input into the label
			$label->setValue(__($text). ' ' . $input->generate() . $err);

			$wrap->appendChild($label);

			return $wrap;
		}
		
		/**
		 * Delegate handle that is called prior to saving the settings
		 * @param array $context
		 */
		public function save(&$context){
			$s = $context['settings'][self::SETTINGS_GROUP];

			if ( is_array($s) ) {
				
				// Detect new enty
				if ( !empty ($s['new-key']) && !empty ($s['new-value']) )  {
					$context['settings'][self::SETTINGS_GROUP][$s['new-key']] = $s['new-value'];
				}
				
				// always remove those, since we don't want them to be saved
				$s ['new-key'] = null;
				$s ['new-value'] = null;
				
				// Pass all the settings: remove empt one
				foreach ($s as $key => $setting) {
					if (empty($setting) || empty($key)) {
						// remove to assure they were not in the config
						Symphony::Configuration()->remove($key, self::SETTINGS_GROUP);
						// remove from context to prevent Symphony from saving it
						unset($context['settings'][self::SETTINGS_GROUP][$key]);	
					}
				}
			}
		}
		
		/** Installation **/
		
		public function install(){
			
			$initial_mappings = array(
				'xml' => 'text/xml; charset=utf-8',
				'text' => 'text/plain; charset=utf-8',
				'css' => 'text/css; charset=utf-8',
				'json' => 'application/json; charset=utf-8'
			);
			
			foreach($initial_mappings as $type => $content_type){
				Symphony::Configuration()->set($type, $content_type, self::SETTINGS_GROUP);
			}
			
			Administration::instance()->saveConfig();	
		}	

		public function uninstall(){
			Symphony::Configuration()->remove(self::SETTINGS_GROUP);			
			Administration::instance()->saveConfig();
		}

		
		
		/** Utilities **/
		
		public function resolveType($type){
			// fix issue #2, for downloadables files
		    if($type{0} == '.'){  
		        return Symphony::Configuration()->get(strtolower(substr($type, 1)), 'content-type-mappings');                               
		    } else {
		        return Symphony::Configuration()->get(strtolower($type), 'content-type-mappings');              
		    }
		}
		
		public function setContentType(array $context=NULL){
			$page_data = Frontend::Page()->pageData();
			
			if(!isset($page_data['type']) || !is_array($page_data['type']) || empty($page_data['type'])) return;
			
			foreach($page_data['type'] as $type){
				$content_type = $this->resolveType($type);
				
				if(!is_null($content_type)){	
					Frontend::Page()->addHeaderToPage('Content-Type', $content_type);
				}
				
				if($type{0} == '.'){  
					$FileName = $page_data['handle'];
					Frontend::Page()->addHeaderToPage('Content-Disposition', "attachment; filename={$FileName}{$type}");
				}
			}
		}

	}

