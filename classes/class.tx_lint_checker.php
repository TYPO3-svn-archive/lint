<?php 

class tx_lint_checker {
	
	
	/**
	 * @var SimpleXMLElement
	 */
	protected $tsrefXml;
	
	
	
	/**
	 * Get tsref xml object
	 * 
	 * @return SimpleXMLElement
	 */
	public function getTsrefXml() {
		if (!($this->tsrefXml instanceof SimpleXMLElement)) {
			$this->tsrefXml = simplexml_load_file(PATH_typo3.'sysext/t3editor/res/tsref/tsref.xml');
		}
		return $this->tsrefXml;
	}
	
	
	public function check($ts, $type) {
		if (!empty($ts)) {
			if (!is_array($ts)) {
				throw new tx_lint_exception('Configuration is not an array');
			}
			$allowedProperties = $this->getPropertiesFromXml($type);
			foreach ($ts as $property => $propertyValue) {
				$property = $this->getPropertyWithoutDot($property);
				if ($property == 'value') {
					continue;
				}
				if ($this->is_numerical($property)) {
					continue;
				}
				if (!array_key_exists($property, $allowedProperties)) {
					throw new tx_lint_exception(sprintf('"%s" is not a valid property for type "%s"', $property, $type));
				}
				$propertyType = $allowedProperties[$property];
				if ($propertyType == 'boolean' && !$this->is_boolean($propertyValue)) {
					throw new tx_lint_exception(sprintf('"%s" for property "%s" is not a boolean value', $propertyValue, $property));
				}
				/*
				if ($this->isSimpleType($propertyType) && !empty($ts[$property.'.'])) {
					throw new tx_lint_exception(sprintf('Property "%s" (type: "%s") must not have subconfiguration', $property, $propertyType));
				}
				*/
			}
		}
	}
	
	public function isSimpleType($type) {
		return in_array($type, array('boolean', 'string'));
	}
	
	public function getPropertyWithoutDot($property) {
		if (substr($property, -1) == '.') {
			$property = substr($property, 0, -1);
		}
		return $property;
	}
	
	public function checkArray($ts) {
		if (!is_null($ts)) {
			if (!is_array($ts)) {
				throw new tx_lint_exception('Not an array');
			}
			foreach (array_keys($ts) as $key) {
				$key = $this->getPropertyWithoutDot($key);
				if (!$this->is_numerical($key)) {
					throw new tx_lint_exception(sprintf('"%s" is not a numerical key', $key));
				}
			}
		}
	}
	
	public function is_boolean($val) {
		return strcmp($val, '0') == 0 || strcmp($val, '1') == 0; 
	}

	public function is_numerical($val) {
		$str = strval($val);
		return ctype_digit($str) && (strlen($str) == 1 || $str[0] != '0') && (intval($val) >= 1);
	}
	
	/**
	 * Get properties from xml
	 * 
	 * @param string $id
	 * @return array
	 */
	public function getPropertiesFromXml($id) {
		
		if (empty($id) || !is_string($id)) {
			throw new tx_lint_exception('Invalid id');
		}
		
		$result = $this->getTsrefXml()->xpath('//type[@id="'.$id.'"]/property');
		
		if (!$result) {
			throw new tx_lint_exception(sprintf('No properties found for id "%s"', $id));
		}
		
		$properties = array();
		foreach ($result as $propertyXml) { /* @var $propertyXml SimpleXMLElement */
			$attributes = $propertyXml->attributes();
			$properties[(string)$attributes['name']] = (string)$attributes['type']; 
		}
		return $properties; 
	}
	
}

?>