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
$cwd = getcwd();
chdir(__DIR__);
require_once('../cos/COSBase.php');
require_once('COSParser.php');
require_once('../cos/COSDictionary.php');
require_once('../cos/COSDocument.php');
require_once('../cos/COSName.php');
require_once('../io/IOUtils.php');
//require_once('../io/RandomAccessRead.php');
require_once('../io/ScratchFile.php');
//require_once('../pdmodel/PDDocument.php');
chdir($cwd);
/*
import org.apache.pdfbox.pdmodel.encryption.AccessPermission;
import org.apache.pdfbox.pdmodel.encryption.DecryptionMaterial;
import org.apache.pdfbox.pdmodel.encryption.PDEncryption;
import org.apache.pdfbox.pdmodel.encryption.PublicKeyDecryptionMaterial;
import org.apache.pdfbox.pdmodel.encryption.StandardDecryptionMaterial;
*/
class PDFParser extends COSParser {
    private $password = "";	// string
    private $keyStoreInputStream = null;	// InputStream
    private $keyAlias = null;	// string

    private $accessPermission;	// accessPermission

    /**
     * Constructor.
     * 
     * @param source input representing the pdf.
     * @param decryptionPassword password to be used for decryption.
     * @param keyStore key store to be used for decryption when using public key security 
     * @param alias alias to be used for decryption when using public key security
     * @param scratchFile buffer handler for temporary storage; it will be closed on
     *        {@link COSDocument#close()}
     *
     * @throws IOException If something went wrong.
     */
    public function __construct($source, $decryptionPassword="", $keyStore=null,
                     $alias=null, $scratchFile=false) {
		if (!($source instanceof RandomAccessRead)) return;
        super($source);
		$this->password = "";
		if (is_string($decryptionPassword)) $this->password = $decryptionPassword;
        $this->fileLen = $source->length();
        if ($keyStore instanceof InputStream) $keyStoreInputStream = $keyStore;
        if (is_string($alias)) $keyAlias = $alias;
        $this->init($scratchFile);
    }
    
    private function init($scratchFile) {
		$eofLookupRangeStr = System.getProperty(SYSPROP_EOFLOOKUPRANGE);
		if (!is_null($eofLookupRangeStr) && ctype_integer($eofLookupRangeStr)) {
			$this->setEOFLookupRange($eofLookupRangeStr);
		}
		$this->document = new COSDocument($scratchFile);
    }
    /**
     * This will get the PD document that was parsed.  When you are done with
     * this document you must call close() on it to release resources.
     *
     * @return The document at the PD layer.
     *
     * @throws IOException If there is an error getting the document.
     */
    public function getPDDocument() {
        return new PDDocument( $this->getDocument(), $this->source, $this->accessPermission );
    }

    /**
     * The initial parse will first parse only the trailer, the xrefstart and all xref tables to have a pointer (offset)
     * to all the pdf's objects. It can handle linearized pdfs, which will have an xref at the end pointing to an xref
     * at the beginning of the file. Last the root object is parsed.
     * 
     * @throws IOException If something went wrong.
     */
    protected function initialParse() {
        $trailer = null;
        // parse startxref
        $startXRefOffset = $this->getStartxrefOffset();
        if ($startXRefOffset > -1) {
            $trailer = $this->parseXref($startXRefOffset);
        } else if ($this->isLenient()) {
            $trailer = $this->rebuildTrailer();
        }
        // prepare decryption if necessary
        $this->prepareDecryption();
    
        $this->parseTrailerValuesDynamically($trailer);
    
        $catalogObj = $this->document->getCatalog();
        if (!is_null($catalogObj) && $this->catalogObj->getObject() instanceof COSDictionary) {
            $this->parseDictObjects($this->catalogObj->getObject(), null);
            $this->document->setDecrypted();
        }
        $this->initialParseDone = true;
    }

    /**
     * This will parse the stream and populate the COSDocument object.  This will close
     * the stream when it is done parsing.
     *
     * @throws IOException If there is an error reading from the stream or corrupt data
     * is found.
     */
    public function parse() {
         // set to false if all is processed
         $exceptionOccurred = true; 
         try {
            // PDFBOX-1922 read the version header and rewind
            if (!$this->parsePDFHeader() && !$this->parseFDFHeader())
            {
                throw new Exception( "Error: Header doesn't contain versioninfo" );
            }
    
            if (!$this->initialParseDone)
            {
                $this->initialParse();
            }
            $exceptionOccurred = false;
        } catch(Exception $e) {
			
		}
		IOUtils::closeQuietly($this->keyStoreInputStream);

		if ($exceptionOccurred && !is_null($this->document))
		{
			IOUtils::closeQuietly($this->document);
			$this->document = null;
		}      
    }

    /**
     * Prepare for decryption.
     * 
     * @throws IOException if something went wrong
     */
    private function prepareDecryption() {
        $trailerEncryptItem = $this->document->getTrailer()->getItem(COSName::ENCRYPT);
        if (!is_null($trailerEncryptItem)) {
            if ($trailerEncryptItem instanceof COSObject) {
                $trailerEncryptObj = $trailerEncryptItem;
                $this->parseDictionaryRecursive($trailerEncryptObj);
            }
            try {
                $encryption = new PDEncryption($this->document->getEncryptionDictionary());
    
                if (!is_null($this->keyStoreInputStream))
                {
                    $ks = KeyStore::getInstance("PKCS12");
                    $ks->load($this->keyStoreInputStream, $this->password);
                    $decryptionMaterial = new PublicKeyDecryptionMaterial($ks, $this->keyAlias, $this->password);
                } else {
                    $decryptionMaterial = new StandardDecryptionMaterial($this->password);
                }
    
                $securityHandler = $encryption->getSecurityHandler();
                $securityHandler->prepareForDecryption($encryption, $this->document->getDocumentID(),
                        $decryptionMaterial);
                $accessPermission = $securityHandler->getCurrentAccessPermission();
            }
            catch (Exception $e)
            {
                throw $e;
            }
        }
    }

    /**
     * Resolves all not already parsed objects of a dictionary recursively.
     * 
     * @param dictionaryObject dictionary to be parsed
     * @throws IOException if something went wrong
     * 
     */
    private function parseDictionaryRecursive($dictionaryObject) {
        $this->parseObjectDynamically($dictionaryObject, true);
        $dictionary = $dictionaryObject->getObject();
        foreach($dictionary->getValues() as $value)
        {
            if ($value instanceof COSObject)
            {
                $object = $value;
                if (is_null($object->getObject()))
                {
                    $this->parseDictionaryRecursive($object);
                }
            }
        }
    }

}
?>