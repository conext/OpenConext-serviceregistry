<?php
/**
 * SURFconext Service Registry
 *
 * LICENSE
 *
 * Copyright 2011 SURFnet bv, The Netherlands
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the License.
 *
 * @category  SURFconext Service Registry
 * @package
 * @copyright Copyright © 2010-2011 SURFnet SURFnet bv, The Netherlands (http://www.surfnet.nl)
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */
 
class Metadata_Validator {

   /**
     * @var sspmod_serviceregistry_EntityController
     */
    protected $_entityController;

    protected $_errors;
    protected $_warnings;
    protected $_validations;

    protected static $_MISSING_REQUIRED_FIELD = 'Field is required';
    protected static $_DEFAULT_VALUE_NOT_ALLOWED = 'The default value is not allowed';
    protected static $_VALIDATION_OK = 'Ok';

    public function __construct($entityController)
    {
        $this->_entityController = $entityController;
        $this->_errors = array();
        $this->_warnings = array();
        $this->_validations = array();
    }

    public function validate()
    {
        $entityType = $this->_entityController->getEntity()->getType();

        if ($entityType == 'saml20-idp') {
            $idpMetadataConfig = $this->_loadExpandedMetadataConfig(SimpleSAML_Configuration::getConfig('module_janus.php')->getArray('metadatafields.saml20-idp'));
            $this->_validate($idpMetadataConfig);
        } else if ($entityType == 'saml20-sp') {
            $spMetadataConfig = $this->_loadExpandedMetadataConfig(SimpleSAML_Configuration::getConfig('module_janus.php')->getArray('metadatafields.saml20-sp'));
            $this->_validate($spMetadataConfig);
        } else {
            $_errors[] = 'Unknown Entity Type';
        }
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function getWarnings()
    {
        return $this->_warnings;
    }

    public function getValidations()
    {
        return $this->_validations;
    }

    protected function _validate($metadataConfig)
    {
        $entityMetadata = $this->_loadImplodedEntityMetadata($this->_entityController->getMetaArray());

        foreach ($metadataConfig as $k => $v) {
            // Required field
            $errors = array();
            $warnings = array();
            // Missing required field
            if (!array_key_exists($k, $entityMetadata) && $this->_isRequired($v)) {
                $errors[] = self::$_MISSING_REQUIRED_FIELD;

            } else if (array_key_exists($k, $entityMetadata)) {
                $this->_validateDefaultValue($entityMetadata, $k, $v, $errors, $warnings);
                $this->_validateCustomValidates($entityMetadata, $k, $v, $errors, $warnings);
            }
            // Do nothing is key is not present and field is not required

            $this->_setValidations($k, $errors, $warnings);
        }
    }

    protected function _loadExpandedMetadataConfig($metadataConfig)
    {
        $metadataInfo = array();
        foreach ($metadataConfig as $k => $v) {
            if (!empty($v['supported'])) {
                foreach ($v['supported'] as $supportedValue) {
                    $expandedKey = str_replace('#', $supportedValue, $k);
                    $expandedValue = $v;
                    unset($expandedValue['supported']);
                    $metadataInfo[$expandedKey] = $expandedValue;
                }
            }
        }

        return $metadataInfo;
    }

    protected function _loadImplodedEntityMetadata($entityMetadata)
    {
        $implodedEntityMetadata = array();
        $this->_implodeEntityMetadataValues($entityMetadata, '', $implodedEntityMetadata);

        return $implodedEntityMetadata;
    }

    protected function _implodeEntityMetadataValues($data, $implodedKey = '', &$implodedEntityMetadata)
    {

       if (is_array($data) && !empty($data)) {
          foreach ($data as $key => $value) {
            $this->_implodeEntityMetadataValues($value, $implodedKey. ':' .$key, $implodedEntityMetadata);
          }
       } else {
          $implodedEntityMetadata[substr($implodedKey, 1)] = $data;
       }
    }

    protected function _isRequired($metadata)
    {
        return (array_key_exists('required', $metadata) && $metadata['required'] === true);
    }

    protected function _setValidations($key, $errors, $warnings)
    {
        if (count($errors) > 0 || count($warnings) > 0) {
            $this->_validations[$key] = array('errors' => $errors, 'warnings' => $warnings);
        } else {
            $this->_validations[$key] = self::$_VALIDATION_OK;
        }
    }

    protected function _validateDefaultValue($entityMetadata, $k, $v, &$errors, &$warnings)
    {
        if (array_key_exists('default_allow', $v) && $v['default_allow'] === false) {
            if ($v['default'] == $entityMetadata[$k]) {
                $errors[] = self::$_DEFAULT_VALUE_NOT_ALLOWED;
            }
        }
    }

    protected function _validateCustomValidates($entityMetadata, $k, $v, &$errors, &$warnings)
    {
        $functions = array();
        include __DIR__ . '/../../../janus/lib/Validation/Metadata.php';

        if (array_key_exists('validate', $v) && array_key_exists($v['validate'], $functions)) {
            $validateFunction = $v['validate'];
            $value = $entityMetadata[$k];
            $valid = eval($functions[$validateFunction]['code']);

            if (!$valid) {
                $errors[] = $v['validate_error'];
            }
        }
    }
}
