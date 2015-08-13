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
require_once('BaseParser.php');
require_once('../cos/COSBase.php');
require_once('../cos/COSDocument.php');
require_once('../cos/COSObject.php');
require_once('../cos/COSStream.php');
/**
 * This will parse a PDF 1.5 object stream and extract all of the objects from the stream.
 *
 * @author Ben Litchfield
 * 
 */
class PDFObjectStreamParser extends BaseParser {
	private $streamObjects = array();	// List<COSObject>
	private $stream;					// COSStream
	
}
?>