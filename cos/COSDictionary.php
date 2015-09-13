<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * This class represents a dictionary where name/value pairs reside.
 *
 * @author Ben Litchfield
 * 
 */
class COSDictionary extends COSBase {
    const PATH_SEPARATOR = "/";
    private $needToBeUpdated;	// boolean

    /**
     * The name-value pairs of this dictionary. The pairs are kept in the order they were added to the dictionary.
     */
    protected $items = array();	// Note: Key order is important here, and must be preserved.
    /**
     * Copy Constructor. This will make a shallow copy of this dictionary.
     *
     * @param dict The dictionary to copy.
     */
    public function __construct($dict=null) {
		if (is_null($dict)) return;
		if (!($dict instanceof COSDictionary)) return;
        $this->items = $dict->items;
    }
    /**
     * @see java.util.Map#containsValue(java.lang.Object)
     *
     * @param value The value to find in the map.
     *
     * @return true if the map contains this value.
     */
    public function containsValue($value) {
        $contains = in_array($value,$items);
        if (!$contains && ($value instanceof COSObject)) {
            $contains = in_array($value->getObject(),$items);
        }
        return $contains;
    }
    /**
     * Search in the map for the value that matches the parameter and return the first key that maps to that value.
     *
     * @param value The value to search for in the map.
     * @return The key for the value in the map or null if it does not exist.
     */
    public function getKeyForValue($value) {
        foreach ($items as $nextKey=>$nextValue) {
            if ($nextValue==$value
                    || ($nextValue instanceof COSObject && ($nextValue->getObject()==($value)))) {
                return $nextKey;
            }
        }
        return null;
    }
    /**
     * This will return the number of elements in this dictionary.
     *
     * @return The number of elements in the dictionary.
     */
    public function size() {
        return count($items);
    }
    /**
     * This will clear all items in the map.
     */
    public function clear() {
        $items = array();
    }
    /**
     * This will get an object from this dictionary. If the object is a reference then it will dereference it and get it
     * from the document. If the object is COSNull then null will be returned.
     *
     * @param key The key, or list of keys, to the object that we are getting.
     * @param secondKey The second key to try.
     *
     * @return The object that matches the key.
     */
    public function getDictionaryObject($key, $secondKey=null) {
		if (is_string($key)) $key = COSName::getPDFName($key);
		if (is_array($key)) {
			$arr = $key;
			$key = null;
			foreach($arr as $name) {
				if (isset($items[COSName::getPDFName($name)])) {
					$key = $COSName::getPDFName($name);
					break;
				}
			}
		}
		if (is_null($key)) return null;
		if (!isset($items[$key]) && !is_null($secondKey)) $key=$secondKey;
        if (!isset($items[$key])) return null;
        if ($items[$key] instanceof COSObject) {
            return $items[$key]->getObject();
        } else {
			return $items[$key];
		}
	}
    /**
     * This will set an item in the dictionary. If value is null then the result will be the same as removeItem( key ).
     *
     * @param key The key to the dictionary object.
     * @param value The value to the dictionary object.
     */
    public function setItem($key, $value) {
        if (is_null($value)) {
            $this->removeItem($key);
        } else {
			if ($value instanceof COSObjectable) $value=$value->getCOSObject();
			if (is_string($key)) $key=COSName::getPDFName($key);
			if ($key instanceof COSName)
				$items[$key] = $value;
        }
    }
    /**
     * This will set an item in the dictionary.
     *
     * @param key The key to the dictionary object.
     * @param value The value to the dictionary object.
     */
    public function setBoolean($key, $value) {
        if (is_bool($value)) $this->setItem($key,$value);
    }
    /**
     * This is a convenience method that will convert the value to a COSName object. If it is null then the object will
     * be removed.
     *
     * @param key The key to the object,
     * @param value The string value for the name.
     */
    public function setName($key, $value) {
		if (!is_null($value) && is_string($value)) $value = COSName::getPDFName($value);
		$this->setItem($key,$value);
    }
    /**
     * Set the value of a date entry in the dictionary.
     *
     * @param key The key to the date value.
     * @param date The date value.
     */
    public function setDate($key, $date)  {
		if (is_integer($date)) $date = new DateTime("@$date");
		if (is_string($date)) $this->setString($key, $date);
		elseif ($date instanceof DateTime) {
			$tz=$date->format('P');
			if (substr($tz,1)=='00:00') $tz='Z';
			elseif (strlen($tz)==6) {
				$tz[3]="'";
				$tz.="'";
			}
			$this->setString($key, $date->format('\D:YmdHis'.$tz));
		}
    }
    /**
     * Set the date object.
     *
     * @param embedded The embedded dictionary.
     * @param key The key to the date.
     * @param date The date to set.
     */
    public function setEmbeddedDate($embedded, $key, $date) {
		if (!is_string($embedded)) return;
        $dic = $this->getDictionaryObject($embedded);
        if (is_null($dic) && !is_null($date)) {
            $dic = new COSDictionary();
            $this->setItem($embedded, $dic);
        }
        if (!is_null($dic)) {
            $dic->setDate($key, $date);
        }
    }
    /**
     * This is a convenience method that will convert the value to a COSString object. If it is null then the object
     * will be removed.
     *
     * @param key The key to the object,
     * @param value The string value for the name.
     */
    public function setString($key, $value) {
        $this->setItem($key,$value);
    }
    /**
     * This is a convenience method that will convert the value to a COSString object. If it is null then the object
     * will be removed.
     *
     * @param embedded The embedded dictionary to set the item in.
     * @param key The key to the object,
     * @param value The string value for the name.
     */
    public function setEmbeddedString($embedded, $key, $value) {
		if (!is_string($embedded)) return;
        $dic = $this->getDictionaryObject($embedded);
        if (is_null($dic) && !is_null($date)) {
            $dic = new COSDictionary();
            $this->setItem($embedded, $dic);
        }
        if (!is_null($dic)) {
            $dic->setString($key, $date);
        }
    }
    /**
     * This is a convenience method that will convert the value to a COSInteger object.
     *
     * @param key The key to the object,
     * @param value The int value for the name.
     */
    public function setInt($key, $value) {
        $this->setItem($key,$value);
    }
    /**
     * This is a convenience method that will convert the value to a COSInteger object.
     *
     * @param key The key to the object,
     * @param value The int value for the name.
     */
    public function setLong($key, $value) {
        $this->setItem($key,$value);
    }
    /**
     * This is a convenience method that will convert the value to a COSInteger object.
     *
     * @param embeddedDictionary The embedded dictionary.
     * @param key The key to the object,
     * @param value The int value for the name.
     */
    public function setEmbeddedInt($embeddedDictionary, $key, $value) {
		if (!is_string($embedded)) return;
        $dic = $this->getDictionaryObject($embedded);
        if (is_null($dic) && !is_null($date)) {
            $dic = new COSDictionary();
            $this->setItem($embedded, $dic);
        }
        if (!is_null($dic)) {
            $dic->setInt($key, $date);
        }
    }
    /**
     * This is a convenience method that will convert the value to a COSFloat object.
     *
     * @param key The key to the object,
     * @param value The int value for the name.
     */
    public function setFloat($key, $value) {
        $this->setItem($key, $value);
    }
    /**
     * Sets the given boolean value at bitPos in the flags.
     *
     * @param field The COSName of the field to set the value into.
     * @param bitFlag the bit position to set the value in.
     * @param value the value the bit position should have.
     */
    public function setFlag($field, $bitFlag, $value) {
		if (!($field instanceof COSName)) return;
		if (!is_integer($bitFlag)) return;
		if (!is_bool($value)) return;
        $currentFlags = $this->getInt($field, 0);
        if ($value) {
            $currentFlags = $currentFlags | $bitFlag;
        } else {
            $currentFlags &= ~$bitFlag;
        }
        $this->setInt($field, $currentFlags);
    }
    /**
     * This is a convenience method that will get the dictionary object that is expected to be a name. Default is
     * returned if the entry does not exist in the dictionary.
     *
     * @param key The key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The COS name.
     */
    public function getCOSName($key, $defaultValue=null) {
        $name = $this->getDictionaryObject($key);
        if ($name instanceof COSName) {
            return $name;
        }
        return defaultValue;
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be a name and convert it to
     * a string. Null is returned if the entry does not exist in the dictionary.
     *
     * @param key The key to the item in the dictionary.
     * @return The name converted to a string.
     */
    public function getNameAsString($key,$defaultValue=null) {
		if (is_string($key)) $key = COSName::getPDFName($key);
        $retval = null;
        $name = $this->getDictionaryObject($key);
        if ($name instanceof COSName) {
            $retval = $name->getName();
		} elseif (is_string($name)) {
			$retval = $name;
        } elseif ($name instanceof COSString) {
            $retval = $name->getString();
        } 
		if (is_null($retval)) return $defaultValue;
        return $retval;
    }
    /**
     * This is a convenience method that will get the dictionary object that is expected to be a name and convert it to
     * a string. Null is returned if the entry does not exist in the dictionary.
     *
     * @param key The key to the item in the dictionary.
     * @return The name converted to a string.
     */
	public function getString($key,$defaultValue=null) {
		if (is_string($key)) $key = COSName::getPDFName($key);
        $retval = null;
        $value = $this->getDictionaryObject($key);
		if (is_string($value)) $retval = $value;
		elseif ($value instanceof COSString) {
            $retval = $value->getString();
        } 
		if (is_null($retval)) return $defaultValue;
        return $retval;
	}
    /**
     * This is a convenience method that will get the dictionary object that is expected to be a name and convert it to
     * a string. Null is returned if the entry does not exist in the dictionary.
     *
     * @param embedded The embedded dictionary.
     * @param key The key to the item in the dictionary.
     * @param defaultValue The default value to return.
     * @return The name converted to a string.
     */
    public function getEmbeddedString($embedded, $key, $defaultValue=null) {
        $retval = $defaultValue;
        $dic = $this->getDictionaryObject($embedded);
        if ($dic != null) {
            $retval = $dic->getString($key, $defaultValue);
        }
        return $retval;
    }
    /**
     * This is a convenience method that will get the dictionary object that is expected to be a name and convert it to
     * a string. Null is returned if the entry does not exist in the dictionary or if the date was invalid.
     *
     * @param key The key to the item in the dictionary.
     * @return The name converted to a date.
     */
    public function getDate($key, $defaultValue=null) {
 		if (is_string($key)) $key = COSName::getPDFName($key);
        $date = $this->getDictionaryObject($key);
		if (is_null($date)) return $defaultValue;
		if (strlen($date)>=17 && substr($date,0,3)=='\D:') {
			$y = substr($date,3,4);
			$m = substr($date,7,2);
			$d = substr($date,9,2);
			$h = substr($date,11,2);
			$n = substr($date,13,2);
			$s = substr($date,15,2);
			$tz = substr($date,17);
			if ($tz=='Z') $tz = '00:00';
			if (strlen($tz)==7) {
				$tz = substr($tz,0,6);
				$tz[3] = ':';
			}
			$dt = new DateTime($y,$m,$d,$h,$n,$s,$tz);
			return $dt;
		} else return $date;        
    }
    /**
     * This is a convenience method that will get the dictionary object that is expected to be a date. Null is returned
     * if the entry does not exist in the dictionary.
     *
     * @param embedded The embedded dictionary to get.
     * @param key The key to the item in the dictionary.
     * @param defaultValue The default value to return.
     * @return The name converted to a string.
     * @throws IOException If there is an error converting to a date.
     */
    public function getEmbeddedDate($embedded, $key, $defaultValue=null) {
 		if (is_string($key)) $key = COSName::getPDFName($key);
        $retval = $defaultValue;
        $eDic = $this->getDictionaryObject($embedded);
        if (!is_null($eDic)) {
            $retval = $eDic->getDate($key, $defaultValue);
        }
        return $retval;
    }
    /**
     * This is a convenience method that will get the dictionary object that is expected to be a COSBoolean and convert
     * it to a primitive boolean.
     *
     * @param firstKey The first key to the item in the dictionary.
     * @param secondKey The second key to the item in the dictionary.
     * @param defaultValue The value returned if the entry is null.
     *
     * @return The entry converted to a boolean.
     */
    public function getBoolean($firstKey, $secondKey=null, $defaultValue=null) {
 		if (is_string($firstKey)) $firstKey = COSName::getPDFName($firstKey);
 		if (is_string($secondKey)) $secondKey = COSName::getPDFName($secondKey);
        $retval = $defaultValue;
        $bool = $this->getDictionaryObject($firstKey, $secondKey);
        if (is_bool($bool)) {
            $retval = $bool;
        }
        return retval;
    }
    /**
     * Get an integer from an embedded dictionary. Useful for 1-1 mappings.
     *
     * @param embeddedDictionary The name of the embedded dictionary.
     * @param key The key in the embedded dictionary.
     * @param defaultValue The value if there is no embedded dictionary or it does not contain the key.
     *
     * @return The value of the embedded integer.
     */
    public function getEmbeddedInt($embeddedDictionary, $key, $defaultValue=0) {
 		if (is_string($key)) $key = COSName::getPDFName($key);
        $retval = $defaultValue;
        $embedded = $this->getDictionaryObject($embeddedDictionary);
        if (!is_null($embedded)) {
            $retval = $embedded->getInt($key, $defaultValue);
        }
        return $retval;
    }
    /**
     * This is a convenience method that will get the dictionary object that is expected to be an integer. If the
     * dictionary value is null then the default Value will be returned.
     *
     * @param firstKey The first key (or keylist) to the item in the dictionary.
     * @param secondKey The second key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The integer value.
     */
    public function getInt($firstKey, $secondKey=null, $defaultValue=0) {
 		if (is_string($firstKey)) $firstKey = COSName::getPDFName($firstKey);
 		if (is_string($secondKey)) $secondKey = COSName::getPDFName($secondKey);
        $retval = $defaultValue;
		if (is_array($firstKey)) $obj = $this->getDictionaryObject($firstKey);
        else $obj = $this->getDictionaryObject($firstKey, $secondKey);
        if (is_numeric($obj)) {
            $retval = floor($obj);
        }
        return $retval;
    }
	public function getLong($firstKey, $secondKey=null, $defaultValue=0) {
		return getInt($firstKey,$secondKey,$defaultValue);
	}
    /**
     * This is a convenience method that will get the dictionary object that is expected to be an float. If the
     * dictionary value is null then the default Value will be returned.
     *
     * @param key The key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The float value.
     */
    public function getFloat($key, $defaultValue) {
 		if (is_string($key)) $key = COSName::getPDFName($key);
        $retval = $defaultValue;
        $obj = $this->getDictionaryObject($key);
        if (is_numeric($obj)) {
            $retval = 0.00 + $obj;
        }
        return $retval;
    }

    /**
     * Gets the boolean value from the flags at the given bit position.
     *
     * @param field The COSName of the field to get the flag from.
     * @param bitFlag the bitPosition to get the value from.
     *
     * @return true if the number at bitPos is '1'
     */
    public function getFlag($field, $bitFlag) {
		if (!is_integer($bitFlag)) return false;
        $ff = $this->getInt($field, 0);
        return ($ff & $bitFlag) == $bitFlag;
    }

    /**
     * This will remove an item for the dictionary. This will do nothing of the object does not exist.
     *
     * @param key The key to the item to remove from the dictionary.
     */
    public function removeItem($key) {
		if (isset($this->items[$key])) unset($this->items[$key]);
    }

    /**
     * This will do a lookup into the dictionary.
     *
     * @param key The key to the object.
     *
     * @return The item that matches the key.
     */
    public function getItem($key) {
 		if (is_string($key)) $key = COSName::getPDFName($key);
		if (isset($this->items[$key]))
			return $this->items[$key];
		else
			return null;
    }
    /**
     * Returns the names of the entries in this dictionary. The returned set is in the order the entries were added to
     * the dictionary.
     *
     * @since Apache PDFBox 1.1.0
     * @return names of the entries in this dictionary
     */
    public function keySet() {
        return array_keys($this->items);
    }

    /**
     * Returns the name-value entries in this dictionary. The returned set is in the order the entries were added to the
     * dictionary.
     *
     * @since Apache PDFBox 1.1.0
     * @return name-value entries in this dictionary
     */
    public function entrySet()
    {
        return $this->items;
    }

    /**
     * This will get all of the values for the dictionary.
     *
     * @return All the values for the dictionary.
     */
    public function getValues()
    {
        return array_values($this->items);
    }

    /**
     * visitor pattern double dispatch method.
     *
     * @param visitor The object to notify when visiting this object.
     * @return The object that the visitor returns.
     *
     * @throws IOException If there is an error visiting this object.
     */
    public function accept($visitor) {
		if (!($visitor instanceof ICOSVisitor)) return null;
        return $visitor->visitFromDictionary($this);
    }
    public function isNeedToBeUpdated() 
    {
      return $this->needToBeUpdated;
    }
    public function setNeedToBeUpdated($flag) {
		if (is_bool($flag))
			$this->needToBeUpdated = $flag;
    }

    /**
     * This will add all of the dictionarys keys/values to this dictionary. Only called when adding keys to a trailer
     * that already exists.
     *
     * @param dic The dic to get the keys from.
     */
    public function addAll($dic) {
		$map = $dic->entrySet();
		array_merge($this->items, $map);
    }
	public function mergeInto($dic) {
		$this->addAll($dic);
	}
    /**
     * @see java.util.Map#containsKey(Object)
     *
     * @param name The key to find in the map.
     * @return true if the map contains this key.
     */
    public function containsKey($name)
    {
  		if (is_string($name)) $name = COSName::getPDFName($name);
		return isset($this->items[$name]);
    }
    /**
     * Nice method, gives you every object you want Arrays works properly too. Try "P/Annots/[k]/Rect" where k means the
     * index of the Annotsarray.
     *
     * @param objPath the relative path to the object.
     * @return the object
     */
    public function getObjectFromPath($objPath) {
		if (!is_string($objPath)) return null;
        $retval = null;
        $path = explode(PATH_SEPARATOR,$objPath);
        $retval = $this;
        foreach ($path as $pathString) {
            if (is_array($retval)) {
				$ps = str_replace("\\[","",str_replace("\\]","",$pathString));
                $idx = 0+$ps;
                $retval = $retval[$idx];
            }
            elseif ($retval instanceof COSDictionary) {
                $retval = $retval->getDictionaryObject($pathString);
            }
        }
        return $retval;
    }

    /**
     * Returns an unmodifiable view of this dictionary.
     * 
     * @return an unmodifiable view of this dictionary
     */
    public function asUnmodifiableDictionary()
    {
        return new UnmodifiableCOSDictionary($this);
    }

    /**
     * {@inheritDoc}
     */
    public function toString()
    {
        $retVal = "COSDictionary{";
        foreach ($this->items as $key=>$value) {
            $retVal.="($key:$value) ";
        }
        $retVal.="}";
        return $retVal;
    }
}
?>