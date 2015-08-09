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
 * Implementation of {@link RandomAccess} as sequence of multiple fixed size pages handled
 * by {@link ScratchFile}.
 */
class ScratchFileBuffer {
    private $pageSize;	// int
    /**
     * The underlying page handler.
     */
    private $pageHandler;	// ScratchFile
    /**
     * The number of bytes of content in this buffer.
     */
    private $size = 0;	// long
    /**
     * Index of current page in {@link #pageIndexes} (the nth page within this buffer).
     */
    private $currentPagePositionInPageIndexes;	// int
    /**
     * The offset of the current page within this buffer.
     */
    private $currentPageOffset;	// long
    /**
     * The current page data.
     */
    private $currentPage;	// byte[]
    /**
     * The current position (for next read/write) of the buffer as an offset in the current page.
     */
    private $positionInPage;	// int
    /** 
     * <code>true</code> if current page was changed by a write method
     */
    private $currentPageContentChanged = false;	// boolean
    /** contains ordered list of pages with the index the page is known by page handler ({@link ScratchFile}) */
    private $pageIndexes = array();		// int[16]
    /** number of pages held by this buffer */
    private $pageCount = 0;		// int

}
?>