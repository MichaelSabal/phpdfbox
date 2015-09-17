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
require_once('../cos/COSDictionary.php');
/**
 * This is the interface that will be used to apply filters to a byte stream.
 *
 * @author <a href="mailto:ben@benlitchfield.com">Ben Litchfield</a>
 * @version $Revision: 1.7 $
 */
interface Filter {
    /**
     * This will decode some compressed data.
     *
     * @param compressedData The compressed byte stream.	InputStream
     * @param result The place to write the uncompressed byte stream.	OutputStream
     * @param options The options to use to encode the data.	COSDictionary
     * @param filterIndex The index to the filter being decoded.	int
     *
     * @throws IOException If there is an error decompressing the stream.
     */
    public function decode($compressedData,$result, $options, $filterIndex );
    /**
     * This will encode some data.
     *
     * @param rawData The raw data to encode.	InputStream
     * @param result The place to write to encoded results to.	OutputStream
     * @param options The options to use to encode the data.	COSDictionary
     * @param filterIndex The index to the filter being encoded.	int
     *
     * @throws IOException If there is an error compressing the stream.
     */
    public function encode($rawData, $result, $options, $filterIndex );
}
?>