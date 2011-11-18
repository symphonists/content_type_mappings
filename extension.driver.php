<?php

	Final Class extension_Content_Type_Mappings extends Extension{

		const SETTINGS_GROUP = 'content-type-mappings';

		const EXT_NAME = 'Content Type Mappings';

		/** Extension **/

		public function about(){
			return array('name' => self::EXT_NAME,
						 'version' => '1.4',
						 'release-date' => '2011-11-17',
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
							'2.2.5' => true,
							'2.2.4' => true,
							'2.2.3' => true,
							'2.2.2' => true,
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
					),
					array(
						'page' => '/backend/',
						'delegate' => 'InitaliseAdminPageHead',
						'callback' => 'initaliseAdminPageHead'
					)
				);
		}

		public function initaliseAdminPageHead($context)
		{
			$page = $context['parent']->Page;
			if($page instanceof contentSystemPreferences)
			{
				$page->addScriptToHead(URL . '/extensions/content_type_mappings/assets/content_type_mappings.preferences.js', 401, false);
			}
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
			$out_wrapper->setAttribute('class', 'subsection');

			// create a wrapper
			$wrapper = new XMLElement('ol');
			$wrapper->setAttribute('id', 'content-type-mappings-duplicator');
			$wrapper->setAttribute('class', 'orderable duplicator collapsible');

			// template
			$wrapper->appendChild($this->generateRow('New Content Mapping','template'));

			// data
			foreach($mappings as $type => $content_type){
				$values = array('mime-type'=>$content_type,'page-type'=>$type);
				$wrapper->appendChild($this->generateRow($values['page-type'], 'instance expanded', $values));
			}

			$out_wrapper->appendChild($wrapper);

			// wrapper into fieldset
			$fieldset->appendChild($out_wrapper);

			// adds the field set to the wrapper
			$context['wrapper']->appendChild($fieldset);
		}


		/**
		 * Quick utility function that creates a duplicator row
		 *
		 * @param string $header
		 * @param string $class @optional
		 * @param array $values @optional
		 */
		public function generateRow($header, $class = '', $values = array()) {

			// create the label and the input field
			$wrap = new XMLElement('li');
			$wrap->setAttribute('class', $class);

			$h4 = new XMLElement('h4');
			$h4->setAttribute('class', 'header');
			$h4->setValue(__($header));

			// value group
			$group = new XMLElement('div', null, array('class' => 'group'));

			// page type
			$page_type = $this->generateLabelInput($group, 'Page Type', 'page-type', $values['page-type']);
			// mime type
			$mime_type = $this->generateLabelInput($group, 'Mime Type', 'mime-type', $values['mime-type']);

			// append header and content
			$wrap->appendChild($h4);
			$wrap->appendChild($group);

			return $wrap;
		}

		private function generateLabelInput(&$wrap, $title, $name, $value=null) {

			$type = Widget::Label();

			$type->appendChild(new XMLElement('span',__($title)));
			$type->appendChild(Widget::Input('settings[' . self::SETTINGS_GROUP . '][mappings][]['.$name.']', $value));

			$wrap->appendChild($type);

		}


		/**
		 * Delegate handle that is called prior to saving the settings
		 * @param array $context
		 */
		public function save(&$context){
			$s = $context['settings'][self::SETTINGS_GROUP]['mappings'];

			// if it's an array
			if ( is_array($s) ) {

				// flush all the group
				Symphony::Configuration()->remove(self::SETTINGS_GROUP);

				// create a pointer to the prev element
				$last_page_type = null;

				// recreate them
				// iterate all values and assemble them
				foreach ($s as $setting) {

					if(isset($setting['page-type']) && !empty($setting['page-type'])) {
						$last_page_type = $setting['page-type'];
					}

					if(isset($setting['mime-type']) && !empty($setting['mime-type'])) {
						Symphony::Configuration()->set($last_page_type, $setting['mime-type'], self::SETTINGS_GROUP);
						$last_page_type = null;
					}
				}

				// save the changes
				Administration::instance()->saveConfig();

				// unset from the context
				unset($context['settings'][self::SETTINGS_GROUP]['mappings']);
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

