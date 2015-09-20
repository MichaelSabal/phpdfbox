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

/* $Id: IOUtils.java 1666014 2015-03-11 21:08:39Z tilman $ */


/**
 * This class contains various I/O-related methods.
 */
class IOUtils {

    //TODO PDFBox should really use Apache Commons IO.

    private function __construct() {
        //Utility class. Don't instantiate.
    }

    /**
     * Reads the input stream and returns its contents as a byte array.
     * @param in the input stream to read from.
     * @return the byte array
     * @throws IOException if an I/O error occurs
     */
    public function toByteArray($in) {
        $baout = new ByteArrayOutputStream();
        $this->copy($in, $baout);
        return $baout->toByteArray();
    }

    /**
     * Copies all the contents from the given input stream to the given output stream.
     * @param input the input stream
     * @param output the output stream
     * @return the number of bytes that have been copied
     * @throws IOException if an I/O error occurs
     */
    public function copy($input, $output) {
        $buffer = array_pad(array(),4096,0);
        $count = 0;
        $n = 0;
        while (-1 != ($n = fread($input,$buffer))) {
            fwrite($output, $buffer, 0, n);
            $count += $n;
        }
        return $count;
    }

    /**
     * Populates the given buffer with data read from the input stream. If the data doesn't
     * fit the buffer, only the data that fits in the buffer is read. If the data is less than
     * fits in the buffer, the buffer is not completely filled.
     * @param in the input stream to read from
     * @param buffer the buffer to fill
     * @return the number of bytes written to the buffer
     * @throws IOException if an I/O error occurs
     */
    public static function populateBuffer($in, $buffer) {
        $remaining = count($buffer);
        while ($remaining > 0) {
            $bufferWritePos = count($buffer) - $remaining;
            $bytesRead = fread($in, $buffer, $bufferWritePos, $remaining);
            if ($bytesRead < 0)
            {
                break; //EOD
            }
            $remaining -= $bytesRead;
        }
        return count($buffer) - $remaining;
    }

    /**
     * Null safe close of the given {@link Closeable} suppressing any exception.
     *
     * @param closeable to be closed
     */
    public static function closeQuietly($closeable)
    {
        try
        {
            if ($closeable != null)
            {
                $closeable->close();
            }
        }
        catch (Exception $ioe)
        {
            // ignore
        }
    }
}
?>