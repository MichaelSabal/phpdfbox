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

final class MemoryUsageSetting {
    private $useMainMemory;		// boolean
    private $useTempFile;		// boolean
    /** maximum number of main-memory bytes allowed to be used;
     *  <code>-1</code> means 'unrestricted' */
    private $maxMainMemoryBytes;	// long
    /** maximum number of bytes allowed for storage at all (main-memory+file);
     *  <code>-1</code> means 'unrestricted' */
    private $maxStorageBytes;		// long
    /** directory to be used for scratch file */
    private $tempDir = null;		// File
    /**
     * Private constructor for setup buffering memory usage called by one of the setup methods.
     * 
     * @param useMainMemory if <code>true</code> main memory usage is enabled; in case of
     *                      <code>false</code> and <code>useTempFile</code> is <code>false</code> too
     *                      we set this to <code>true</code>
     * @param useTempFile if <code>true</code> using of temporary file(s) is enabled
     * @param maxMainMemoryBytes maximum number of main-memory to be used;
     *                           if <code>-1</code> means 'unrestricted';
     *                           if <code>0</code> we only use temporary file if <code>useTempFile</code>
     *                           is <code>true</code> otherwise main-memory usage will have restriction
     *                           defined by maxStorageBytes
     * @param maxStorageBytes maximum size the main-memory and temporary file(s) may have all together;
     *                        <code>0</code>  or less will be ignored; if it is less than
     *                        maxMainMemoryBytes we use maxMainMemoryBytes value instead 
     */
    private function __construct($useMainMemory,$useTempFile,
                        $maxMainMemoryBytes, $maxStorageBytes) {
		if (!is_bool($useMainMemory) || !is_bool($useTempFile)) return;
        // do some checks; adjust values as needed to get consistent setting
        $locUseMainMemory = $useTempFile ? $useMainMemory : true;
        $locMaxMainMemoryBytes = $useMainMemory ? $maxMainMemoryBytes : -1;
        $locMaxStorageBytes = $maxStorageBytes > 0 ? $maxStorageBytes : -1;
        
        if ($locMaxMainMemoryBytes < -1) $locMaxMainMemoryBytes = -1;
        
        if ($locUseMainMemory && ($locMaxMainMemoryBytes == 0)) {
            if ($useTempFile) {
                $locUseMainMemory = false;
            } else {
                $locMaxMainMemoryBytes = $locMaxStorageBytes;
            }
        }
        if ($locUseMainMemory && ($locMaxStorageBytes > -1) &&
            (($locMaxMainMemoryBytes == -1) || ($locMaxMainMemoryBytes > $locMaxStorageBytes)))
        {
            $locMaxStorageBytes = $locMaxMainMemoryBytes;
        }
        $this->useMainMemory = $locUseMainMemory;
        $this->useTempFile = $useTempFile;
        $this->maxMainMemoryBytes = $locMaxMainMemoryBytes;
        $this->maxStorageBytes = $locMaxStorageBytes;
	}
    /**
     * Setups buffering memory usage to only use main-memory (no temporary file)
     * @param maxMainMemoryBytes maximum number of main-memory to be used;
     *                           <code>-1</code> for no restriction;
     *                           <code>0</code> will also be interpreted here as no restriction
     */
    public static function setupMainMemoryOnly($maxMainMemoryBytes=-1) {
        return new MemoryUsageSetting(true, false, $maxMainMemoryBytes, $maxMainMemoryBytes);
    }
    /**
     * Setups buffering memory usage to only use temporary file(s) (no main-memory)
     * with the specified maximum size.
     * 
     * @param maxStorageBytes maximum size the temporary file(s) may have all together;
     *                        <code>-1</code> for no restriction;
     *                        <code>0</code> will also be interpreted here as no restriction
     */
    public static function setupTempFileOnly($maxStorageBytes=-1) {
        return new MemoryUsageSetting(false, true, 0, $maxStorageBytes);
    }
    /**
     * Setups buffering memory usage to use a portion of main-memory and additionally
     * temporary file(s) in case the specified portion is exceeded.
     * 
     * @param maxMainMemoryBytes maximum number of main-memory to be used;
     *                           if <code>-1</code> this is the same as {@link #setupMainMemoryOnly()};
     *                           if <code>0</code> this is the same as {@link #setupTempFileOnly()}
     * @param maxStorageBytes maximum size the main-memory and temporary file(s) may have all together;
     *                        <code>0</code>  or less will be ignored; if it is less than
     *                        maxMainMemoryBytes we use maxMainMemoryBytes value instead 
     */
    public static function setupMixed($maxMainMemoryBytes=-1, $maxStorageBytes=-1) {
        return new MemoryUsageSetting(true, true, $maxMainMemoryBytes, $maxStorageBytes);
    }
    /**
     * Sets directory to be used for temporary files.
     * 
     * @param tempDir directory for temporary files
     * 
     * @return this instance
     */
    public function setTempDir($tempDir) {
		if (is_dir($tempDir))
			$this->tempDir = $tempDir;
        return $this;
    }
    /**
     * Returns <code>true</code> if main-memory is to be used.
     * 
     * <p>If this returns <code>false</code> it is ensured {@link #useTempFile()}
     * returns <code>true</code>.</p>
     */
    public function useMainMemory() {
        return $this->useMainMemory;
    }
    /**
     * Returns <code>true</code> if temporary file is to be used.
     * 
     * <p>If this returns <code>false</code> it is ensured {@link #useMainMemory}
     * returns <code>true</code>.</p>
     */
    public function useTempFile() {
        return $this->useTempFile;
    }
    /**
     * Returns <code>true</code> if maximum main memory is restricted to a specific
     * number of bytes.
     */
    public function isMainMemoryRestricted() {
        return $this->maxMainMemoryBytes >= 0;
    }
    /**
     * Returns <code>true</code> if maximum amount of storage is restricted to a specific
     * number of bytes.
     */
    public function isStorageRestricted() {
        return $this->maxStorageBytes > 0;
    }
    /**
     * Returns maximum size of main-memory in bytes to be used.
     */
    public function getMaxMainMemoryBytes() {
        return $this->maxMainMemoryBytes;
    }
    /**
     * Returns maximum size of storage bytes to be used
     * (main-memory in temporary files all together).
     */
    public function getMaxStorageBytes() {
        return $this->maxStorageBytes;
    }
    /**
     * Returns directory to be used for temporary files or <code>null</code>
     * if it was not set.
     */
    public function getTempDir() {
        return $this->tempDir;
    }
    public function toString() {
        return $useMainMemory ?
                   ($this->useTempFile ? "Mixed mode with max. of $maxMainMemoryBytes main memory bytes".
                                  ($this->isStorageRestricted() ? " and max. of $maxStorageBytes storage bytes" :
                                                           " and unrestricted scratch file size") :
                                  ($this->isMainMemoryRestricted() ? "Main memory only with max. of $maxMainMemoryBytes bytes" :
                                                              "Main memory only with no size restriction")):
                   ($this->isStorageRestricted() ? "Scratch file only with max. of $maxStorageBytes bytes" :
                                            "Scratch file only with no size restriction");
    }
}
?>