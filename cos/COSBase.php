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
 * The base object that all objects in the PDF document will extend.
 *
 * @author Ben Litchfield
 */
abstract class COSBase {
  
    private $needToBeUpdate;	// boolean
    private $direct;			// boolean
  
   /**
     * Constructor.
     */
    public function __construct() {
		$needToBeUpdate = false;
    }

    /**
     * This will get the filter manager to use to filter streams.
     *
     * @return The filter manager.
     */
    public function getFilterManager() {
        /**
         * @todo move this to PDFdocument or something better
         */
        return new FilterManager();
    }

    /**
     * Convert this standard java object to a COS object.
     *
     * @return The cos object that matches this Java object.
     */
    public function getCOSObject() {
        return $this;
    }

    /**
     * visitor pattern double dispatch method.
     *
     * @param visitor The object to notify when visiting this object.
     * @return any object, depending on the visitor implementation, or null
     * @throws COSVisitorException If an error occurs while visiting this object.
     */
    public abstract function accept($visitor);
    
    public function setNeedToBeUpdate($flag) {
		if (!is_boolean($flag)) return;
		$this->needToBeUpdate = $flag;
    }
    
    /**
     * If the state is set true, the dictionary will be written direct into the called object. 
     * This means, no indirect object will be created.
     * 
     * @return the state
     */
    public function isDirect() {
        return $this->direct;
    }
    
    /**
     * Set the state true, if the dictionary should be written as a direct object and not indirect.
     * 
     * @param direct set it true, for writting direct object
     */
    public function setDirect($direct) {
		if (!is_boolean($direct)) return;
		$this->direct = $direct;
    }
    
    public function isNeedToBeUpdate() 
    {
		return $this->needToBeUpdate;
    }
}
?>