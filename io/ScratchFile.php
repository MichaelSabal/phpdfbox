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
 require_once('MemoryUsageSetting.php');
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
    const ENLARGE_PAGE_COUNT = 16;
    /** in case of unrestricted main memory usage this is the initial number of pages
     *  {@link #inMemoryPages} is setup for */
    const INIT_UNRESTRICTED_MAINMEM_PAGECOUNT = 100000;
    const PAGE_SIZE = 4096;
	private $ioLock;
	private $scratchFileDirectory;
	private $file;
    /** random access to scratch file; only to be accessed under synchronization of {@link #ioLock} */
	private $raf;
	private $pageCount = 0;
	private $freePages;	// = new BitSet(); - array of boolean
    /** holds pointers to in-memory page content; will be initialized once in case of restricted
     *  main memory, otherwise it is enlarged as needed and first initialized to a size of
     *  {@link #INIT_UNRESTRICTED_MAINMEM_PAGECOUNT} */
	private $inMemoryPages = array();
	private $inMemoryMaxPageCount;	// int
	private $maxPageCount;			// int
	private $useScratchFile;			// boolean
	private $maxMainMemoryRestricted;	// boolean
	
	private $isClosed = false;
    /**
     * Initializes page handler. If a <code>scratchFileDirectory</code> is supplied,
     * then the scratch file will be created in that directory.
     * 
     * <p>All pages will be stored in the scratch file.</p>
     * <p>Depending on the size of allowed memory usage a number of pages (memorySize/{@link #PAGE_SIZE})
     * will be stored in-memory and only additional pages will be written to/read from scratch file.</p>
     * 
     * @param param =memUsageSetting set how memory/temporary files are used for buffering streams etc. 
     * or =scratchFileDirectory The directory in which to create the scratch file
     *                             or <code>null</code> to created it in the default temporary directory.
     * 
     * @throws IOException If scratch file directory was given but don't exist.
     */
	public function __construct($param) {
		if (is_file($param)) {
			$mus = new MemoryUsageSetting();
			$param = $mus->setupTempFileOnly()->setTempDir($param);
		} 
		if ($param instanceof MemoryUsageSetting) {
			$this->maxMainMemoryIsRestricted = ((!($param->useMainMemory())) || $param->isMainMemoryRestricted());
			$this->useScratchFile = $this->maxMainMemoryIsRestricted ? $param->useTempFile() : false;
			$this->scratchFileDirectory = $this->useScratchFile ? $param->getTempDir() : null;
			$this->maxPageCount = $param->isStorageRestricted() ? floor(min(PHP_INT_MAX, $param->getMaxStorageBytes() / PAGE_SIZE)) : PHP_INT_MAX;
			$this->inMemoryMaxPageCount = $param->useMainMemory() ? ($param->isMainMemoryRestricted() ? 
				floor( min(PHP_INT_MAX, $param->getMaxMainMemoryBytes() / PAGE_SIZE)) : PHP_INT_MAX) : 0;
		} else {
			$this->maxMainMemoryRestricted = true;
			$this->useScratchFile = true;
			$this->scratchFileDirectory = sys_get_temp_dir();
			$this->maxPageCount = floor(100*1024*1024 / PAGE_SIZE);
			$this->inMemoryMaxPageCount = floor(ini_get('memory_limit')/PAGE_SIZE);
		}
        if (!is_null($this->scratchFileDirectory) && !is_dir($this->scratchFileDirectory)) {
            throw new Exception("Scratch file directory does not exist: ".$this->scratchFileDirectory);
        }
		
        $this->inMemoryPages = array();	// size check will take place while the array is in use.
		$freepagecount = $this->maxMainMemoryIsRestricted ? $this->inMemoryMaxPageCount : INIT_UNRESTRICTED_MAINMEM_PAGECOUNT;
		for ($i=0;$i<$freepagecount;$i++) $this->freePages[$i] = true;
	}
    /**
     * Returns a new free page, either from free page pool
     * or by enlarging scratch file (may be created).
     * 
     * @return index of new page
     */
	private function getNewPage() {
		$idx = array_search(true,$this->freePages);
		if ($idx===false) {
			$this->enlarge();
			$idx = array_search(true,$this->freePages);
			if ($idx===false) throw new Exception("Maximum allowed scratch file memory exceeded.");
		}
		$this->freePages[$idx] = false;
		if ($idx >= $this->pageCount) $this->pageCount = $idx+1;
		return $idx;
	}
    /**
     * This will provide new free pages by either enlarging the scratch file 
     * by a number of pages defined by {@link #ENLARGE_PAGE_COUNT} - in case
     * scratch file usage is allowed - or increase the {@link #inMemoryPages}
     * array in case main memory was not restricted. If neither of both is
     * allowed/the case than free pages count won't be changed. The same is true
     * if no new pages could be added because we reached the maximum of
     * {@link Integer#MAX_VALUE} pages.
     * 
     * <p>If scratch file uage is allowed and scratch file does not exist already
     * it will be created.</p>
     * 
     * <p>Only to be called under synchronization on {@link #freePages}.</p>
     */
	private function enlarge() {
		$this->checkClosed();
		if ($this->pageCount >= $this->maxPageCount) return;
		if ($this->useScratchFile) {
			// create scratch file is needed
			if ( $this->raf == null ) {
				$this->file = tempnam($this->scratchFileDirectory,'PDFBox');
				try {
					$this->raf = fopen($this->file,'w+');
				} catch ($e) {
					$this->raf = tmpfile();
					$meta = stream_get_meta_data($this->raf);
					$this->file = $meta['uri'];
				}
			}
			$fileLen = filesize($this->file);
			$expectedFileLen = ($this->pageCount - $this->inMemoryMaxPageCount) * PAGE_SIZE;
			if ($expectedFileLen != $fileLen) {
				throw new Exception("Expected scratch file size of $expectedFileLen but found $fileLen");
			}
			// enlarge if we do not overflow
			if ($this->pageCount + ENLARGE_PAGE_COUNT <= $this->maxPageCount) {
				$fileLen += ENLARGE_PAGE_COUNT * PAGE_SIZE;
				fseek($this->raf,0,SEEK_END);
				fwrite($this->raf,str_repeat(chr(0),ENLARGE_PAGE_COUNT*PAGE_SIZE));
				for ($i=$this->pageCount;$i<$this->pageCount+ENLARGE_PAGE_COUNT;$i++) $this->freePages[$i]=true;
			}
		} elseif (!$this->maxMainMemoryRestricted) {
			// increase number of in-memory pages
			$oldSize = count($this->inMemoryPages);
			$newSize = floor(min($oldSize) * 2, PHP_INT_MAX);  // this handles integer overflow
			if ($newSize > $oldSize) {
				for ($i=$oldSize;$i<$newSize;$i++) $this->freePages[$i] = true;
			}
		}
	}
    /**
     * Returns byte size of a page.
     * 
     * @return byte size of a page
     */
    private function getPageSize()
    {
        return PAGE_SIZE;
    }
    /**
     * Reads the page with specified index.
     * 
     * @param pageIdx index of page to read
     * 
     * @return byte array of size {@link #PAGE_SIZE} filled with page data read from file 
     * 
     * @throws IOException
     */
	private function readPage($pageIdx) {
        if (($pageIdx < 0) || ($pageIdx >= $this->pageCount))
        {
            $this->checkClosed();
            throw new Exception("Page index out of range: $pageIdx. Max value: ".($this->pageCount - 1) );
        }
        // check if we have the page in memory
        if ($pageIdx < $this->inMemoryMaxPageCount) {
            $page = $this->inMemoryPages[pageIdx];
            // handle case that we are closed
            if ($page == null) {
                $this->checkClosed();
                throw new Exception("Requested page with index $pageIdx was not written before.");
            }
            return $page;
        }
		if ($this->raf == null)	{
			$this->checkClosed();
			throw new Exception("Missing scratch file to read page with index $pageIdx from.");
		}
		$page = array();
		fseek($this->raf,($pageIdx - $this->inMemoryMaxPageCount) * PAGE_SIZE);
		$page = fread($this->raf,PAGE_SIZE);		
		return $page;
	}
}
?>