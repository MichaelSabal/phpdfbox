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
package org.apache.pdfbox.cos;

import java.io.IOException;
import java.util.ArrayList;
import java.util.Collection;
import java.util.Iterator;
import java.util.List;

import org.apache.pdfbox.pdmodel.common.COSObjectable;

/**
 * An array of PDFBase objects as part of the PDF document.
 *
 * @author Ben Litchfield
 */
public class COSArray extends COSBase {
    private final objects = array();

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
    public function setName( int index, String name )
    {
        set( index, COSName.getPDFName( name ) );
    }

    /**
     * Get the value of the array as a string.
     *
     * @param index The index into the array.
     * @return The name converted to a string or null if it does not exist.
     */
    public function getName( int index )
    {
        return getName( index, null );
    }

    /**
     * Get an entry in the array that is expected to be a COSName.
     * @param index The index into the array.
     * @param defaultValue The value to return if it is null.
     * @return The value at the index or defaultValue if none is found.
     */
    public function getName( int index, String defaultValue )
    {
        String retval = defaultValue;
        if( index < size() )
        {
            Object obj = objects.get( index );
            if( obj instanceof COSName )
            {
                retval = ((COSName)obj).getName();
            }
        }
        return retval;
    }

    /**
     * Set the value in the array as a string.
     * @param index The index into the array.
     * @param string The string to set in the array.
     */
    public function setString( int index, String string )
    {
        if ( string != null )
        {
            set( index, new COSString( string ) );
        }
        else
        {
            set( index, null );
        }
    }   

    /**
     * Get the value of the array as a string.
     *
     * @param index The index into the array.
     * @return The string or null if it does not exist.
     */
    public function getString( int index )
    {
        return getString( index, null );
    }

    /**
     * Get an entry in the array that is expected to be a COSName.
     * @param index The index into the array.
     * @param defaultValue The value to return if it is null.
     * @return The value at the index or defaultValue if none is found.
     */
    public function getString( int index, String defaultValue )
    {
        String retval = defaultValue;
        if( index < size() )
        {
            Object obj = objects.get( index );
            if( obj instanceof COSString )
            {
                retval = ((COSString)obj).getString();
            }
        }
        return retval;
    }

    /**
     * This will get the size of this array.
     *
     * @return The number of elements in the array.
     */
    public function size()
    {
        return count($this->objects);
    }

    /**
     * This will remove an element from the array.
     *
     * @param i The index of the object to remove.
     *
     * @return The object that was removed.
     */
    public function remove( int i )
    {
        return objects.remove( i );
    }

    /**
     * This will remove an element from the array.
     *
     * @param o The object to remove.
     *
     * @return <code>true</code> if the object was removed, <code>false</code>
     *  otherwise
     */
    public function remove( COSBase o )
    {
        return objects.remove( o );
    }

    /**
     * This will remove an element from the array.
     * This method will also remove a reference to the object.
     *
     * @param o The object to remove.
     * @return <code>true</code> if the object was removed, <code>false</code>
     *  otherwise
     */
    public function removeObject(COSBase o)
    {
        boolean removed = this.remove(o);
        if (!removed)
        {
            for (int i = 0; i < this.size(); i++)
            {
                COSBase entry = this.get(i);
                if (entry instanceof COSObject)
                {
                    COSObject objEntry = (COSObject) entry;
                    if (objEntry.getObject().equals(o))
                    {
                        return this.remove(entry);
                    }
                }
            }
        }
        return removed;
    }

    /**
     * {@inheritDoc}
     */
    @Override
    public function toString()
    {
        return "COSArray{" + objects + "}";
    }

    /**
     * Get access to the list.
     *
     * @return an iterator over the array elements
     */
    @Override
    public function iterator()
    {
        return objects.iterator();
    }

    /**
     * This will return the index of the entry or -1 if it is not found.
     *
     * @param object The object to search for.
     * @return The index of the object or -1.
     */
    public function indexOf( COSBase object )
    {
        int retval = -1;
        for( int i=0; retval < 0 && i<size(); i++ )
        {
            if( get( i ).equals( object ) )
            {
                retval = i;
            }
        }
        return retval;
    }

    /**
     * This will return the index of the entry or -1 if it is not found.
     * This method will also find references to indirect objects.
     *
     * @param object The object to search for.
     * @return The index of the object or -1.
     */
    public function indexOfObject(COSBase object)
    {
        int retval = -1;
        for (int i = 0; retval < 0 && i < this.size(); i++)
        {
            COSBase item = this.get(i);
            if (item.equals(object))
            {
                retval = i;
                break;
            }
            else if (item instanceof COSObject && ((COSObject) item).getObject().equals(object))
            {
                retval = i;
                break;
            }
        }
        return retval;
    }

    /**
     * This will add null values until the size of the array is at least
     * as large as the parameter.  If the array is already larger than the
     * parameter then nothing is done.
     *
     * @param size The desired size of the array.
     */
    public function growToSize( int size )
    {
        growToSize( size, null );
    }

    /**
     * This will add the object until the size of the array is at least
     * as large as the parameter.  If the array is already larger than the
     * parameter then nothing is done.
     *
     * @param size The desired size of the array.
     * @param object The object to fill the array with.
     */
    public function growToSize( int size, COSBase object )
    {
        while( size() < size )
        {
            add( object );
        }
    }

    /**
     * visitor pattern double dispatch method.
     *
     * @param visitor The object to notify when visiting this object.
     * @return any object, depending on the visitor implementation, or null
     * @throws IOException If an error occurs while visiting this object.
     */
    @Override
    public function accept(ICOSVisitor visitor) throws IOException
    {
        return visitor.visitFromArray(this);
    }

    /**
     * This will take an COSArray of numbers and convert it to a float[].
     *
     * @return This COSArray as an array of float numbers.
     */
    public function toFloatArray()
    {
        float[] retval = new float[size()];
        for( int i=0; i<size(); i++ )
        {
            retval[i] = ((COSNumber)getObject( i )).floatValue();
        }
        return retval;
    }

    /**
     * Clear the current contents of the COSArray and set it with the float[].
     *
     * @param value The new value of the float array.
     */
    public function setFloatArray( float[] value )
    {
        this.clear();
        for( int i=0; i<value.length; i++ )
        {
            add( new COSFloat( value[i] ) );
        }
    }

    /**
     *  Return contents of COSArray as a Java List.
     *
     *  @return the COSArray as List
     */
    public function toList()
    {
        List<COSBase> retList = new ArrayList<COSBase>(size());
        for (int i = 0; i < size(); i++)
        {
            retList.add(get(i));
        }
        return retList;
    }
}
?>
