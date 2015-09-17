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
require_once('../filter/Filter.php');
require_once('../filter/FilterFactory.php');
require_once('../io/IOUtils.php');
require_once('../io/ScratchFile.php');
require_once('../io/RandomAccessBuffer.php');
//import org.apache.pdfbox.io.RandomAccess;
//import org.apache.pdfbox.io.RandomAccessBuffer;
//import org.apache.pdfbox.io.RandomAccessInputStream;
//import org.apache.pdfbox.io.RandomAccessOutputStream;

/**
 * This class represents a stream object in a PDF document.
 *
 * @author Ben Litchfield
 */
class COSStream extends COSDictionary {
    private $randomAccess;      // backing store, in-memory or on-disk
    private $scratchFile; 		// used as a temp buffer during decoding
    private $isWriting=false;              // true if there's an open OutputStream
    
    /**
     * Creates a new stream with an empty dictionary.
     * @param scratchFile Scratch file for writing stream data.
     */
    public function __construct($scratchFile=null) {
		if (is_null($scratchFile)) {
			$this->randomAccess = new RandomAccessBuffer();
			$this->scratchFile = null;
		} else {
			$this->randomAccess = createRandomAccess($scratchFile);
			$this->scratchFile = $scratchFile;
		}
    }
    /**
     * Creates a buffer for writing stream data, either in-memory or on-disk.
     */
    private function createRandomAccess($scratchFile) {
        if (!is_null($scratchFile)) {
            try {
                return $scratchFile->createBuffer();
            } catch (Exception $e) {
                // user can't recover from this exception anyway
                throw new Exception($e);
            }
        } else {
            return new RandomAccessBuffer();
        }
    }
    /**
     * Throws if the random access backing store has been closed. Helpful for catching cases where
     * a user tries to use a COSStream which has outlived its COSDocument.
     */
    private function checkClosed() {
        if ($this->randomAccess->isClosed()) {
            throw new Exception("COSStream has been closed and cannot be read. ".
                                  "Perhaps its enclosing PDDocument has been closed?");
        }
    }
    /**
     * Returns a new InputStream which reads the decoded stream data.
     * 
     * @return InputStream containing decoded stream data.
     * @throws IOException If the stream could not be read.
     */
    public function createRawInputStream() {
        $this->checkClosed();
        if ($this->isWriting) {
            throw new Exception("Cannot read while there is an open stream writer");
        }
        return new RandomAccessInputStream($this->randomAccess);
    }

    /**
     * Returns a new InputStream which reads the encoded PDF stream data. Experts only!
     * 
     * @return InputStream containing raw, encoded PDF stream data.
     * @throws IOException If the stream could not be read.
     */
    public function createInputStream() {
        $this->checkClosed();
        if ($this->isWriting) {
            throw new Exception("Cannot read while there is an open stream writer");
        }
        $input = new RandomAccessInputStream($this->randomAccess);
        return COSInputStream::create($this->getFilterList(), $this, $this->input, $this->scratchFile);
    }
    /**
     * Returns a new OutputStream for writing stream data, using and the given filters.
     * 
     * @param filters COSArray or COSName of filters to be used.
     * @return OutputStream for un-encoded stream data.
     * @throws IOException If the output stream could not be created.
     */
    public function createOutputStream($filters=null) {
        $this->checkClosed();
        if ($this->isWriting) {
            throw new Exception("Cannot have more than one open stream writer.");
        }
        // apply filters, if any
        if (!is_null($filters)) {
            $this->setItem(COSName::FILTER, $filters);
        }
        $this->randomAccess = $this->createRandomAccess($this->scratchFile); // discards old data
        $randomOut = new RandomAccessOutputStream($this->randomAccess);
        $cosOut = new COSOutputStream($this->getFilterList(), $this, $randomOut, $this->scratchFile);
        $this->isWriting = true;
        return new FilterOutputStream($cosOut);
    }
    /**
     * Returns a new OutputStream for writing encoded PDF data. Experts only!
     * 
     * @return OutputStream for raw PDF stream data.
     * @throws IOException If the output stream could not be created.
     */
    public function createRawOutputStream() {
        $this->checkClosed();
        if ($this->isWriting)
        {
            throw new Exception("Cannot have more than one open stream writer.");
        }
        $this->randomAccess = $this->createRandomAccess($this->scratchFile); // discards old data
        $out = new RandomAccessOutputStream($this->randomAccess);
        $this->isWriting = true;
        return new FilterOutputStream($out);
    }
    /**
     * Returns the list of filters.
     */
    private function getFilterList() {
        $filterList = array();
        $filters = $this->getFilters();
        if ($filters instanceof COSName) {
			$ff = FilterFactory::INSTANCE;
            $filterList[] = ($ff->getFilter($filters));
        } elseif (is_array($filters)) {
            $filterArray = $filters;
            for ($i = 0; $i < count($filterArray); $i++) {
                $filterName = $filterArray[$i];
				$ff = FilterFactory::INSTANCE;
                $filterList[] = ($ff->getFilter($filterName));
            }
        }
        return $filterList;
    }
    /**
     * Returns the length of the encoded stream.
     *
     * @return length in bytes
     */
    public function getLength() {
        if ($this->isWriting) {
            throw new Exception("There is an open OutputStream associated with ".
                                            "this COSStream. It must be closed before querying".
                                            "length of this COSStream.");
        }
        return $this->getInt(COSName::LENGTH, 0);
    }

    /**
     * This will return the filters to apply to the byte stream.
     * The method will return
     * - null if no filters are to be applied
     * - a COSName if one filter is to be applied
     * - a COSArray containing COSNames if multiple filters are to be applied
     *
     * @return the COSBase object representing the filters
     */
    public function getFilters() {
        return $this->getDictionaryObject(COSName::FILTER);
    }
    /**
     * Returns the contents of the stream as a PDF "text string".
     */
    public function toTextString() {
        $out = new ByteArrayOutputStream();
        $input = null;
        try
        {
            $input = $this->createInputStream();
            IOUtils::copy($input, $out);
        }
        catch (Exception $e)
        {
            return "";
        }
        IOUtils::closeQuietly($input);
        $string = $out->toByteArray();
        return $string;
    }
    public function accept($visitor) {
		if (!($visitor instanceof ICOSVisitor)) return null;
        return $visitor->visitFromStream($this);
    }
    public function close() {
        // marks the scratch file pages as free
        $this->randomAccess->close();
    }
}
?>