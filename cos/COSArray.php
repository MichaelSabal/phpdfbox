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

/**
 * An array of PDFBase objects as part of the PDF document.
 *
 * @author Ben Litchfield
 */
class COSArray extends COSBase {
    private $objects = array();

    /**
     * Constructor.
     */
    public function __construct() {
        //default constructor
    }

    /**
     * This will add an object to the array.
     *
     * @param object The object to add to the array.
     */
    public function add( $object ) {
		if ($object instanceof COSObjectable) $this->objects[] = $object->getCOSObject();
		elseif ($object instanceof COSBase) $this->objects[] = $object;
    }
    /**
     * Add the specified object at the ith location and push the rest to the
     * right.
     *
     * @param i The index to add at.
     * @param object The object to add at that index.
     */
    public function insert( $i, $object) {
		if (!($object instanceof COSBase || is_array($object)) || !is_integer($i)) return;
        array_splice($this->objects, $i, 0, $object );
    }
    /**
     * This will remove all of the objects in the collection.
     */
    public function clear() {
        $this->objects = array();
    }
    /**
     * This will remove all of the objects in the collection.
     *
     * @param objectsList The list of objects to remove from the collection.
     */
    public function removeAll( $objectsList ) {
        $this->objects = array_diff($this->objects,$objectsList);
    }
    /**
     * This will retain all of the objects in the collection.
     *
     * @param objectsList The list of objects to retain from the collection.
     */
/*    public function retainAll( $objectsList ) {
        objects.retainAll( objectsList );
    }
*/
    /**
     * This will add an object to the array.
     *
     * @param objectsList The object to add to the array.
     */
    public function addAll( $objectsList ) {
        $this->objects = array_merge($this->objects,$objectsList);
    }
    /**
     * This will set an object at a specific index.
     *
     * @param index zero based index into array.
     * @param object The object to set.
     */
    public function set( $index, $object ) {
		if ($object instanceof COSObjectable) $object = $object->getCOSObject();
        $this->objects[$index] = $object;
    }
    /**
     * This will get an object from the array.  This will dereference the object.
     * If the object is COSNull then null will be returned.
     *
     * @param index The index into the array to get the object.
     *
     * @return The object at the requested index.
     */
    public function getObject( $index ) {
		if (!is_integer($index)) return null;
		if (!isset($this->objects[$index])) return null;
        $obj = $this->objects[$index];
        if( $obj instanceof COSObject ) {
            return $obj->getObject();
        }
        return $obj;
    }

    /**
     * This will get an object from the array.  This will NOT dereference
     * the COS object.
     *
     * @param index The index into the array to get the object.
     *
     * @return The object at the requested index.
     */
    public function get( $index ) {
		if (!is_integer($index)) return null;
		if (!isset($this->objects[$index])) return null;
        return $this->objects[$index];
    }
    /**
     * Get the value of the array as an integer, return the default if it does
     * not exist.
     *
     * @param index The value of the array.
     * @param defaultValue The value to return if the value is null.
     * @return The value at the index or the defaultValue.
     */
    public function getInt( $index, $defaultValue=-1 ) {
        $retval = $defaultValue;
		if (!is_integer($index)) return $retval;
        if ( $index < $this->size() ) {
			if (is_numeric($this->objects[$index])) $retval = floor($this->objects[$index]);
        }
        return $retval;
    }
    /**
     * Set the value in the array as an integer.
     *
     * @param index The index into the array.
     * @param value The value to set.
     */
    public function setInt( $index,$value ) {
        $this->set( $index, 0+$value );
    }

    /**
     * Set the value in the array as a name.
     * @param index The index into the array.
     * @param name The name to set in the array.
     */
    public function setName( $index, $name ) {
        $this->set( $index, COSName::getPDFName( $name ) );
    }
    /**
     * Get an entry in the array that is expected to be a COSName.
     * @param index The index into the array.
     * @param defaultValue The value to return if it is null.
     * @return The value at the index or defaultValue if none is found.
     */
    public function getName( $index, $defaultValue=null ) {
        $retval = $defaultValue;
        if( $index < $this->size() ) {
            $obj = $this->objects[$index];
            if( $obj instanceof COSName ) {
                $retval = $obj->getName();
            }
        }
        return $retval;
    }
    /**
     * Set the value in the array as a string.
     * @param index The index into the array.
     * @param string The string to set in the array.
     */
    public function setString( $index, $string ) {
		if (!is_integer($index) || !is_string($string)) return;
        if ( !is_null($string) ) {
            $this->set( $index, $string );
        } else {
            $this->set( $index, null );
        }
    }   
    /**
     * Get an entry in the array that is expected to be a COSName.
     * @param index The index into the array.
     * @param defaultValue The value to return if it is null.
     * @return The value at the index or defaultValue if none is found.
     */
    public function getString( $index, $defaultValue=null ) {
		if (!is_integer($index) || !isset($this->objects[$index])) return $defaultValue;
		if (!is_string($this->objects[$index])) return $defaultValue;
		return $this->objects[$index];
    }
    /**
     * This will get the size of this array.
     *
     * @return The number of elements in the array.
     */
    public function size() {
        return count($this->objects);
    }

    /**
     * This will remove an element from the array.
     *
     * @param i The index of the object to remove.
     * @param o The object to remove.
     *
     * @return The object that was removed, if an index was supplied.
     * @return <code>true</code> if the object was removed, <code>false</code>
     *  otherwise
     */
    public function remove( $what ) {
		if ($what instanceof COSBase) {
			$i = array_search($what,$this->objects);
			if ($i===false) return false;
			unset($this->objects[$i]);
			return true;
		} elseif (is_integer($what)) {
			// This could be either a key or a value.  We're going to assume a key if one exists, or object if it doesn't.
			if (isset($this->objects[$what])) {
				$obj = $this->objects[$what];
				unset($this->objects[$what]);
				return $obj;
			}
		}
		// The same logic as COSBase is duplicated here because of the uncertainty of the parameter.
		// It will be easier to make changes in the future based on the certainly level if they are split.
		$i = array_search($what,$this->objects);
		if ($i===false || is_null($i)) return false;
		unset($this->objects[$i]);
		return true;
    }
    /**
     * This will remove an element from the array.
     * This method will also remove a reference to the object.
     *
     * @param o The object to remove.
     * @return <code>true</code> if the object was removed, <code>false</code>
     *  otherwise
     */
    public function removeObject($o) {
		$i = array_search($o,$this->objects);
		if ($i===false || is_null($i)) $removed = false;
		else {
			unset($this->objects[$i]);
			$removed = true;
		}	
        if (!$removed) {
            for ($i = 0; $i < $this->size(); $i++) {
                $entry = $this->objects[$i];
                if ($entry instanceof COSObject) {
                    $objEntry = $entry;
                    if ($objEntry->getObject()==$o) {
                        unset ($this->objects[$i]);
						return true;
                    }
                }
            }
        }
        return $removed;
    }
    /**
     * {@inheritDoc}
     */
    public function toString() {
        return "COSArray{".var_export($this->objects,true)."}";
    }
    /**
     * This will return the index of the entry or -1 if it is not found.
     *
     * @param object The object to search for.
     * @return The index of the object or -1.
     */
    public function indexOf( $object ) {
		$result = array_search($object,$this->objects);
		if (is_null($result) || $result===false) return -1;
		else return $result;
	}
    /**
     * This will return the index of the entry or -1 if it is not found.
     * This method will also find references to indirect objects.
     *
     * @param object The object to search for.
     * @return The index of the object or -1.
     */
    public function indexOfObject($object) {
        $retval = -1;
        for ($i = 0; $retval < 0 && $i < $this->size(); $i++) {
            $item = $this->objects[$i];
            if ($item==$object) {
                $retval = $i;
                break;
            } elseif ($item instanceof COSObject && $item->getObject()==$object) {
                $retval = $i;
                break;
            }
        }
        return $retval;
    }
    /**
     * This will add the object until the size of the array is at least
     * as large as the parameter.  If the array is already larger than the
     * parameter then nothing is done.
     *
     * @param size The desired size of the array.
     * @param object The object to fill the array with.
     */
    public function growToSize( $size, $object=null ) {
		array_pad($this->objects,$size,$object);
    }
    /**
     * visitor pattern double dispatch method.
     *
     * @param visitor The object to notify when visiting this object.
     * @return any object, depending on the visitor implementation, or null
     * @throws IOException If an error occurs while visiting this object.
     */
    public function accept($visitor) {
		if (!($visitor instanceof ICOSVisitor)) return null;
        return $visitor->visitFromArray($this);
    }
    /**
     * This will take an COSArray of numbers and convert it to a float[].
     *
     * @return This COSArray as an array of float numbers.
     */
    public function toFloatArray() {
        $retval = array();
		array_pad($retval,count($this->objects),0.00);
        for( $i=0; $i<$this->size(); $i++ ) {
            $retval[$i] = 0.00+$this->getObject($i);
        }
        return $retval;
    }
    /**
     * Clear the current contents of the COSArray and set it with the float[].
     *
     * @param value The new value of the float array.
     */
    public function setFloatArray( $value ) {
		if (is_array($value)) $this->objects = $value;
    }
}
?>
