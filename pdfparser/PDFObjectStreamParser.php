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
require_once('InputStreamSource.php');
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
    /**
     * Constructor.
     *
     * @param stream The stream to parse.
     * @param document The document for the current parsing.
     * @throws IOException If there is an error initializing the stream.
     */
	public function __construct($stream, $document) {
		if (!($stream instanceof COSStream) || !($document instanceof COSObject)) return;
        super(new InputStreamSource($stream->getUnfilteredStream()));
        $this->stream = $stream;
        $this->document = $document;		
	}
    /**
     * This will parse the tokens in the stream.  This will close the
     * stream when it is finished parsing.
     *
     * @throws IOException If there is an error while parsing the stream.
     */
	public function parse() {
		try {
            //need to first parse the header.
            $numberOfObjects = stream.getInt( "N" );
            $objectNumbers = array_pad( numberOfObjects );
            $this->streamObjects = array_pad( numberOfObjects );
            for( $i=0; $i<$numberOfObjects; $i++ ) {
                $objectNumber = $this->readObjectNumber();
                // skip offset
                $this->readLong();
                $objectNumbers[$i]=$objectNumber;
            }
            $objectCounter = 0;
            while( !is_null($this->cosObject = $this->parseDirObject()) ) {
                $object = new COSObject($this->cosObject);
                $object->setGenerationNumber(0);
                if ($objectCounter >= count($objectNumbers)) {
                    print('/ObjStm (object stream) has more objects than /N ' + $numberOfObjects);
                    break;
                }
                $object->setObjectNumber( $objectNumbers[$objectCounter] );
                $this->streamObjects[$objectCounter] = $object;
                // According to the spec objects within an object stream shall not be enclosed 
                // by obj/endobj tags, but there are some pdfs in the wild using those tags 
                // skip endobject marker if present
                if (!$this->seqSource->isEOF() && $this->seqSource->peek() == 'e') {
                    $this->readLine();
                }
                $objectCounter++;
            }
			$this->seqSource->close();
		} catch (Exception $e) {
			$this->seqSource->close();
		} // finally is not supported in PHP until version 5.5.
	}
    /**
     * This will get the objects that were parsed from the stream.
     *
     * @return All of the objects in the stream.
     */
    public function getObjects()
    {
        return $this->streamObjects;
    }
}
?>