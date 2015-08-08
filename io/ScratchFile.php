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
 * Implements a memory page handling mechanism as base for creating (multiple)
 * {@link RandomAccess} buffers each having its set of pages (implemented by
 * {@link ScratchFileBuffer}). A buffer is created calling {@link #createBuffer()}.
 * 
 * <p>Pages can be stored in main memory or in a temporary file. A mixed mode
 * is supported storing a certain amount of pages in memory and only the
 * additional ones in temporary file (defined by maximum main memory to
 * be used).</p>
 * 
 * <p>Pages can be marked as 'free' in order to re-use them. For in-memory pages
 * this will release the used memory while for pages in temporary file this
 * simply marks the area as free to re-use.</p>
 * 
 * <p>If a temporary file was created (done with the first page to be stored
 * in temporary file) it is deleted when {@link ScratchFile#close()} is called.</p>
 * 
 * <p>Using this class for {@link RandomAccess} buffers allows for a direct control
 * on the maximum memory usage and allows processing large files for which we
 * otherwise would get an {@link OutOfMemoryError} in case of using {@link RandomAccessBuffer}.</p>
 * 
 * <p>This base class for providing pages is thread safe (the buffer implementations are not).</p>
 */
class ScratchFile {
    /** number of pages by which we enlarge the scratch file (reduce I/O-operations) */
    private const ENLARGE_PAGE_COUNT = 16;
    /** in case of unrestricted main memory usage this is the initial number of pages
     *  {@link #inMemoryPages} is setup for */
    private const INIT_UNRESTRICTED_MAINMEM_PAGECOUNT = 100000;
    private const PAGE_SIZE = 4096;
	private $ioLock;
	private $scratchFileDirectory;
	private $file;
    /** random access to scratch file; only to be accessed under synchronization of {@link #ioLock} */
	private $raf;
	private $pageCount = 0;
	private $freePages;	// = new BitSet();
    /** holds pointers to in-memory page content; will be initialized once in case of restricted
     *  main memory, otherwise it is enlarged as needed and first initialized to a size of
     *  {@link #INIT_UNRESTRICTED_MAINMEM_PAGECOUNT} */
	private $inMemoryPages = array();
	


}
?>