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
require_once('COSBase.php');
require_once('../io/ScratchFile.php');
require_once('../pdfparser/PDFObjectStreamParser.php');
/**
 * This is the in-memory representation of the PDF document.  You need to call
 * close() on this object when you are done using it!!
 *
 * @author <a href="ben@benlitchfield.com">Ben Litchfield</a>
 * 
 */
class COSDocument extends COSBase {
	private $version = 1.4;
    /**
     * Log instance.
     */
	private $LOG;
    /**
     * Maps ObjectKeys to a COSObject. Note that references to these objects
     * are also stored in COSDictionary objects that map a name to a specific object.
     */
	private $objectPool = array();	// hashmap
    /**
     * Maps object and generation id to object byte offsets.
     */
 	private $xrefTable = array();	// hashmap
    /**
     * Document trailer dictionary.
     */
	private $trailer;	// COSDictionary
    /**
     * Signature interface.
     */
	private $signatureInterface;	// SignatureInterface
    /**
     * This file will store the streams in order to conserve memory.
     */
	private $scratchFile;	// File (RandomAccess)
	private $tmpFile;		// File
	private $headerString;
	private $warnMissingClose = true;	// boolean
	 /** signal that document is already decrypted, e.g. with {@link NonSequentialPDFParser} */
	private $isDecrypted = false;	// boolean	   
	private $startXref;			// long
	private $closed = false;	// boolean
    /**
     * Flag to skip malformed or otherwise unparseable input where possible.
     */
	private $forceParsing;		// boolean
	
    /**
     * Constructor that will use a temporary file in the given directory
     * for storage of the PDF streams. The temporary file is automatically
     * removed when this document gets closed.
     *
     * @param path directory for the temporary file,
     *                   or <code>null</code> to use the system default
     * @param file the random access file to use for storage
     * @param forceParsingValue flag to skip malformed or otherwise unparseable
     *                     document content where possible
     * @throws IOException if something went wrong
     */
	public function __construct($path='',$file='',$forceParsingValue=false) {
		$this->headerString = "%PDF-$version";
	}
    /**
     * visitor pattern double dispatch method.
     *
     * @param visitor The object to notify when visiting this object.
     * @return any object, depending on the visitor implementation, or null
     * @throws COSVisitorException If an error occurs while visiting this object.
     */
	public function accept($visitor) {
		if (!($visitor instanceof ICOSVisitor)) return;
		
	}
    /**
     * This will print contents to stdout. (Changed from Java version's 'print' method)
     */
	public function cout() {
	
	}
    /**
     * This will close all storage and delete the tmp files.
     *
     *  @throws IOException If there is an error close resources.
     */
	public function close() {
	
	}
    /**
     * Warn the user in the finalizer if he didn't close the PDF document. The method also
     * closes the document just in case, to avoid abandoned temporary files. It's still a good
     * idea for the user to close the PDF document at the earliest possible to conserve resources.
     * @throws IOException if an error occurs while closing the temporary files
     */
	protected function finalize() {
	
	}
    /**
     * This will get the scratch file for this document.
     *
     * @return The scratch file.
     * 
     * @deprecated direct access to the scratch file will be removed
     */
	public function getScratchFile() {
	
	}
    /**
     * Create a new COSStream using the underlying scratch file.
     *
     * @param dictionary the corresponding dictionary
     * 
     * @return the new COSStream
     */
	public function createCOSStream($dictionary=null) {
		if ($dictionary==null) $dictionary = getScratchFile();
		if (!($dictionary instanceof COSDictionary)) return;
		
	}
    /**
     * This will get the first dictionary object by type.
     *
     * @param type The type of the object.
     *
     * @return This will return an object with the specified type.
     * @throws IOException If there is an error getting the object
     */
	public function getObjectByType($type) {
	
	}
    /**
     * This will get a dictionary object by type.
     *
     * @param type The type of the object.
     *
     * @return This will return an object with the specified type.
     * @throws IOException If there is an error getting the object
     */
	public function getObjectsByType($type) {
	
	}
    /**
     * This will set the version of this PDF document.
     *
     * @param versionValue The version of the PDF document.
     */
	public function setVersion($versionValue) {
		if (!is_float($versionValue)) return;
	}
    /**
     * This will get the version of this PDF document.
     *
     * @return This documents version.
     */
	public function getVersion() {
	
	}
    /** 
     * Signals that the document is decrypted completely.
     *  Needed e.g. by {@link org.apache.pdfbox.pdfparser.NonSequentialPDFParser} to circumvent
     *  additional decryption later on.
     */
	public function setDecrypted() {
	
	}
    /** 
     * Indicates if a encrypted pdf is already decrypted after parsing.
     * Does make sense only if the {@link org.apache.pdfbox.pdfparser.NonSequentialPDFParser} is used.
     * 
     *  @return true indicates that the pdf is decrypted.
     */
	public function isDecrypted() {
	
	}
    /**
     * This will tell if this is an encrypted document.
     *
     * @return true If this document is encrypted.
     */
	public function isEncrypted() {
	
	}
    /**
     * This will set the encryption dictionary, this should only be called when
     * encrypting the document.
     *
     * @param encDictionary The encryption dictionary.
     */
	public function setEncryptionDictionary($encDictionary) {
		if (!($encDictionary instanceof COSDictionary)) return;
		
	}
    /**
     * This will get the encryption dictionary if the document is encrypted or null
     * if the document is not encrypted.
     *
     * @return The encryption dictionary.
     */
	public function getEncryptionDictionary() {
	
	}
    /**
     * Set the signature interface to the given value.
     * @param sigInterface the signature interface
     */
	public function setSignatureInterface($sigInterface) {
		if (!($sigInterface instanceof SignatureInterface)) return;
		
	}
    /**
     * This will return the signature interface.
     * @return the signature interface 
     */
	public function getSignatureInterface() {
	
	}
    /**
     * This will return a list of signature dictionaries as COSDictionary.
     *
     * @return list of signature dictionaries as COSDictionary
     * @throws IOException if no document catalog can be found
     */
	public function getSignatureDictionaries() {
	
	}
    /**
     * This will return a list of signature fields.
     *
     * @param onlyEmptyFields only empty signature fields will be returned
     * @return list of signature dictionaries as COSDictionary
     * @throws IOException if no document catalog can be found
     */
	public function getSignatureFields($onlyEmptyFields) {
		if (!is_bool($onlyEmtpyFields)) return null;
		
	}
    /**
     * This will set the document ID.
     *
     * @param id The document id.
     */
	public function setDocumentId($id) {
		if (!($id instanceof COSArray)) return;
		
	}
    /**
     * This will get the document ID.
     *
     * @return The document id.
     */
	public function getDocumentId() {
	
	}
    /**
     * This will get the document catalog.
     *
     * Maybe this should move to an object at PDFEdit level
     *
     * @return catalog is the root of all document activities
     *
     * @throws IOException If no catalog can be found.
     */
	public function getCatalog() {
	
	}
    /**
     * This will get a list of all available objects.
     *
     * @return A list of all objects.
     */
	public function getObjects() {
	
	}
    /**
     * // MIT added, maybe this should not be supported as trailer is a persistence construct.
     * This will set the document trailer.
     *
     * @param newTrailer the document trailer dictionary
     */
	public function setTrailer($newTrailer) {
		if (!($newTrailer instanceof COSDictionary)) return;
	}
    /**
     * This will get the document trailer.
     *
     * @return the document trailer dict
     */
	public function getTrailer() {
	
	}
    /**
     * Controls whether this instance shall issue a warning if the PDF document wasn't closed
     * properly through a call to the {@link #close()} method. If the PDF document is held in
     * a cache governed by soft references it is impossible to reliably close the document
     * before the warning is raised. By default, the warning is enabled.
     * @param warn true enables the warning, false disables it.
     */
	public function setWarnMissingClose($warn) {
		if (!is_bool($warn)) return;
		
	}
    /**
     * @param header The headerString to set.
     */
	public function setHeaderString($header) {
		if (!is_string($header)) return;
		
	}
    /**
     * @return Returns the headerString.
     */
	public function getHeaderString() {
	
	}
    /**
     * This method will search the list of objects for types of ObjStm.  If it finds
     * them then it will parse out all of the objects from the stream that is contains.
     *
     * @throws IOException If there is an error parsing the stream.
     */
	public function dereferenceObjectStreams() {
	
	}
    /**
     * This will get an object from the pool.
     *
     * @param key The object key.
     *
     * @return The object in the pool or a new one if it has not been parsed yet.
     *
     * @throws IOException If there is an error getting the proxy object.
     */
	public function getObjectFromPool($key) {
		if (!($key instanceof COSObjectKey)) return null;
	
	}
    /**
     * Removes an object from the object pool.
     * @param key the object key
     * @return the object that was removed or null if the object was not found
     */
	public function removeObject($key) {
		if (!($key instanceof COSObjectKey)) return null;
		
	}
    /**
     * Populate XRef HashMap with given values.
     * Each entry maps ObjectKeys to byte offsets in the file.
     * @param xrefTableValues  xref table entries to be added
     */
	public function addXrefTable($xrefTableValues) {
		if (!is_array($xrefTableValues)) return;
		
	}
    /**
     * Returns the xrefTable which is a mapping of ObjectKeys
     * to byte offsets in the file.
     * @return mapping of ObjectsKeys to byte offsets
     */
	public function getXrefTable() {
	
	}
    /**
     * This method set the startxref value of the document. This will only 
     * be needed for incremental updates.
     * 
     * @param startXrefValue the value for startXref
     */
	public function setStartXref($startXrefValue) {
		if (!is_numeric($startXrefValue)) return;
		
	}
    /**
     * Return the startXref Position of the parsed document. This will only be needed for incremental updates.
     * 
     * @return a long with the old position of the startxref
     */
	public function getStartXref() {
	
	}
    /**
     * Determines it the trailer is a XRef stream or not.
     * 
     * @return true if the trailer is a XRef stream
     */
	public function isXRefStream() {
	
	}
}
?>