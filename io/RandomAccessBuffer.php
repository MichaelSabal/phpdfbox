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
 * An implementation of the RandomAccess interface to store data in memory.
 * The data will be stored in chunks organized in an ArrayList.
 */
class RandomAccessBuffer {
    // default chunk size is 1kb
    private static $DEFAULT_CHUNK_SIZE = 1024;
    // use the default chunk size
    private $chunkSize;
    // list containing all chunks
    private $bufferList = null;
    // current chunk
    private $currentBuffer;
    // current pointer to the whole buffer
    private $pointer;
    // current pointer for the current chunk
    private $currentBufferPointer;
    // size of the whole buffer
    private $size;
    // current chunk list index
    private $bufferListIndex;
    // maximum chunk list index
    private $bufferListMaxIndex;

    /**
     * Default constructor.
     */
    public function __construct($input = null) {
		$this->chunkSize = RandomAccessBuffer::DEFAULT_CHUNK_SIZE;
		if (is_null($input) || is_resource($input)) {
			// starting with one chunk
			$this->bufferList = array();
			$this->currentBuffer = array_pad(array(),$this->chunkSize,0);
			$this->bufferList[] = $currentBuffer;
			$this->pointer = 0;
			$this->currentBufferPointer = 0;
			$this->size = 0;
			$this->bufferListIndex = 0;
			$this->bufferListMaxIndex = 0;
		} elseif (is_array($input)) {
			// this is a special case. The given byte array is used as the one
			// and only chunk.
			$this->bufferList = [$input];
			$this->chunkSize = count($input);
			$this->currentBuffer = $input;
			$this->pointer = 0;
			$this->currentBufferPointer = 0;
			$this->size = $chunkSize;
			$this->bufferListIndex = 0;
			$this->bufferListMaxIndex = 0;
		}
		if (is_resource($input)) {
			$byteBuffer = array_pad(array(),8192,0);
			$bytesRead = 0;
			while (($bytesRead = fread($input,$byteBuffer)) > -1)
			{
				$this->write($byteBuffer, 0, $bytesRead);
			}
			$this->seek(0);
		}
    }
    public function duplicate() {
        $copy = new RandomAccessBuffer();

        $copy->bufferList = $this->bufferList;
        if (!is_null($this->currentBuffer)) {
            $copy->currentBuffer = $copy->bufferList[count($copy->bufferList)-1];
        } else {
            $copy->currentBuffer = null;
        }
        $copy->pointer = $this->pointer;
        $copy->currentBufferPointer = $this->currentBufferPointer;
        $copy->size = $this->size;
        $copy->bufferListIndex = $this->bufferListIndex;
        $copy->bufferListMaxIndex = $this->bufferListMaxIndex;
        return $copy;
    }

    /**
     * {@inheritDoc}
     */
    public function close() {
        $this->currentBuffer = null;
        $this->bufferList = array();
        $this->pointer = 0;
        $this->currentBufferPointer = 0;
        $this->size = 0;
        $this->bufferListIndex = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function clear() {
        $this->bufferList = array();
        $this->currentBuffer = array_pad(array(),$this->chunkSize,0);
        $this->bufferList[] = $this->currentBuffer;
        $this->pointer = 0;
        $this->currentBufferPointer = 0;
        $this->size = 0;
        $this->bufferListIndex = 0;
        $this->bufferListMaxIndex = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function seek($position) {
		if (!is_integer($position)) return;
        $this->checkClosed();
        if ($this->position < 0)
        {
            throw new Exception("Invalid position ".$position);
        }
        $this->pointer = $position;
        if ($this->pointer < $this->size)
        {
            // calculate the chunk list index
            $this->bufferListIndex = floor($this->pointer / $this->chunkSize);
            $this->currentBufferPointer = floor($this->pointer % $this->chunkSize);
            $this->currentBuffer = $this->bufferList[$this->bufferListIndex];
        } else {
            // it is allowed to jump beyond the end of the file
            // jump to the end of the buffer
            $this->bufferListIndex = $this->bufferListMaxIndex;
            $this->currentBuffer = $this->bufferList[$this->bufferListIndex];
            $this->currentBufferPointer = floor($this->size % $this->chunkSize);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPosition() {
       $this->checkClosed();
       return $this->pointer;
    }
    /**
     * {@inheritDoc}
     */
    public function read(&$b=null, $offset=0, $length=-1) {
		if (is_null($b)) $b = array_pad(array(),1,0);
		if ($length==-1) $length = count($b);
        $this->checkClosed();
        if ($this->pointer >= $this->size) {
			if ($length==1) return -1;
            else return 0;
        }
        $bytesRead = $this->readRemainingBytes($b, $offset, $length);
        while ($bytesRead < $length && $this->available() > 0) {
            $bytesRead += $this->readRemainingBytes($b, $offset + $bytesRead, $length - $bytesRead);
            if ($this->currentBufferPointer == $this->chunkSize)
            {
                $this->nextBuffer();
            }
        }
        return $bytesRead;
    }

    private function readRemainingBytes(&$b, $offset, $length) {
        if ($this->pointer >= $this->size) {
            return 0;
        }
        $maxLength = floor(min($length, $this->size-$this->pointer));
        $remainingBytes = $this->chunkSize - $this->currentBufferPointer;
        // no more bytes left
        if ($remainingBytes == 0) {
            return 0;
        }
        if ($maxLength >= $remainingBytes) {
            // copy the remaining bytes from the current buffer
			$b = array_slice($this->currentBuffer,$this->currentBufferPointer+$offset,$remainingBytes);
            // end of file reached
            $this->currentBufferPointer += $remainingBytes;
            $this->pointer += $remainingBytes;
            return $remainingBytes;
        } else {
            // copy the remaining bytes from the whole buffer
			$b = array_slice($this->currentBuffer,$this->currentBufferPointer+$offset,$maxLength);
            // end of file reached
            $this->currentBufferPointer += $maxLength;
            $this->pointer += $maxLength;
            return $maxLength;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function length() {
        $this->checkClosed();
        return $this->size;
    }
    /**
     * {@inheritDoc}
     */
    public function write($b=null, $offset=0, $length=-1) {
		if (is_integer($b)) $b = array(chr($b));
		if (!is_array($b)) throw new Exception("Bad write to RandomAccessBuffer.");
		if ($length==-1) $length = count($b);
        $this->checkClosed();
        $newSize = $this->pointer + $length;
        $remainingBytes = $this->chunkSize - $this->currentBufferPointer;
        if ($length >= $remainingBytes) {
            if ($newSize > PHP_INT_MAX) {
                throw new Exception("RandomAccessBuffer overflow");
            }
            // copy the first bytes to the current buffer
			for ($i=$this->currentBufferPointer;$i<$remainingBytes;$i++) {
				$this->currentBuffer[$i] = $b[$offset+($i-$this->currentBufferPointer)];
			}
            $newOffset = $offset + $remainingBytes;
            $remainingBytes2Write = $length - $remainingBytes;
            // determine how many buffers are needed for the remaining bytes
            $numberOfNewArrays = floor($remainingBytes2Write / $this->chunkSize);
            for ($na=0;$na<$numberOfNewArrays;$na++) {
                $this->expandBuffer();
				for ($i=$this->currentBufferPointer;$i<$this->chunkSize;$i++) {
					$this->currentBuffer[$i] = $b[$newOffset+($i-$this->currentBufferPointer)];
				}
                $newOffset += $this->chunkSize;
            }
            // are there still some bytes to be written?
            $remainingBytes2Write -= $numberOfNewArrays * $this->chunkSize;
            if ($remainingBytes2Write >= 0) {
                $this->expandBuffer();
                if ($remainingBytes2Write > 0) {
					for ($i=$this->currentBufferPointer;$i<$remainingBytes2Write;$i++) {
						$this->currentBuffer[$i] = $b[$newOffset+($i-$this->currentBufferPointer)];
					}
                }
                $this->currentBufferPointer = $remainingBytes2Write;
            }
        } else {
			for ($i=$this->currentBufferPointer;$i<$length;$i++) {
				$this->currentBuffer[$i] = $b[$offset+($i-$this->currentBufferPointer)];
			}
            $this->currentBufferPointer += $length;
        }
        $this->pointer += $length;
        if ($this->pointer > $this->size) {
            $this->size = $this->pointer;
        }
    }

    /**
     * create a new buffer chunk and adjust all pointers and indices.
     */
    private function expandBuffer() {
        if ($this->bufferListMaxIndex > $this->bufferListIndex) {
            // there is already an existing chunk
            $this->nextBuffer();
        } else {
            // create a new chunk and add it to the buffer
            $this->currentBuffer = array_pad(array(),$this->chunkSize,0);
            $this->bufferList[] = $this->currentBuffer;
            $this->currentBufferPointer = 0;
            $this->bufferListMaxIndex++;
            $this->bufferListIndex++;
        }
    }

    /**
     * switch to the next buffer chunk and reset the buffer pointer.
     */
    private function nextBuffer() {
        if ($this->bufferListIndex == $this->bufferListMaxIndex) {
            throw new Exception("No more chunks available, end of buffer reached");
        }
        $this->currentBufferPointer = 0;
        $this->currentBuffer = $this->bufferList[++$bufferListIndex];
    }
    
    /**
     * Ensure that the RandomAccessBuffer is not closed
     * @throws IOException
     */
    private function checkClosed() {
        if (is_null($this->currentBuffer))
        {
            // consider that the rab is closed if there is no current buffer
            throw new Exception("RandomAccessBuffer already closed");
        }
    }
    /**
     * {@inheritDoc}
     */
    public function isClosed()
    {
        return is_null($this->currentBuffer);
    }
    /**
     * {@inheritDoc}
     */
    public function isEOF() {
        $this->checkClosed();
        return $this->pointer >= $this->size;
    }
    /**
     * {@inheritDoc}
     */
    public function available() {
        return min($this->length() - $this->getPosition(), PHP_INT_MAX);
    }
    /**
     * {@inheritDoc}
     */
    public function peek() {
        $result = $this->read();
        if ($result != -1)
        {
            $this->rewind(1);
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind($bytes) {
		if (is_integer($bytes)) {
			$this->checkClosed();
			$this->seek($this->getPosition() - $bytes);
		}
    }

    /**
     * {@inheritDoc}
     */
    public function readFully($length) {
        $b = array_pad(array(),$length,0);
        $bytesRead = $this->read($b);
        while ($bytesRead < $length) {
            $bytesRead += $this->read($b, $bytesRead, $length - $bytesRead);
        }
        return $b;
    }
}
?>