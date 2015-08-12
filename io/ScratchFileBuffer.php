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
    private $pageIndexes = array_fill(0,16,0);		// int[16]
    /** number of pages held by this buffer */
    private $pageCount = 0;		// int
    /**
     * Creates a new buffer using pages handled by provided {@link ScratchFile}.
     * 
     * @param pageHandler The {@link ScratchFile} managing the pages to be used by this buffer.
     * 
     * @throws IOException If getting first page failed.
     */
    public function __construct($pageHandler) {
		if (!($pageHandler instanceof ScratchFile)) return;
        $pageHandler->checkClosed();
        $this->pageHandler = $pageHandler;
        $this->pageSize = $this->pageHandler->getPageSize();
        $this->addPage();
    }
    /**
     * Checks if this buffer, or the underlying {@link ScratchFile} have been closed,
     * throwing {@link IOException} if so.
     * 
     * @throws IOException If either this buffer, or the underlying {@link ScratchFile} have been closed.
     */
    private function checkClosed() {
        if ($this->pageHandler == null) {
            throw new Exception("Buffer already closed");
        }
        $this->pageHandler->checkClosed();
    }
    /**
     * Adds a new page and positions all pointers to start of new page.
     * 
     * @throws IOException if requesting a new page fails
     */
    private function addPage() {
        if ($this->pageCount+1 >= count($this->pageIndexes)) {
            $newSize = count($this->pageIndexes)*2;
            // check overflow
            if ($newSize<count($this->pageIndexes)) {
                if (count($this->pageIndexes) == PHP_INT_MAX) {
                    throw new Exception("Maximum buffer size reached.");
                }
                $newSize = PHP_INT_MAX;
            }
            $this->pageIndexes = array_pad($this->pageIndexes,$newSize,0);
        }
        $newPageIdx = $this->pageHandler->getNewPage();
        $this->pageIndexes[$this->pageCount] = $newPageIdx;
        $this->currentPagePositionInPageIndexes = $this->pageCount;
        $this->currentPageOffset = $this->pageCount * $this->pageSize; 
        $this->pageCount++;
        $this->currentPage = str_pad('',$this->pageSize,0);
        $this->positionInPage = 0;
    }
	public function length() {
		return $this->size;
	}
    /**
     * Ensures the current page has at least one byte left
     * ({@link #positionInPage} in &lt; {@link #pageSize}).
     * 
     * <p>If this is not the case we go to next page (writing
     * current one if changed). If current buffer has no more
     * pages we add a new one.</p>
     * 
     * @param addNewPageIfNeeded if <code>true</code> it is allowed to add a new page in case
     *                           we are currently at end of last buffer page
     * 
     * @return <code>true</code> if we were successful positioning pointer before end of page;
     *         we might return <code>false</code> if it is not allowed to add another page
     *         and current pointer points at end of last page
     * 
     * @throws IOException
     */
    private function ensureAvailableBytesInPage($addNewPageIfNeeded) {
		if (!is_bool($addNewPageIfNeeded)) return false;
        if ($this->positionInPage >= $this->pageSize)  {
            // page full
            if ($this->currentPageContentChanged) {
                // write page
                $this->pageHandler->writePage($this->pageIndexes[$this->currentPagePositionInPageIndexes], $this->currentPage);
                $this->currentPageContentChanged = false;
            }
            // get new page
            if ($this->currentPagePositionInPageIndexes+1 < $this->pageCount) {
                // we already have more pages assigned (there was a backward seek before)
                $this->currentPage = $this->pageHandler->readPage($this->pageIndexes[$this->currentPagePositionInPageIndexes]);
				$this->currentPagePositionInPageIndexes++;
                $this->currentPageOffset = $this->currentPagePositionInPageIndexes * $this->pageSize;
                $this->positionInPage = 0;
            } elseif ($addNewPageIfNeeded) {
                // need new page
                $this->addPage();
            } else {
                // we are at last page and are not allowed to add new page
                return false;
            }
        }
        return true;
    }
    public function write($b,$off=0,$len=0) {
		$this->checkClosed();
		if (is_integer($b)) {
			$this->ensureAvailableBytesInPage(true);
			$this->currentPage[$this->positionInPage] = $b;
			$this->positionInPage++;
			$this->currentPageContentChanged = true;
			if($this->currentPageOffset + $this->positionInPage > $this->size) {
				$this->size = $this->currentPageOffset + $this->positionInPage;
			}
			return;
		}
		if (!is_string($b)) return;
		if ($len===0) $len=strlen($b);
        $remain = $len;
        $bOff   = $off;
        
        while ($remain > 0) {
            $this->ensureAvailableBytesInPage(true);
            $bytesToWrite = min($remain, $this->pageSize-$this->positionInPage);
            $newPage = substr($this->currentPage,0,$this->positionInPage).
				substr($b,$bOff,$bytesToWrite);
			if (strlen($newPage)<strlen($this->currentPage))	// Don't try to add a zero-length substring to the end of the page.
				substr($this->currentPage,$this->positionInPage+$bytesToWrite);
			if (strlen($newPage)!=strlen($this->currentPage)) {
				throw new Exception("Buffer write failed.  The new page is no longer the correct size.");
				return;
			}
			$this->currentPage = $newPage;
            $this->positionInPage += $bytesToWrite;
            $this->currentPageContentChanged = true;
            $bOff   += $bytesToWrite;
            $remain -= $bytesToWrite;
        }
        if($this->currentPageOffset + $this->positionInPage > $this->size) {
            $this->size = $this->currentPageOffset + $this->positionInPage;
        }
    }
    public final function clear() {
        $this->checkClosed();
        // keep only the first page, discard all other pages
        $this->pageHandler->markPagesAsFree($this->pageIndexes, 1, $this->pageCount - 1);
        $this->pageCount = 1;
        // change to first page if we are not already there
        if ($this->currentPagePositionInPageIndexes > 0) {
            $this->currentPage = $this->pageHandler->readPage($this->pageIndexes[0]);
            $this->currentPagePositionInPageIndexes = 0;
            $this->currentPageOffset = 0;
        }
        $this->positionInPage = 0;
        $this->size = 0;
        $this->currentPageContentChanged = false;
    }
    public function getPosition() {
        $this->checkClosed();
        return $this->currentPageOffset + $this->positionInPage;
    }
    public function seek($seekToPosition) {
		if (!is_integer($seekToPosition)) return;
        $this->checkClosed();
        /*
         * for now we won't allow to seek past end of buffer; this can be changed by adding new pages as needed
         */
        if ($seekToPosition > $this->size) {
            throw new Exception("EOF");
        }
        if ($seekToPosition < 0) {
            throw new Exception("Negative seek offset: " + $seekToPosition);
        }
        if (($seekToPosition >= $this->currentPageOffset) && ($seekToPosition <= $this->currentPageOffset + $this->pageSize)) {
            // within same page
            $this->positionInPage = ($seekToPosition - $this->currentPageOffset);
        } else {
            // have to go to another page
            // check if current page needs to be written to file
            if ($this->currentPageContentChanged) {
                $this->pageHandler->writePage($this->pageIndexes[$this->currentPagePositionInPageIndexes], $this->currentPage);
                $this->currentPageContentChanged = false;
            }
            $newPagePosition = floor($seekToPosition / $this->pageSize);
            $currentPage = $this->pageHandler->readPage($this->pageIndexes[$newPagePosition]);
            $this->currentPagePositionInPageIndexes = $newPagePosition;
            $this->currentPageOffset = ($this->currentPagePositionInPageIndexes) * $this->pageSize;
            $this->positionInPage = ($seekToPosition - $this->currentPageOffset);
        }
    }
    public function isClosed() {
        return is_null($this->pageHandler);
    }
    public function peek() {
        $result = $this->read();
        if ($result != -1) {
            $this->rewind(1);
        }
        return $result;
    }
    public function rewind($bytes) {
		if (!is_integer($bytes)) return;
        $this->seek($this->currentPageOffset + $this->positionInPage - $bytes);
    }
    public function readFully($len) {
		if (!is_integer($len)) return;
        $b = str_pad('',$len,0);
        $n = 0;
        do {
			$count = $this->read($b, $n, $len - $n);
            if ($count < 0) {
                throw new Exception("EOF");
            }
            $n += $count;
        } while ($n < $len);
        return $b;
    }
    public function isEOF() {
        $this->checkClosed();
        return $this->currentPageOffset + $this->positionInPage >= $this->size;
    }
    public function available() {
        $this->checkClosed();
        return min($this->size - ($this->currentPageOffset + $this->positionInPage), PHP_INT_MAX);
    }
	// read
	// close
}
?>