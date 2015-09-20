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
require_once('../cos/COSName.php');

/**
 * Factory for Filter classes.
 *
 * @author Ben Litchfield
 */
class FilterFactory {
	private $filters = array();
    /**
     * Singleton instance.
     */
    public static function Instance() {
        static $inst = null;
        if ($inst === null) {
            $inst = new FilterFactory();
        }
        return $inst;
    }

    private function __construct() {
        $flate = new FlateFilter();
        $dct = new DCTFilter();
        $ccittFax = new CCITTFaxFilter();
        $lzw = new LZWFilter();
        $asciiHex = new ASCIIHexFilter();
        $ascii85 = new ASCII85Filter();
        $runLength = new RunLengthDecodeFilter();
        $crypt = new CryptFilter();
        $jpx = new JPXFilter();
        $jbig2 = new JBIG2Filter();

        $this->filters[COSName::FLATE_DECODE]=flate;
        $this->filters[COSName::FLATE_DECODE_ABBREVIATION]=flate;
        $this->filters[COSName::DCT_DECODE]=dct;
        $this->filters[COSName::DCT_DECODE_ABBREVIATION]=dct;
        $this->filters[COSName::CCITTFAX_DECODE]=ccittFax;
        $this->filters[COSName::CCITTFAX_DECODE_ABBREVIATION]=ccittFax;
        $this->filters[COSName::LZW_DECODE]=lzw;
        $this->filters[COSName::LZW_DECODE_ABBREVIATION]=lzw;
        $this->filters[COSName::ASCII_HEX_DECODE]=asciiHex;
        $this->filters[COSName::ASCII_HEX_DECODE_ABBREVIATION]=asciiHex;
        $this->filters[COSName::ASCII85_DECODE]=ascii85;
        $this->filters[COSName::ASCII85_DECODE_ABBREVIATION]=ascii85;
        $this->filters[COSName::RUN_LENGTH_DECODE]=runLength;
        $this->filters[COSName::RUN_LENGTH_DECODE_ABBREVIATION]=runLength;
        $this->filters[COSName::CRYPT]=crypt;
        $this->filters[COSName::JPX_DECODE]=jpx;
        $this->filters[COSName::JBIG2_DECODE]=jbig2;
    }

    /**
     * Returns a filter instance given its COSName.
     * @param filterName the name of the filter to retrieve
     * @return the filter that matches the name
     * @throws IOException if the filter name was invalid
     */
    public function getFilter($filterName) {
		if (is_string($filterName)) $filterName = COSName::getPDFName($filterName);
        $filter = $this->filters[$filterName];
        if (is_null($filter)) {
            throw new Exception("Invalid filter: $filterName");
        }
        return $filter;
    }

    // returns all available filters, for testing
    function getAllFilters() {
        return array_values($this->filters);
    }
}
?>