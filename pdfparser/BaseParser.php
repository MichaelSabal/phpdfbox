<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License").php'); you may not use this file except in compliance with
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
require_once('../cos/COSBase.php');
require_once('../cos/COSDictionary.php');
require_once('../cos/COSDocument.php');
require_once('../cos/COSName.php');
//require_once('../cos/COSObject.php');
//require_once('../cos/COSObjectKey.php');
//require_once('../cos/COSArray.php');
/**
 * This class is used to contain parsing logic that will be used by both the
 * PDFParser and the COSStreamParser.
 *
 * @author Ben Litchfield
 */
abstract class BaseParser {
    const OBJECT_NUMBER_THRESHOLD = 10000000000;

    const GENERATION_NUMBER_THRESHOLD = 65535;

    const E = 101;
    const N = 110;
    const D = 100;

    const S = 115;
    const T = 116;
    const R = 114;
    const A = 97;
    const M = 109;

    const O = 111;
    const B = 98;
    const J = 106;

    /**
     * This is a string constant that will be used for comparisons.
     */
    const DEF = "def";
    /**
     * This is a string constant that will be used for comparisons.
     */
    const ENDOBJ_STRING = "endobj";
    /**
     * This is a string constant that will be used for comparisons.
     */
    const ENDSTREAM_STRING = "endstream";
    /**
     * This is a string constant that will be used for comparisons.
     */
    const STREAM_STRING = "stream";
    /**
     * This is a string constant that will be used for comparisons.
     */
    const TRUE = "true";
    /**
     * This is a string constant that will be used for comparisons.
     */
    const FALSE = "false";
    /**
     * This is a string constant that will be used for comparisons.
     */
    const NULL = "null";

    /**
     * ASCII code for line feed.
     */
    const ASCII_LF = 10;
    /**
     * ASCII code for carriage return.
     */
    const ASCII_CR = 13;
    const ASCII_ZERO = 48;
	const ASCII_NINE = 57;
    const ASCII_SPACE = 32;
    
    /**
     * This is the stream that will be read from.
     */
    protected $seqSource;	// InputStreamSource

    /**
     * This is the document that will be parsed.
     */
    protected $document;	// COSDocument
    /**
     * Default constructor.
     */
    public function __construct($pdfSource) {
		if (!($pdfSource instanceof InputStreamSource)) return;
        $this->seqSource = $pdfSource;
    }
    private static function isHexDigit($ch) {
		if (is_integer($ch)) return ($ch>=48 && $ch<=57) || ($ch>=65 && $ch<=70) || ($ch>=97 && $ch<=102);
		return is_string($ch) && ctype_xdigit($ch);
    }
    /**
     * This will parse a PDF dictionary value.
     *
     * @return The parsed Dictionary object.
     *
     * @throws IOException If there is an error parsing the dictionary object.
     */
    private function parseCOSDictionaryValue()
    {
        $numOffset = $this->seqSource->getPosition();	// long
        $number = $this->parseDirObject();	// COSBase
        $this->skipSpaces();
        if (!$this->isDigit()) {
            return $number;
        }
        $genOffset = $this->seqSource->getPosition();	// long
        $generationNumber = $this->parseDirObject();	// COSBase
        $this->skipSpaces();
        $this->readExpectedChar('R');
        if (!is_numeric($number)) {
            throw new Exception("expected number, actual=".$number." at offset ".$numOffset);
        }
        if (!is_numeric($generationNumber)) {
            throw new Exception("expected number, actual=".$number." at offset ".$genOffset);
        }
        $key = new COSObjectKey($number, floor($generationNumber));
        return $this->getObjectFromPool($key);
    }
    private function getObjectFromPool($key) {
		if (!($key instanceof COSObjectKey)) return null;
        if ($this->document == null) {
            throw new Exception("object reference ".$key." at offset ".$this->seqSource->getPosition()." in content stream");
        }
        return $this->document->getObjectFromPool($key);
    }
    /**
     * This will parse a PDF dictionary.
     *
     * @return The parsed dictionary.
     *
     * @throws IOException If there is an error reading the stream.
     */
    protected function parseCOSDictionary() {
        $this->readExpectedChar('<');
        $this->readExpectedChar('<');
        $this->skipSpaces();
        $obj = new COSDictionary();
        $done = false;
        while (!$done) {
            $this->skipSpaces();
            $c = $this->seqSource->peek();
            if ($c == '>' || $c==ord('>')) {
                $done = true;
            } elseif ($c == '/' || $c==ord('/')) {
                $this->parseCOSDictionaryNameValuePair($obj);
            } else {
                // invalid dictionary, we were expecting a /Name, read until the end or until we can recover
                print("Invalid dictionary, found: '".$c."' but expected: '/'");
                if ($this->readUntilEndOfCOSDictionary())  {
                    // we couldn't recover
                    return $obj;
                }
            }
        }
        $this->readExpectedChar('>');
        $this->readExpectedChar('>');
        return $obj;
    }
    /**
     * Keep reading until the end of the dictionary object or the file has been hit, or until a '/'
     * has been found.
     *
     * @return true if the end of the object or the file has been found, false if not, i.e. that the
     * caller can continue to parse the dictionary at the current position.
     *
     * @throws IOException if there is a reading error.
     */
    private function readUntilEndOfCOSDictionary() {
        $c = $this->seqSource->read();
        while ($c != -1 && $c != ord('/') && $c != ord('>')) {
            // in addition to stopping when we find / or >, we also want
            // to stop when we find endstream or endobj.
            if ($c == E) {
                $c = $this->seqSource->read();
                if ($c == N) {
                    $c = $this->seqSource->read();
                    if ($c == D) {
                        $c = $this->seqSource->read();
                        $isStream = ($c == S && $this->seqSource->read() == T && $this->seqSource->read() == R
                                && $this->seqSource->read() == E && $this->seqSource->read() == A && $this->seqSource->read() == M);
                        $isObj = (!isStream && c == O && $this->seqSource->read() == B && $this->seqSource->read() == J);
                        if ($isStream || $isObj) {
                            // we're done reading this object!
                            return true;
                        }
                    }
                }
            }
            $c = $this->seqSource->read();
        }
        if ($c == -1) {
            return true;
        }
        $this->seqSource->unread($c);
        return false;
    }
    private function parseCOSDictionaryNameValuePair($obj) {
		if (!($obj instanceof COSDictionary)) return;
        $key = $this->parseCOSName();
        $value = $this->parseCOSDictionaryValue();
        $this->skipSpaces();
		$c = $this->seqSource->peek();
        if ( $c == 'd' || $c == 100) {
            // if the next string is 'def' then we are parsing a cmap stream
            // and want to ignore it, otherwise throw an exception.
            $potentialDEF = $this->readString();
            if (!$potentialDEF==DEF) {
                $this->seqSource->unread($potentialDEF);
            } else {
                $this->skipSpaces();
            }
        }
        if (is_null($value)) {
            print("Bad Dictionary Declaration ".$this->seqSource->toString());
        } else {
            $value->setDirect(true);
            $obj->setItem($key, $value);
        }
    }
    protected function skipWhiteSpaces() {
        //PDF Ref 3.2.7 A stream must be followed by either
        //a CRLF or LF but nothing else.
        $whitespace = $this->seqSource->read();
        //see brother_scan_cover.pdf, it adds whitespaces
        //after the stream but before the start of the
        //data, so just read those first
        while (ASCII_SPACE == $whitespace) {
            $whitespace = $this->seqSource->read();
        }
        if (ASCII_CR == $whitespace) {
            $whitespace = $this->seqSource->read();
            if (ASCII_LF != $whitespace) {
                $this->seqSource->unread($whitespace);
                //The spec says this is invalid but it happens in the real
                //world so we must support it.
            }
        } elseif (ASCII_LF != $whitespace) {
            //we are in an error.
            //but again we will do a lenient parsing and just assume that everything
            //is fine
            $this->seqSource->unread($whitespace);
        }
    }
    /**
     * This is really a bug in the Document creators code, but it caused a crash
     * in PDFBox, the first bug was in this format:
     * /Title ( (5)
     * /Creator which was patched in 1 place.
     * However it missed the case where the Close Paren was escaped
     *
     * The second bug was in this format
     * /Title (c:\)
     * /Producer
     *
     * This patch  moves this code out of the parseCOSString method, so it can be used twice.
     *
     *
     * @param bracesParameter the number of braces currently open.
     *
     * @return the corrected value of the brace counter
     * @throws IOException
     */
    private function checkForMissingCloseParen($bracesParameter) {
		if (!is_integer($bracesParameter)) return -1;
        $braces = $bracesParameter;
        $nextThreeBytes = "   ";
        $amountRead = $this->seqSource->read($nextThreeBytes);
        //lets handle the special case seen in Bull  River Rules and Regulations.pdf
        //The dictionary looks like this
        //    2 0 obj
        //    <<
        //        /Type /Info
        //        /Creator (PaperPort http://www.scansoft.com)
        //        /Producer (sspdflib 1.0 http://www.scansoft.com)
        //        /Title ( (5)
        //        /Author ()
        //        /Subject ()
        //
        // Notice the /Title, the braces are not even but they should
        // be.  So lets assume that if we encounter an this scenario
        //   <end_brace><new_line><opening_slash> then that
        // means that there is an error in the pdf and assume that
        // was the end of the document.
        //
        if ($amountRead == 3 && ($nextThreeBytes=="\r\n/" || substr($nextThreeBytes,0,2)=="\r/")) {
			$braces = 0;
        }
        if ($amountRead > 0) {
            $this->seqSource->unread($nextThreeBytes, 0, $amountRead);
        }
        return $braces;
    }
    /**
     * This will parse a PDF string.
     *
     * @return The parsed PDF string.
     *
     * @throws IOException If there is an error reading from the stream.
     */
    protected function parseCOSString() {
        $nextChar = $this->seqSource->read();
		$openBrace = '(';
		$closeBrace = ')';
        if( $nextChar == '(' || $nextChar == ord('(')) {
            $openBrace = '(';
            $closeBrace = ')';
        } elseif( $nextChar == '<' || $nextChar == ord('<')) {
            return $this->parseCOSHexString();
        } else {
            throw new Exception( "parseCOSString string should start with '(' or '<' and not '$nextChar' ".$this->seqSource->toString());
        }
        $out = new ByteArrayOutputStream();
        //This is the number of braces read
        //
        $braces = 1;
        $c = $this->seqSource->read();
        while( $braces > 0 && $c != -1) {
            $ch = chr($c);
            $nextc = -2; // not yet read
            if($ch == $closeBrace) {
                $braces--;
                $braces = $this->checkForMissingCloseParen(braces);
                if( $braces != 0 ) {
                    $out->write($ch);
                }
            } else if( $ch == $openBrace ) {
                $braces++;
                $out->write($ch);
            } else if( $ch == '\\' ) {
                //patched by ram
                $next = chr($this->seqSource->read());
                switch($next) {
                    case 'n': $out->write('\n'); break;
                    case 'r': $out->write('\r'); break;
                    case 't': $out->write('\t'); break;
                    case 'b': $out->write('\b'); break;
                    case 'f': $out->write('\f'); break;
                    case ')':
                        // PDFBox 276 /Title (c:\)
                        $braces = $this->checkForMissingCloseParen($braces);
                        if( $braces != 0 ) {
                            $out->write($next);
                        } else {
                            $out->write('\\');
                        }
                        break;
                    case '(':
                    case '\\':
                        $out->write($next);
                        break;
                    case ASCII_LF:
                    case ASCII_CR:
                        //this is a break in the line so ignore it and the newline and continue
                        $c = $this->seqSource->read();
                        while( $this->isEOL($c) && $c != -1) {
                            $c = $this->seqSource->read();
                        }
                        $nextc = $c;
                        break;
                    case '0':
                    case '1':
                    case '2':
                    case '3':
                    case '4':
                    case '5':
                    case '6':
                    case '7':
                    {
                        $octal = new StringBuffer();
                        $octal->append( $next );
                        $c = $this->seqSource->read();
                        $digit = chr($c);
                        if( $digit >= '0' && $digit <= '7' ) {
                            $octal->append( $digit );
                            $c = $this->seqSource->read();
                            $digit = chr($c);
                            if( $digit >= '0' && $digit <= '7' ) {
                                $octal->append( $digit );
                            } else {
                                $nextc = $c;
                            }
                        } else {
                            $nextc = $c;
                        }
    
                        $character = 0;
                        try {
                            $character = octdec($octal);
                        } catch( Exception $e ) {
                            throw new Exception( "Error: Expected octal character, actual='".$octal."'" );
                        }
                        $out->write($character);
                        break;
                    }
                    default:
                    {
                        // dropping the backslash
                        // see 7.3.4.2 Literal Strings for further information
                        $out->write($next);
                    }
                }
            }
            else
            {
                $out->write($ch);
            }
            if ($nextc != -2)
            {
                $c = $nextc;
            }
            else
            {
                $c = $this->seqSource->read();
            }
        }
        if ($c != -1)
        {
            $this->seqSource->unread($c);
        }
        return new COSString($out->toByteArray());
    }
    /**
     * This will parse a PDF HEX string with fail fast semantic
     * meaning that we stop if a not allowed character is found.
     * This is necessary in order to detect malformed input and
     * be able to skip to next object start.
     *
     * We assume starting '&lt;' was already read.
     * 
     * @return The parsed PDF string.
     *
     * @throws IOException If there is an error reading from the stream.
     */
    private function parseCOSHexString() {
        $sBuf = "";
        while( true ) {
            $c = $this->seqSource->read();
			if ($c < 0) {
				throw new Exception( "Missing closing bracket for hex string. Reached EOS." );
				return null;
			}
			$c = chr($c);
            if ( $this->isHexDigit($c) ) {
                $sBuf.=$c;
            } elseif ( c == '>' ) {
                break;
            } elseif ( ( $c == ' ' ) || ( $c == '\n' ) ||
                    ( $c == '\t' ) || ( $c == '\r' ) ||
                    ( $c == '\b' ) || ( $c == '\f' ) ) {
                continue;
            } else {
                // if invalid chars was found: discard last
                // hex character if it is not part of a pair
                if (strlen($sBuf)%2!=0) {
					$sBuf = substr($sBuf,0,-1);
                }               
                // read till the closing bracket was found
                do {
                    $c = $this->seqSource->read();
                } while ( $c != ord('>') && $c >= 0 );
                // might have reached EOF while looking for the closing bracket
                // this can happen for malformed PDFs only. Make sure that there is
                // no endless loop.
                if ( $c < 0 ) {
                    throw new Exception( "Missing closing bracket for hex string. Reached EOS." );
                }
                // exit loop
                break;
            }
        }
        return COSString::parseHex($sBuf);
    }
    /**
     * This will parse a PDF array object.
     *
     * @return The parsed PDF array.
     *
     * @throws IOException If there is an error parsing the stream.
     */
    protected function parseCOSArray() {
        $this->readExpectedChar('[');
        $po = new COSArray();
        $this->skipSpaces();
        $i=-1;
        while( (($i = $this->seqSource->peek()) > 0) && (chr($i) != ']') ) {
            $pbo = $this->parseDirObject();
            if( $pbo instanceof COSObject ) {
                // We have to check if the expected values are there or not PDFBOX-385
                if ($po->get($po->size()-1) instanceof COSInteger) {
                    $genNumber = $po->remove( $po->size() -1 );
                    if ($po->get($po->size()-1) instanceof COSInteger) {
                        $number = $po->remove( $po->size() -1 );
                        $key = new COSObjectKey($number->longValue(), $genNumber->intValue());
                        $pbo = $this->getObjectFromPool($key);
                    } else {
                        // the object reference is somehow wrong
                        $pbo = null;
                    }
                } else {
                    $pbo = null;
                }
            }
            if( $pbo != null ) {
                $po->add( $pbo );
            } else {
                //it could be a bad object in the array which is just skipped
                print("Corrupt object reference at offset ".$this->seqSource->getPosition());
                // This could also be an "endobj" or "endstream" which means we can assume that
                // the array has ended.
                $isThisTheEnd = $this->readString();
                $this->seqSource->unread($isThisTheEnd);
                if(ENDOBJ_STRING==$isThisTheEnd || ENDSTREAM_STRING==$isThisTheEnd) {
                    return $po;
                }
            }
            $this->skipSpaces();
        }
        // read ']'
        $this->seqSource->read(); 
        $this->skipSpaces();
        return $po;
    }
    /**
     * Determine if a character terminates a PDF name.
     *
     * @param ch The character
     * @return true if the character terminates a PDF name, otherwise false.
     */
    protected function isEndOfName($ch) {
		if (is_integer($ch))
        return ch == ASCII_SPACE || ch == ASCII_CR || ch == ASCII_LF || ch == 9 || ch == 62 ||
               ch == 60 || ch == 91 || ch ==47 || ch ==93 || ch ==41 || ch ==40;
		if (is_string($ch))
		return ch == ' ' || ch == "\r" || ch == "\n" || ch == "\t" || ch == '>' ||
               ch == '<' || ch == '[' || ch =='/' || ch ==']' || ch ==')' || ch =='(';
		return false;
    }
    /**
     * This will parse a PDF name from the stream.
     *
     * @return The parsed PDF name.
     * @throws IOException If there is an error reading from the stream.
     */
    protected function parseCOSName() {
        $this->readExpectedChar('/');
        $buffer = new ByteArrayOutputStream();
        $c = $this->seqSource->read();
        while ($c != -1) {
            $ch = chr($c);
            if (ch == '#') {
                $ch1 = chr($this->seqSource->read());
                $ch2 = chr($this->seqSource->read());
                // Prior to PDF v1.2, the # was not a special character.  Also,
                // it has been observed that various PDF tools do not follow the
                // spec with respect to the # escape, even though they report
                // PDF versions of 1.2 or later.  The solution here is that we
                // interpret the # as an escape only when it is followed by two
                // valid hex digits.
                if ($this->isHexDigit($ch1) && $this->isHexDigit($ch2)) {
                    $hex = $ch1.$ch2;
                    $buffer->write(hexdec($hex));
                    $c = $this->seqSource->read();
                } else {
                    $this->seqSource->unread($ch2);
                    $c = $ch1;
                    $buffer->write($ch);
                }
            } elseif ($this->isEndOfName($ch)) {
                break;
            } else {
                $buffer->write($ch);
                $c = $this->seqSource->read();
            }
        }
        if ($c != -1) {
            $seqSource->unread($c);
        }
        $string = $buffer->toByteArray();
        return COSName::getPDFName($string);
    }
    /**
     * This will parse a boolean object from the stream.
     *
     * @return The parsed boolean object.
     *
     * @throws IOException If an IO error occurs during parsing.
     */
    protected function parseBoolean() {
        $retval = null;
        $c = chr($this->seqSource->peek());
        if( $c == 't' )  {
            $trueString = $this->seqSource->readFully( 4 );
            if( $trueString!=TRUE ) {
                throw new Exception( "Error parsing boolean: expected='true' actual='".$trueString. 
                        "' at offset ".$this->seqSource->getPosition());
            } else {
                $retval = COSBoolean::TRUE;
            }
        } else if( $c == 'f' ) {
            $falseString = $this->seqSource->readFully( 5 );
            if( $falseString!=FALSE  ) {
                throw new Exception( "Error parsing boolean: expected='true' actual='".$falseString.
                        "' at offset ".$this->seqSource->getPosition());
            } else {
                $retval = COSBoolean::FALSE;
            }
        } else {
            throw new Exception( "Error parsing boolean expected='t or f' actual='".$c.
                    "' at offset ".$this->seqSource->getPosition());
        }
        return $retval;
    }
    /**
     * This will parse a directory object from the stream.
     *
     * @return The parsed object.
     *
     * @throws IOException If there is an error during parsing.
     */
    protected function parseDirObject() {
        $retval = null;
        $this->skipSpaces();
        $nextByte = $this->seqSource->peek();
        $c = chr($nextByte);
        switch($c) {
        case '<':
            // pull off first left bracket
            $leftBracket = $this->seqSource->read();
            // check for second left bracket
            $c = chr($this->seqSource->peek());
            $this->seqSource->unread($leftBracket);
            if($c == '<') {
                $retval = $this->parseCOSDictionary();
                $this->skipSpaces();
            } else {
                $retval = $this->parseCOSString();
            }
            break;
        case '[':
            // array
            $retval = $this->parseCOSArray();
            break;
        case '(':
            $retval = $this->parseCOSString();
            break;
        case '/':   
            // name
            $retval = $this->parseCOSName();
            break;
        case 'n':   
            // null
            $this->readExpectedString(NULL);
            $retval = COSNull::NULL;
            break;
        case 't':
            $trueString = $this->seqSource->readFully(4);
            if( $trueString==TRUE  ) {
                $retval = COSBoolean::TRUE;
            } else {
                throw new Exception( "expected true actual='$trueString' ".$this->seqSource->toString(). 
                        "' at offset ".$this->seqSource->getPosition());
            }
            break;
        case 'f':
            $falseString = $this->seqSource->readFully(5);
            if( $falseString==FALSE  ) {
                $retval = COSBoolean::FALSE;
            } else {
                throw new Exception( "expected false actual='$falseString' ".$this->seqSource->toString(). 
                        "' at offset ".$this->seqSource->getPosition());
            }
            break;
        case 'R':
            $this->seqSource->read();
            $retval = new COSObject(null);
            break;
        case -1:
            return null;
        default:
            if( ctype_digit($c) || $c == '-' || $c == '+' || $c == '.') {
                $buf = "";
                $ic = $this->seqSource->read();
                $c = chr($ic);
                while( ctype_digit( $c )||
                        $c == '-' ||
                        $c == '+' ||
                        $c == '.' ||
                        $c == 'E' ||
                        $c == 'e' )
                {
                    $buf.=$c;
                    $ic = $this->seqSource->read();
                    $c = chr($ic);
                }
                if( $ic != -1 ) {
                    $this->seqSource->unread($ic);
                }
                $retval = COSNumber::get( $buf );
            } else {
                //This is not suppose to happen, but we will allow for it
                //so we are more compatible with POS writers that don't
                //follow the spec
                $badString = $this->readString();
                if( is_null($badString) || strlen($badString) == 0 ) {
                    $peek = $this->seqSource->peek();
                    // we can end up in an infinite loop otherwise
                    throw new Exception( "Unknown dir object c='".$c.
                            "' cInt=".ord($c)." peek='".chr($peek).
                            "' peekInt=".$peek." ".$this->seqSource->getPosition() );
                }
                // if it's an endstream/endobj, we want to put it back so the caller will see it
                if($badString==ENDOBJ_STRING || $badString==ENDSTREAM_STRING) {
                    $this->seqSource->unread($badString);
                }
            }
        }
        return $retval;
    }
    /**
     * Reads given pattern from {@link #seqSource}. Skipping whitespace at start and end if wanted.
     * 
     * @param expectedString pattern to be skipped
     * @param skipSpaces if set to true spaces before and after the string will be skipped
     * @throws IOException if pattern could not be read
     */
    protected final function readExpectedString($expectedString,$skipSpaces=false) {
		if (!is_string($expectedString) || !is_bool($skipSpaces)) return;
        if ($skipSpaces) $this->skipSpaces();
        for ($i=0;$i<strlen(expectedString);$i++) {
            if ($this->seqSource->read() != ord(substr($expectedString,$i,1))) {
                throw new Exception("Expected string '".$expectedString
					."' but missed at character '".substr($expectedString,$i,1)."' at offset "
                    .$this->seqSource->getPosition());
            }
        }
        if($skipSpaces) $this->skipSpaces();
    }
    /**
     * Read one char and throw an exception if it is not the expected value.
     *
     * @param ec the char value that is expected.
     * @throws IOException if the read char is not the expected value or if an
     * I/O error occurs.
     */
    protected function readExpectedChar($ec) {
        $c = $this->seqSource->read();
        if ((is_string($ec) && $ec!=chr($c)) || (is_integer($ec) && $ec!=$c)) {
            throw new Exception("expected='$ec' actual='$c' at offset ".$this->seqSource->getPosition());
        }
    }
    /**
     * This will read the next string from the stream up to a certain length.
     *
     * @param length The length to stop reading at.
     *
     * @return The string that was read from the stream of length 0 to length.
     *
     * @throws IOException If there is an error reading from the stream.
     */
    protected function readString( $length=-1 ) {
		if (!is_integer($length)) return;
        $this->skipSpaces();
        $c = chr($this->seqSource->read());
        //average string size is around 2 and the normal string buffer size is
        //about 16 so lets save some space.
        $buffer = "";
		if ($length>-1) {
			while( !ctype_space($c) && !$this->isClosing($c) && ord($c) != 255 && strlen($buffer) < $length &&
					$c != '[' &&
					$c != '<' &&
					$c != '(' &&
					$c != '/' )
			{
				$buffer .= $c;
				$c = chr($this->seqSource->read());
			}
		} else {
			$c = ord($c);
			while( !$this->isEndOfName($c) && $c != -1 && $c != 255) {
				$buffer.=chr($c);
				$c = $this->seqSource->read();
			}
		}
        if ((is_integer($c) && $c!=-1) || (ord($c) != 255)) {
            $this->seqSource->unread($c);
        }
        return $buffer;
    }
    /**
     * This will tell if the next character is a closing brace( close of PDF array ).
     *
     * @param c The character to check against end of line
     * @return true if the next byte is ']', false otherwise.
     */
    protected function isClosing($c=null) {
		if (is_null($c)) $c=$this->seqSource->peek();
		if (is_string($c)) return $c == ']';
		elseif (is_integer($c)) return chr($c) == ']';
		else return false;
    }
    /**
     * This will read bytes until the first end of line marker occurs.
     * NOTE: The EOL marker may consists of 1 (CR or LF) or 2 (CR and CL) bytes
     * which is an important detail if one wants to unread the line.
     *
     * @return The characters between the current position and the end of the line.
     *
     * @throws IOException If there is an error reading from the stream.
     */
    protected function readLine() {
        if ($this->seqSource->isEOF()) {
            throw new Exception( "Error: End-of-File, expected line");
        }
        $buffer = "";
        while (($c = $this->seqSource->read()) != -1) {
            // CR and LF are valid EOLs
            if ($c==10 || $c==13) {
                break;
            }
            $buffer .= chr($c);
        }
        // CR+LF is also a valid EOL 
        if ($c==13 && $this->seqSource->peek()==10) {
            $this->seqSource->read();
        }
        return $buffer;
    }
    /**
     * This will skip all spaces and comments that are present.
     *
     * @throws IOException If there is an error reading from the stream.
     */
    protected function skipSpaces() {
        $c = $this->seqSource->read();
        // 37 is the % character, a comment
        while( ctype_space(chr($c)) || c == 37) {
            if ( $c == 37 ) {
                // skip past the comment section
                $c = $this->seqSource->read();
                while($c!=10 && $c!=13 && $c != -1) {
                    $c = $this->seqSource->read();
                }
            } else {
                $c = $this->seqSource->read();
            }
        }
        if ($c != -1) {
            $this->seqSource->unread($c);
        }
    }
    /**
     * This will read a long from the Stream and throw an {@link IOException} if
     * the long value is negative or has more than 10 digits (i.e. : bigger than
     * {@link #OBJECT_NUMBER_THRESHOLD})
     *
     * @return the object number being read.
     * @throws IOException if an I/O error occurs
     */
    protected function readObjectNumber() {
        $retval = $this->readLong();
        if ($retval < 0 || $retval >= $this->OBJECT_NUMBER_THRESHOLD) {
            throw new Exception("Object Number '$retval' has more than 10 digits or is negative");
        }
        return $retval;
    }
    /**
     * This will read a integer from the Stream and throw an {@link IllegalArgumentException} if the integer value
     * has more than the maximum object revision (i.e. : bigger than {@link #GENERATION_NUMBER_THRESHOLD})
     * @return the generation number being read.
     * @throws IOException if an I/O error occurs
     */
    protected function readGenerationNumber() {
        $retval = $this->readInt();
        if($retval < 0 || $retval > $this->GENERATION_NUMBER_THRESHOLD) {
            throw new Exception("Generation Number '$retval' has more than 5 digits");
        }
        return $retval;
    }
    /**
     * This will read an integer from the stream.
     *
     * @return The integer that was read from the stream.
     *
     * @throws IOException If there is an error reading from the stream.
     */
    protected function readInt() {
        $this->skipSpaces();
        $retval = 0;
        $intBuffer = $this->readStringNumber();
        try {
            $retval = 0 + $intBuffer;
        } catch( Exception $e ) {
            $this->seqSource->unread($intBuffer);
            throw new Exception( "Error: Expected an integer type at offset ".$this->seqSource->getPosition());
        }
        return $retval;
    }
    /**
     * This will read an long from the stream.
     *
     * @return The long that was read from the stream.
     *
     * @throws IOException If there is an error reading from the stream.
     */
    protected function readLong() {
        $this->skipSpaces();
        $retval = 0;
        $longBuffer = $this->readStringNumber();
        try {
            $retval = 0 + $longBuffer;
        } catch( Exception $e ) {
            $this->seqSource->unread($longBuffer);
            throw new Exception( "Error: Expected a long type at offset "
                    .$this->seqSource->getPosition().", instead got '$longBuffer'");
        }
        return $retval;
    }
    /**
     * This method is used to read a token by the {@linkplain #readInt()} method
     * and the {@linkplain #readLong()} method.
     *
     * @return the token to parse as integer or long by the calling method.
     * @throws IOException throws by the {@link #seqSource} methods.
     */
    protected final function readStringNumber() {
        $lastByte = 0;
        $buffer = "";
        while( ($lastByte = $this->seqSource->read() ) != ASCII_SPACE &&
                $lastByte != ASCII_LF &&
                $lastByte != ASCII_CR &&
                $lastByte != 60 && //see sourceforge bug 1714707
                chr($lastByte) != '[' && // PDFBOX-1845
                chr($lastByte) != '(' && // PDFBOX-2579
                $lastByte != 0 && //See sourceforge bug 853328
                $lastByte != -1 )
        {
            $buffer .= chr($lastByte);
        }
        if( $lastByte != -1 ) {
            $this->seqSource->unread($lastByte);
        }
        return $buffer;
    }
}
?>