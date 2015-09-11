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
require_once('../pdmodel/common/COSObjectable.php');
require_once('../util/DateConverter.php');	// May just use base PHP functionality.

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
    protected $items = new array();	// Note: Key order is important here, and must be preserved.
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
        $items = new array();
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
    public void setInt($key, $value) {
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
     * This is a convenience method that will get the dictionary object that is expected to be a name and convert it to
     * a string. Null is returned if the entry does not exist in the dictionary.
     *
     * @param embedded The embedded dictionary to get.
     * @param key The key to the item in the dictionary.
     * @return The name converted to a string.
     * @throws IOException If there is an error converting to a date.
     */
    public Calendar getEmbeddedDate(String embedded, String key) throws IOException
    {
        return getEmbeddedDate(embedded, COSName.getPDFName(key), null);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be a name and convert it to
     * a string. Null is returned if the entry does not exist in the dictionary.
     *
     * @param embedded The embedded dictionary to get.
     * @param key The key to the item in the dictionary.
     * @return The name converted to a string.
     *
     * @throws IOException If there is an error converting to a date.
     */
    public Calendar getEmbeddedDate(String embedded, COSName key) throws IOException
    {
        return getEmbeddedDate(embedded, key, null);
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
    public Calendar getEmbeddedDate(String embedded, String key, Calendar defaultValue)
            throws IOException
    {
        return getEmbeddedDate(embedded, COSName.getPDFName(key), defaultValue);
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
    public Calendar getEmbeddedDate(String embedded, COSName key, Calendar defaultValue)
            throws IOException
    {
        Calendar retval = defaultValue;
        COSDictionary eDic = (COSDictionary) getDictionaryObject(embedded);
        if (eDic != null)
        {
            retval = eDic.getDate(key, defaultValue);
        }
        return retval;
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be a cos boolean and convert
     * it to a primitive boolean.
     *
     * @param key The key to the item in the dictionary.
     * @param defaultValue The value returned if the entry is null.
     *
     * @return The value converted to a boolean.
     */
    public boolean getBoolean(String key, boolean defaultValue)
    {
        return getBoolean(COSName.getPDFName(key), defaultValue);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be a COSBoolean and convert
     * it to a primitive boolean.
     *
     * @param key The key to the item in the dictionary.
     * @param defaultValue The value returned if the entry is null.
     *
     * @return The entry converted to a boolean.
     */
    public boolean getBoolean(COSName key, boolean defaultValue)
    {
        return getBoolean(key, null, defaultValue);
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
    public boolean getBoolean(COSName firstKey, COSName secondKey, boolean defaultValue)
    {
        boolean retval = defaultValue;
        COSBase bool = getDictionaryObject(firstKey, secondKey);
        if (bool instanceof COSBoolean)
        {
            retval = ((COSBoolean) bool).getValue();
        }
        return retval;
    }

    /**
     * Get an integer from an embedded dictionary. Useful for 1-1 mappings. default:-1
     *
     * @param embeddedDictionary The name of the embedded dictionary.
     * @param key The key in the embedded dictionary.
     *
     * @return The value of the embedded integer.
     */
    public int getEmbeddedInt(String embeddedDictionary, String key)
    {
        return getEmbeddedInt(embeddedDictionary, COSName.getPDFName(key));
    }

    /**
     * Get an integer from an embedded dictionary. Useful for 1-1 mappings. default:-1
     *
     * @param embeddedDictionary The name of the embedded dictionary.
     * @param key The key in the embedded dictionary.
     *
     * @return The value of the embedded integer.
     */
    public int getEmbeddedInt(String embeddedDictionary, COSName key)
    {
        return getEmbeddedInt(embeddedDictionary, key, -1);
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
    public int getEmbeddedInt(String embeddedDictionary, String key, int defaultValue)
    {
        return getEmbeddedInt(embeddedDictionary, COSName.getPDFName(key), defaultValue);
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
    public int getEmbeddedInt(String embeddedDictionary, COSName key, int defaultValue)
    {
        int retval = defaultValue;
        COSDictionary embedded = (COSDictionary) getDictionaryObject(embeddedDictionary);
        if (embedded != null)
        {
            retval = embedded.getInt(key, defaultValue);
        }
        return retval;
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an int. -1 is returned if
     * there is no value.
     *
     * @param key The key to the item in the dictionary.
     * @return The integer value.
     */
    public int getInt(String key)
    {
        return getInt(COSName.getPDFName(key), -1);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an int. -1 is returned if
     * there is no value.
     *
     * @param key The key to the item in the dictionary.
     * @return The integer value..
     */
    public int getInt(COSName key)
    {
        return getInt(key, -1);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an integer. If the
     * dictionary value is null then the default Value will be returned.
     *
     * @param keyList The key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The integer value.
     */
    public int getInt(String[] keyList, int defaultValue)
    {
        int retval = defaultValue;
        COSBase obj = getDictionaryObject(keyList);
        if (obj instanceof COSNumber)
        {
            retval = ((COSNumber) obj).intValue();
        }
        return retval;
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an integer. If the
     * dictionary value is null then the default Value will be returned.
     *
     * @param key The key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The integer value.
     */
    public int getInt(String key, int defaultValue)
    {
        return getInt(COSName.getPDFName(key), defaultValue);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an integer. If the
     * dictionary value is null then the default Value will be returned.
     *
     * @param key The key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The integer value.
     */
    public int getInt(COSName key, int defaultValue)
    {
        return getInt(key, null, defaultValue);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an integer. If the
     * dictionary value is null then the default Value -1 will be returned.
     *
     * @param firstKey The first key to the item in the dictionary.
     * @param secondKey The second key to the item in the dictionary.
     * @return The integer value.
     */
    public int getInt(COSName firstKey, COSName secondKey)
    {
        return getInt(firstKey, secondKey, -1);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an integer. If the
     * dictionary value is null then the default Value will be returned.
     *
     * @param firstKey The first key to the item in the dictionary.
     * @param secondKey The second key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The integer value.
     */
    public int getInt(COSName firstKey, COSName secondKey, int defaultValue)
    {
        int retval = defaultValue;
        COSBase obj = getDictionaryObject(firstKey, secondKey);
        if (obj instanceof COSNumber)
        {
            retval = ((COSNumber) obj).intValue();
        }
        return retval;
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an long. -1 is returned
     * if there is no value.
     *
     * @param key The key to the item in the dictionary.
     *
     * @return The long value.
     */
    public long getLong(String key)
    {
        return getLong(COSName.getPDFName(key), -1L);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an long. -1 is returned
     * if there is no value.
     *
     * @param key The key to the item in the dictionary.
     * @return The long value.
     */
    public long getLong(COSName key)
    {
        return getLong(key, -1L);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an long. If the
     * dictionary value is null then the default Value will be returned.
     *
     * @param keyList The key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The long value.
     */
    public long getLong(String[] keyList, long defaultValue)
    {
        long retval = defaultValue;
        COSBase obj = getDictionaryObject(keyList);
        if (obj instanceof COSNumber)
        {
            retval = ((COSNumber) obj).longValue();
        }
        return retval;
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an integer. If the
     * dictionary value is null then the default Value will be returned.
     *
     * @param key The key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The integer value.
     */
    public long getLong(String key, long defaultValue)
    {
        return getLong(COSName.getPDFName(key), defaultValue);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an integer. If the
     * dictionary value is null then the default Value will be returned.
     *
     * @param key The key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The integer value.
     */
    public long getLong(COSName key, long defaultValue)
    {
        long retval = defaultValue;
        COSBase obj = getDictionaryObject(key);
        if (obj instanceof COSNumber)
        {
            retval = ((COSNumber) obj).longValue();
        }
        return retval;
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an float. -1 is returned
     * if there is no value.
     *
     * @param key The key to the item in the dictionary.
     * @return The float value.
     */
    public float getFloat(String key)
    {
        return getFloat(COSName.getPDFName(key), -1);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an float. -1 is returned
     * if there is no value.
     *
     * @param key The key to the item in the dictionary.
     * @return The float value.
     */
    public float getFloat(COSName key)
    {
        return getFloat(key, -1);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be a float. If the
     * dictionary value is null then the default Value will be returned.
     *
     * @param key The key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The float value.
     */
    public float getFloat(String key, float defaultValue)
    {
        return getFloat(COSName.getPDFName(key), defaultValue);
    }

    /**
     * This is a convenience method that will get the dictionary object that is expected to be an float. If the
     * dictionary value is null then the default Value will be returned.
     *
     * @param key The key to the item in the dictionary.
     * @param defaultValue The value to return if the dictionary item is null.
     * @return The float value.
     */
    public float getFloat(COSName key, float defaultValue)
    {
        float retval = defaultValue;
        COSBase obj = getDictionaryObject(key);
        if (obj instanceof COSNumber)
        {
            retval = ((COSNumber) obj).floatValue();
        }
        return retval;
    }

    /**
     * Gets the boolean value from the flags at the given bit position.
     *
     * @param field The COSName of the field to get the flag from.
     * @param bitFlag the bitPosition to get the value from.
     *
     * @return true if the number at bitPos is '1'
     */
    public boolean getFlag(COSName field, int bitFlag)
    {
        int ff = getInt(field, 0);
        return (ff & bitFlag) == bitFlag;
    }

    /**
     * This will remove an item for the dictionary. This will do nothing of the object does not exist.
     *
     * @param key The key to the item to remove from the dictionary.
     */
    public void removeItem(COSName key)
    {
        items.remove(key);
    }

    /**
     * This will do a lookup into the dictionary.
     *
     * @param key The key to the object.
     *
     * @return The item that matches the key.
     */
    public COSBase getItem(COSName key)
    {
        return items.get(key);
    }

    /**
     * This will do a lookup into the dictionary.
     * 
     * @param key The key to the object.
     *
     * @return The item that matches the key.
     */
    public COSBase getItem(String key)
    {
        return getItem(COSName.getPDFName(key));
    }

    /**
     * Returns the names of the entries in this dictionary. The returned set is in the order the entries were added to
     * the dictionary.
     *
     * @since Apache PDFBox 1.1.0
     * @return names of the entries in this dictionary
     */
    public Set<COSName> keySet()
    {
        return items.keySet();
    }

    /**
     * Returns the name-value entries in this dictionary. The returned set is in the order the entries were added to the
     * dictionary.
     *
     * @since Apache PDFBox 1.1.0
     * @return name-value entries in this dictionary
     */
    public Set<Map.Entry<COSName, COSBase>> entrySet()
    {
        return items.entrySet();
    }

    /**
     * This will get all of the values for the dictionary.
     *
     * @return All the values for the dictionary.
     */
    public Collection<COSBase> getValues()
    {
        return items.values();
    }

    /**
     * visitor pattern double dispatch method.
     *
     * @param visitor The object to notify when visiting this object.
     * @return The object that the visitor returns.
     *
     * @throws IOException If there is an error visiting this object.
     */
    @Override
    public Object accept(ICOSVisitor visitor) throws IOException
    {
        return visitor.visitFromDictionary(this);
    }
    
    @Override
    public boolean isNeedToBeUpdated() 
    {
      return needToBeUpdated;
    }
    
    @Override
    public void setNeedToBeUpdated(boolean flag) 
    {
      needToBeUpdated = flag;
    }

    /**
     * This will add all of the dictionarys keys/values to this dictionary. Only called when adding keys to a trailer
     * that already exists.
     *
     * @param dic The dic to get the keys from.
     */
    public void addAll(COSDictionary dic)
    {
        for (Map.Entry<COSName, COSBase> entry : dic.entrySet())
        {
            /*
             * If we're at a second trailer, we have a linearized pdf file, meaning that the first Size entry represents
             * all of the objects so we don't need to grab the second.
             */
            if (!entry.getKey().getName().equals("Size")
                    || !items.containsKey(COSName.getPDFName("Size")))
            {
                setItem(entry.getKey(), entry.getValue());
            }
        }
    }

    /**
     * @see java.util.Map#containsKey(Object)
     *
     * @param name The key to find in the map.
     * @return true if the map contains this key.
     */
    public boolean containsKey(COSName name)
    {
        return this.items.containsKey(name);
    }

    /**
     * @see java.util.Map#containsKey(Object)
     *
     * @param name The key to find in the map.
     * @return true if the map contains this key.
     */
    public boolean containsKey(String name)
    {
        return containsKey(COSName.getPDFName(name));
    }

    /**
     * This will add all of the dictionarys keys/values to this dictionary, but only if they don't already exist. If a
     * key already exists in this dictionary then nothing is changed.
     *
     * @param dic The dic to get the keys from.
     */
    public void mergeInto(COSDictionary dic)
    {
        for (Map.Entry<COSName, COSBase> entry : dic.entrySet())
        {
            if (getItem(entry.getKey()) == null)
            {
                setItem(entry.getKey(), entry.getValue());
            }
        }
    }

    /**
     * Nice method, gives you every object you want Arrays works properly too. Try "P/Annots/[k]/Rect" where k means the
     * index of the Annotsarray.
     *
     * @param objPath the relative path to the object.
     * @return the object
     */
    public COSBase getObjectFromPath(String objPath)
    {
        COSBase retval = null;
        String[] path = objPath.split(PATH_SEPARATOR);
        retval = this;
        for (String pathString : path)
        {
            if (retval instanceof COSArray)
            {
                int idx = Integer.parseInt(pathString.replaceAll("\\[", "").replaceAll("\\]", ""));
                retval = ((COSArray) retval).getObject(idx);
            }
            else if (retval instanceof COSDictionary)
            {
                retval = ((COSDictionary) retval).getDictionaryObject(pathString);
            }
        }
        return retval;
    }

    /**
     * Returns an unmodifiable view of this dictionary.
     * 
     * @return an unmodifiable view of this dictionary
     */
    public COSDictionary asUnmodifiableDictionary()
    {
        return new UnmodifiableCOSDictionary(this);
    }

    /**
     * {@inheritDoc}
     */
    @Override
    public String toString()
    {
        StringBuilder retVal = new StringBuilder("COSDictionary{");
        for (COSName key : items.keySet())
        {
            retVal.append("(");
            retVal.append(key);
            retVal.append(":");
            if (getDictionaryObject(key) != null)
            {
                retVal.append(getDictionaryObject(key).toString());
            }
            else
            {
                retVal.append("<null>");
            }
            retVal.append(") ");
        }
        retVal.append("}");
        return retVal.toString();
    }

}
?>