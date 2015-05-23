<?php

Class extension_Content_Type_Mappings extends Extension {

    const SETTINGS_GROUP = 'content-type-mappings';

    public function getSubscribedDelegates()
    {
        return array(
                array(
                    'page' => '/frontend/',
                    'delegate' => 'FrontendPreRenderHeaders',
                    'callback' => 'setContentType'
                ),
                array(
                    'page' => '/system/preferences/',
                    'delegate' => 'AddCustomPreferenceFieldsets',
                    'callback' => 'addCustomPreferenceFieldsets'
                ),
                array(
                    'page' => '/system/preferences/',
                    'delegate' => 'Save',
                    'callback' => 'save'
                ),
                array(
                    'page' => '/backend/',
                    'delegate' => 'AdminPagePreGenerate',
                    'callback' => '__appendAssets'
                )
            );
    }

    public function __appendAssets($context)
    {
        $callback = Symphony::Engine()->getPageCallback();
        if ($callback['driver'] == 'systempreferences') {
            Administration::instance()->Page->addScriptToHead(URL . '/extensions/content_type_mappings/assets/content_type_mappings.preferences.js', 401, false);
        }
    }

    /**
     * Delegate handle that adds Custom Preference Fieldsets
     *
     * @param string $page
     * @param array $context
     */
    public function addCustomPreferenceFieldsets($context)
    {
        $mappings = Symphony::Configuration()->get();
        $mappings = $mappings[self::SETTINGS_GROUP];

        // Creates the field set
        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Content Type Mappings')));

        // Create a paragraph for short intructions
        $p = new XMLElement('p', __('Content Types defined here are usable in the Pages Editor.'), array('class' => 'help'));

        // Append intro paragraph
        $fieldset->appendChild($p);

        // Outter wrapper
        $out_wrapper = new XMLElement('div', null, array(
            'class' => 'frame',
            'id' => 'ctm-duplicator'
        ));

        // Create a wrapper
        $wrapper = new XMLElement('ol');

        // Template
        $wrapper->appendChild($this->generateRow('New Content Mapping','template'));

        // Data
        if (is_array($mappings)) {
            foreach ($mappings as $type => $content_type) {
                //if type is export this is not a type but an export url param so skip to next element
                if ($type == 'export') continue;

                $values = array('mime-type'=>$content_type,'page-type'=>$type);
                $wrapper->appendChild($this->generateRow($values['page-type'], 'instance expanded', $values));
            }
        }

        $out_wrapper->appendChild($wrapper);

        // Wrapper into fieldset
        $fieldset->appendChild($out_wrapper);


        // Add fieldset
        $input = Widget::Input('settings[content-type-mappings][export]',Symphony::Configuration()->get('export', self::SETTINGS_GROUP));
        $label = Widget::Label(__('Export URL parameter'), $input);
        $fieldset->appendChild($label);

        // Adds the field set to the wrapper
        $context['wrapper']->appendChild($fieldset);
    }

    /**
     * Quick utility function that creates a duplicator row
     *
     * @param string $title
     * @param string $class @optional
     * @param array $values @optional
     */
    public function generateRow($title, $class = '', $values = array())
    {
        // Create the label and the input field
        $wrapper = new XMLElement('li');
        $wrapper->setAttribute('class', $class);

        // HEader
        $header = new XMLElement('header');
        $header->setValue(__($title));

        // Content
        $content = new XMLElement('div', null, array('class' => 'content'));
        $columns = new XMLElement('div', null, array('class' => 'two columns'));
        $content->appendChild($columns);

        // Page type column
        $page_type = $this->generateLabelInput($columns, 'Page Type', 'page-type', $values['page-type']);

        // Mime type column
        $mime_type = $this->generateLabelInput($columns, 'Mime Type', 'mime-type', $values['mime-type']);

        // Append header and content
        $wrapper->appendChild($header);
        $wrapper->appendChild($columns);

        return $wrapper;
    }

    private function generateLabelInput(&$wrap, $title, $name, $value=null)
    {
        $type = Widget::Label();
        $type->setAttribute('class', 'column');
        $type->appendChild(new XMLElement('span',__($title)));
        $type->appendChild(Widget::Input('settings[content-type-mappings][mappings][]['.$name.']', $value));
        $wrap->appendChild($type);
    }

    /**
     * Delegate handle that is called prior to saving the settings
     * @param array $context
     */
    public function save(&$context)
    {
        $s = $context['settings'][self::SETTINGS_GROUP]['mappings'];

        // If it's an array
        if ( is_array($s) ) {

            // Flush all the group
            Symphony::Configuration()->remove(self::SETTINGS_GROUP);

            // Create a pointer to the prev element
            $last_page_type = null;

            // Recreate them, iterate all values and assemble them
            foreach ($s as $setting) {

                if (isset($setting['page-type']) && !empty($setting['page-type'])) {
                    $last_page_type = $setting['page-type'];
                }

                if (isset($setting['mime-type']) && !empty($setting['mime-type'])) {
                    Symphony::Configuration()->set($last_page_type, $setting['mime-type'], self::SETTINGS_GROUP);
                    $last_page_type = null;
                }
            }

            Symphony::Configuration()->set('export', $context['settings'][self::SETTINGS_GROUP]['export'], self::SETTINGS_GROUP);

            // Save the changes
            Symphony::Configuration()->write();

            // Unset from the context
            unset($context['settings'][self::SETTINGS_GROUP]['mappings']);
        }
    }

/*-------------------------------------------------------------------------
	Installation:
-------------------------------------------------------------------------*/

    public function install()
    {
        $initial_mappings = array(
            'xml' => 'text/xml; charset=utf-8',
            'text' => 'text/plain; charset=utf-8',
            'css' => 'text/css; charset=utf-8',
            'json' => 'application/json; charset=utf-8'
        );

        foreach ($initial_mappings as $type => $content_type) {
            Symphony::Configuration()->set($type, $content_type, self::SETTINGS_GROUP);
        }

        Symphony::Configuration()->set('export', 'export', self::SETTINGS_GROUP);

        Symphony::Configuration()->write();
    }

    public function update($previousVersion = null)
    {
        if(version_compare($previousVersion, '1.7', '<')) {
            Symphony::Configuration()->set('export', 'export', self::SETTINGS_GROUP);
            Symphony::Configuration()->write();
        }
    }

    public function uninstall()
    {
        Symphony::Configuration()->remove(self::SETTINGS_GROUP);
        Symphony::Configuration()->write();
    }

/*-------------------------------------------------------------------------
	Utilities:
-------------------------------------------------------------------------*/

    public function resolveType($type)
    {
        // Fix issue #2, for downloadables files
        if ($type{0} == '.') {
            return Symphony::Configuration()->get(strtolower(substr($type, 1)), self::SETTINGS_GROUP);
        } else {
            return Symphony::Configuration()->get(strtolower($type), self::SETTINGS_GROUP);
        }
    }

    public function setContentType(array $context=NULL)
    {
        $page_data = Frontend::Page()->pageData();
        $params = Frontend::Page()->Params();

        if (!isset($page_data['type']) || !is_array($page_data['type']) || empty($page_data['type'])) {
            return;
        }

        foreach ($page_data['type'] as $type) {
            $exportParam = $params['url-' . Symphony::Configuration()->get('export', self::SETTINGS_GROUP)];

            //if starts with and has url-param export and export matches page data type
            if (strrpos($type, 'export', -strlen($type)) !== FALSE && isset($exportParam) && $exportParam == substr($type, -strlen($exportParam)) ) {
                $type = str_replace('export', '', $type);
            }

            $content_type = $this->resolveType($type);

            if (!is_null($content_type)) {
                Frontend::Page()->addHeaderToPage('Content-Type', $content_type);
            }

            if ($type{0} == '.') {
                $page_params = Frontend::Page()->Params();
                $filename = trim(str_replace('/', '.', $page_params['current-path']), '.');
                Frontend::Page()->addHeaderToPage('Content-Disposition', "attachment; filename={$filename}{$type}");
            }
        }
    }

}
