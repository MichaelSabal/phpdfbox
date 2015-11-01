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
require_once('../cos/COSBase.php');
require_once('../cos/COSDictionary.php');
require_once('../cos/COSDocument.php');
require_once('../cos/COSName.php');
require_once('../io/IOUtils.php');
require_once('../io/RandomAccessRead.php');
require_once('../io/ScratchFile.php');
require_once('../pdmodel/PDDocument.php');
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
    public PDDocument getPDDocument() throws IOException
    {
        return new PDDocument( getDocument(), source, accessPermission );
    }

    /**
     * The initial parse will first parse only the trailer, the xrefstart and all xref tables to have a pointer (offset)
     * to all the pdf's objects. It can handle linearized pdfs, which will have an xref at the end pointing to an xref
     * at the beginning of the file. Last the root object is parsed.
     * 
     * @throws IOException If something went wrong.
     */
    protected void initialParse() throws IOException
    {
        COSDictionary trailer = null;
        // parse startxref
        long startXRefOffset = getStartxrefOffset();
        if (startXRefOffset > -1)
        {
            trailer = parseXref(startXRefOffset);
        }
        else if (isLenient())
        {
            trailer = rebuildTrailer();
        }
        // prepare decryption if necessary
        prepareDecryption();
    
        parseTrailerValuesDynamically(trailer);
    
        COSObject catalogObj = document.getCatalog();
        if (catalogObj != null && catalogObj.getObject() instanceof COSDictionary)
        {
            parseDictObjects((COSDictionary) catalogObj.getObject(), (COSName[]) null);
            document.setDecrypted();
        }
        initialParseDone = true;
    }

    /**
     * This will parse the stream and populate the COSDocument object.  This will close
     * the stream when it is done parsing.
     *
     * @throws IOException If there is an error reading from the stream or corrupt data
     * is found.
     */
    public void parse() throws IOException
    {
         // set to false if all is processed
         boolean exceptionOccurred = true; 
         try
         {
            // PDFBOX-1922 read the version header and rewind
            if (!parsePDFHeader() && !parseFDFHeader())
            {
                throw new IOException( "Error: Header doesn't contain versioninfo" );
            }
    
            if (!initialParseDone)
            {
                initialParse();
            }
            exceptionOccurred = false;
        }
        finally
        {
            IOUtils.closeQuietly(keyStoreInputStream);
    
            if (exceptionOccurred && document != null)
            {
                IOUtils.closeQuietly(document);
                document = null;
            }
        }
    }

    /**
     * Prepare for decryption.
     * 
     * @throws IOException if something went wrong
     */
    private void prepareDecryption() throws IOException
    {
        COSBase trailerEncryptItem = document.getTrailer().getItem(COSName.ENCRYPT);
        if (trailerEncryptItem != null && !(trailerEncryptItem instanceof COSNull))
        {
            if (trailerEncryptItem instanceof COSObject)
            {
                COSObject trailerEncryptObj = (COSObject) trailerEncryptItem;
                parseDictionaryRecursive(trailerEncryptObj);
            }
            try
            {
                PDEncryption encryption = new PDEncryption(document.getEncryptionDictionary());
    
                DecryptionMaterial decryptionMaterial;
                if (keyStoreInputStream != null)
                {
                    KeyStore ks = KeyStore.getInstance("PKCS12");
                    ks.load(keyStoreInputStream, password.toCharArray());
    
                    decryptionMaterial = new PublicKeyDecryptionMaterial(ks, keyAlias, password);
                }
                else
                {
                    decryptionMaterial = new StandardDecryptionMaterial(password);
                }
    
                securityHandler = encryption.getSecurityHandler();
                securityHandler.prepareForDecryption(encryption, document.getDocumentID(),
                        decryptionMaterial);
                accessPermission = securityHandler.getCurrentAccessPermission();
            }
            catch (IOException e)
            {
                throw e;
            }
            catch (Exception e)
            {
                throw new IOException("Error (" + e.getClass().getSimpleName()
                        + ") while creating security handler for decryption", e);
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
    private void parseDictionaryRecursive(COSObject dictionaryObject) throws IOException
    {
        parseObjectDynamically(dictionaryObject, true);
        COSDictionary dictionary = (COSDictionary)dictionaryObject.getObject();
        for(COSBase value : dictionary.getValues())
        {
            if (value instanceof COSObject)
            {
                COSObject object = (COSObject)value;
                if (object.getObject() == null)
                {
                    parseDictionaryRecursive(object);
                }
            }
        }
    }

}
?>