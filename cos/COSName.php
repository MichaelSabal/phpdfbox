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
// The phpbox implementation wraps a few discrete instances around Java's builtin charset.
// PHP doesn't have a builtin Charset class, so all Java's methods would have to be wrapped 
// into this implementation.
// require_once('../util/Charsets.php'); 
require_once('COSBase.php');
/**
 * A PDF Name object.
 *
 * @author Ben Litchfield
 */
final class COSName extends COSBase {
    /**
     * Note: This is a ConcurrentHashMap because a HashMap must be synchronized if accessed by
     * multiple threads.
     */
	private $nameMap = array();
    /**
     * All common COSName values are stored in a simple HashMap. They are already defined as
     * static constants and don't need to be synchronized for multithreaded environments.
     */
	private $commonNameMap = array();

    // A
    public static $A;
    public static $AA;
    public static $ACRO_FORM;
    public static $ACTUAL_TEXT;
    public static $ADBE_PKCS7_DETACHED;
    public static $ADBE_PKCS7_SHA1;
    public static $ADBE_X509_RSA_SHA1;
    public static $ADOBE_PPKLITE;
    public static $AESV3;
    public static $AFTER;
    public static $AIS;
    public static $ALT;
    public static $ALPHA;
    public static $ALTERNATE;
    public static $ANNOT;
    public static $ANNOTS;
    public static $ANTI_ALIAS;
    public static $AP;
    public static $AP_REF;
    public static $APP;
    public static $ART_BOX;
    public static $ARTIFACT;
    public static $AS;
    public static $ASCENT;
    public static $ASCII_HEX_DECODE;
    public static $ASCII_HEX_DECODE_ABBREVIATION;
    public static $ASCII85_DECODE;
    public static $ASCII85_DECODE_ABBREVIATION;
    public static $ATTACHED;
    public static $AUTHOR;
    public static $AVG_WIDTH;
    // B
    public static $B;
    public static $BACKGROUND;
    public static $BASE_ENCODING;
    public static $BASE_FONT;
    public static $BASE_STATE;
    public static $BBOX;
    public static $BC;
    public static $BE;
    public static $BEFORE;
    public static $BG;
    public static $BITS_PER_COMPONENT;
    public static $BITS_PER_COORDINATE;
    public static $BITS_PER_FLAG;
    public static $BITS_PER_SAMPLE;
    public static $BLACK_IS_1;
    public static $BLACK_POINT;
    public static $BLEED_BOX;
    public static $BM;
    public static $BOUNDS;
    public static $BPC;
    public static $BS;
    //** Acro form field type for button fields.
    public static $BTN;
    public static $BYTERANGE;
    // C
    public static $C;
    public static $C0;
    public static $C1;
    public static $CA;
    public static $CA_NS;
    public static $CALGRAY;
    public static $CALRGB;
    public static $CAP;
    public static $CAP_HEIGHT;
    public static $CATALOG;
    public static $CCITTFAX_DECODE;
    public static $CCITTFAX_DECODE_ABBREVIATION;
    public static $CENTER_WINDOW;
    public static $CF;
    public static $CFM;
    //** Acro form field type for choice fields.
    public static $CH;
    public static $CHAR_PROCS;
    public static $CHAR_SET;
    public static $CICI_SIGNIT;
    public static $CID_FONT_TYPE0;
    public static $CID_FONT_TYPE2;
    public static $CID_TO_GID_MAP;
    public static $CID_SET;
    public static $CIDSYSTEMINFO;
    public static $CLR_F;
    public static $CLR_FF;
    public static $CMAP;
    public static $CMAPNAME;
    public static $CMYK;
    public static $CO;
    public static $COLOR_BURN;
    public static $COLOR_DODGE;
    public static $COLORANTS;
    public static $COLORS;
    public static $COLORSPACE;
    public static $COLUMNS;
    public static $COMPATIBLE;
    public static $COMPONENTS;
    public static $CONTACT_INFO;
    public static $CONTENTS;
    public static $COORDS;
    public static $COUNT;
    public static $CP;
    public static $CREATION_DATE;
    public static $CREATOR;
    public static $CROP_BOX;
    public static $CRYPT;
    public static $CS;
    // D
    public static $D;
    public static $DA;
    public static $DARKEN;
    public static $DATE;
    public static $DCT_DECODE;
    public static $DCT_DECODE_ABBREVIATION;
    public static $DECODE;
    public static $DECODE_PARMS;
    public static $DEFAULT;
    public static $DEFAULT_CMYK;
    public static $DEFAULT_GRAY;
    public static $DEFAULT_RGB;
    public static $DESC;
    public static $DESCENDANT_FONTS;
    public static $DESCENT;
    public static $DEST;
    public static $DEST_OUTPUT_PROFILE;
    public static $DESTS;
    public static $DEVICECMYK;
    public static $DEVICEGRAY;
    public static $DEVICEN;
    public static $DEVICERGB;
    public static $DI;
    public static $DIFFERENCE;
    public static $DIFFERENCES;
    public static $DIGEST_METHOD;
    public static $DIGEST_RIPEMD160;
    public static $DIGEST_SHA1;
    public static $DIGEST_SHA256;
    public static $DIGEST_SHA384;
    public static $DIGEST_SHA512;
    public static $DIRECTION;
    public static $DISPLAY_DOC_TITLE;
    public static $DL;
    public static $DM;
    public static $DOC;
    public static $DOC_CHECKSUM;
    public static $DOC_TIME_STAMP;
    public static $DOMAIN;
    public static $DOS;
    public static $DP;
    public static $DR;
    public static $DS;    
    public static $DUPLEX;
    public static $DUR;
    public static $DV;
    public static $DW;
    public static $DW2;
    // E
    public static $E;
    public static $EARLY_CHANGE;
    public static $EF;
    public static $EMBEDDED_FDFS;
    public static $EMBEDDED_FILES;
    public static $EMPTY;
    public static $ENCODE;
    public static $ENCODED_BYTE_ALIGN;    
    public static $ENCODING;
    public static $ENCODING_90MS_RKSJ_H;
    public static $ENCODING_90MS_RKSJ_V;
    public static $ENCODING_ETEN_B5_H;
    public static $ENCODING_ETEN_B5_V;
    public static $ENCRYPT;
    public static $ENCRYPT_META_DATA;
    public static $END_OF_LINE;
    public static $ENTRUST_PPKEF;
    public static $EXCLUSION;
    public static $EXT_G_STATE;
    public static $EXTEND;
    public static $EXTENDS;
    // F
    public static $F;
    public static $F_DECODE_PARMS;
    public static $F_FILTER;
    public static $FB;
    public static $FDF;
    public static $FF;
    public static $FIELDS;
    public static $FILESPEC;
    public static $FILTER;
    public static $FIRST;
    public static $FIRST_CHAR;
    public static $FIT_WINDOW;
    public static $FL;
    public static $FLAGS;
    public static $FLATE_DECODE;
    public static $FLATE_DECODE_ABBREVIATION;
    public static $FONT;
    public static $FONT_BBOX;
    public static $FONT_DESC;
    public static $FONT_FAMILY;
    public static $FONT_FILE;
    public static $FONT_FILE2;
    public static $FONT_FILE3;
    public static $FONT_MATRIX;
    public static $FONT_NAME;
    public static $FONT_STRETCH;
    public static $FONT_WEIGHT;
    public static $FORM;
    public static $FORMTYPE;
    public static $FRM;
    public static $FT;
    public static $FUNCTION;
    public static $FUNCTION_TYPE;
    public static $FUNCTIONS;
    // G
    public static $G;
    public static $GAMMA;
    public static $GROUP;
    public static $GTS_PDFA1;
    // H
    public static $H;
    public static $HARD_LIGHT;
    public static $HEIGHT;
    public static $HIDE_MENUBAR;
    public static $HIDE_TOOLBAR;
    public static $HIDE_WINDOWUI;
    // I
    public static $I;
    public static $IC;
    public static $ICCBASED;
    public static $ID;
    public static $ID_TREE;
    public static $IDENTITY;
    public static $IDENTITY_H;
    public static $IF;
    public static $IM;
    public static $IMAGE;
    public static $IMAGE_MASK;
    public static $INDEX;
    public static $INDEXED;
    public static $INFO;
    public static $INKLIST;
    public static $INTERPOLATE;
    public static $IT;
    public static $ITALIC_ANGLE;
    // J
    public static $JAVA_SCRIPT;
    public static $JBIG2_DECODE;
    public static $JBIG2_GLOBALS;
    public static $JPX_DECODE;
    public static $JS;
    // K
    public static $K;
    public static $KEYWORDS;
    public static $KIDS;
    // L
    public static $L;
    public static $LAB;
    public static $LANG;
    public static $LAST;
    public static $LAST_CHAR;
    public static $LAST_MODIFIED;
    public static $LC;
    public static $LE;
    public static $LEADING;
    public static $LEGAL_ATTESTATION;
    public static $LENGTH;
    public static $LENGTH1;
    public static $LENGTH2;
    public static $LIGHTEN;
    public static $LIMITS;
    public static $LJ;
    public static $LL;
    public static $LLE;
    public static $LLO;
    public static $LOCATION;
    public static $LUMINOSITY;
    public static $LW;
    public static $LZW_DECODE;
    public static $LZW_DECODE_ABBREVIATION;
    // M
    public static $M;
    public static $MAC;
    public static $MAC_ROMAN_ENCODING;
    public static $MARK_INFO;
    public static $MASK;
    public static $MATRIX;
    public static $MAX_LEN;
    public static $MAX_WIDTH;
    public static $MCID;
    public static $MDP;
    public static $MEDIA_BOX;
    public static $METADATA;
    public static $MISSING_WIDTH;
    public static $MK;
    public static $ML;
    public static $MM_TYPE1;
    public static $MOD_DATE;
    public static $MULTIPLY;
    // N
    public static $N;
    public static $NAME;
    public static $NAMES;
    public static $NEED_APPEARANCES;
    public static $NEXT;
    public static $NM;
    public static $NON_EFONT_NO_WARN;
    public static $NON_FULL_SCREEN_PAGE_MODE;
    public static $NONE;
    public static $NORMAL;
    public static $NUMS;
    // O
    public static $O;
    public static $OBJ;
    public static $OBJ_STM;
    public static $OC;
    public static $OCG;
    public static $OCGS;
    public static $OCPROPERTIES;
    public static $OE;
    
    /**
     * "OFF", to be used for OCGs, not for Acroform
     */
    public static $OFF;
    
    /**
     * "Off", to be used for Acroform, not for OCGs
     */
    public static $Off;    
    
    public static $ON;
    public static $OP;
    public static $OP_NS;
    public static $OPEN_ACTION;
    public static $OPEN_TYPE;
    public static $OPM;
    public static $OPT;
    public static $ORDER;
    public static $ORDERING;
    public static $OS;
    public static $OUTLINES;
    public static $OUTPUT_CONDITION;
    public static $OUTPUT_CONDITION_IDENTIFIER;
    public static $OUTPUT_INTENT;
    public static $OUTPUT_INTENTS;
    public static $OVERLAY;
    // P
    public static $P;
    public static $PAGE;
    public static $PAGE_LABELS;
    public static $PAGE_LAYOUT;
    public static $PAGE_MODE;
    public static $PAGES;
    public static $PAINT_TYPE;
    public static $PANOSE; 
    public static $PARAMS;
    public static $PARENT;
    public static $PARENT_TREE;
    public static $PARENT_TREE_NEXT_KEY;
    public static $PATTERN;
    public static $PATTERN_TYPE;
    public static $PDF_DOC_ENCODING;
    public static $PERMS;
    public static $PG;
    public static $PRE_RELEASE;
    public static $PREDICTOR;
    public static $PREV;
    public static $PRINT_AREA;
    public static $PRINT_CLIP;
    public static $PRINT_SCALING;
    public static $PROC_SET;
    public static $PROCESS;
    public static $PRODUCER;
    public static $PROP_BUILD;
    public static $PROPERTIES;
    public static $PS;
    public static $PUB_SEC;
    // Q
    public static $Q;
    public static $QUADPOINTS;
    // R
    public static $R;
    public static $RANGE;
    public static $RC;
    public static $RD;
    public static $REASON;
    public static $REASONS;
    public static $RECIPIENTS;
    public static $RECT;
    public static $REGISTRY;
    public static $REGISTRY_NAME;
    public static $RENAME;
    public static $RESOURCES;
    public static $RGB;
    public static $RI;
    public static $ROLE_MAP;
    public static $ROOT;
    public static $ROTATE;
    public static $ROWS;
    public static $RUN_LENGTH_DECODE;
    public static $RUN_LENGTH_DECODE_ABBREVIATION;
    public static $RV;
    // S
    public static $S;
    public static $SA;
    public static $SCREEN;
    public static $SE;
    public static $SEPARATION;
    public static $SET_F;
    public static $SET_FF;
    public static $SHADING;
    public static $SHADING_TYPE;
    public static $SIG;
    public static $SIG_FLAGS;
    public static $SIZE;
    public static $SM;
    public static $SMASK;
    public static $SOFT_LIGHT;
    public static $SS;
    public static $ST;
    public static $STANDARD_ENCODING;
    public static $STATE;
    public static $STATE_MODEL;
    public static $STATUS;
    public static $STD_CF;
    public static $STEM_H;
    public static $STEM_V;
    public static $STM_F;
    public static $STR_F;
    public static $STRUCT_PARENT;
    public static $STRUCT_PARENTS;
    public static $STRUCT_TREE_ROOT;
    public static $STYLE;
    public static $SUB_FILTER;
    public static $SUBJ;
    public static $SUBJECT;
    public static $SUBTYPE;
    public static $SUPPLEMENT;
    public static $SV;
    public static $SW;
    public static $SY;
    // T
    public static $T;
    public static $TARGET;
    public static $TEMPLATES;
    public static $THREADS;
    public static $THUMB;
    public static $TI;
    public static $TILING_TYPE;
    public static $TIME_STAMP;
    public static $TITLE;
    public static $TK;
    public static $TM;
    public static $TO_UNICODE;
    public static $TR;
    public static $TRAPPED;
    public static $TRANS;
    public static $TRANSPARENCY;
    public static $TREF;
    public static $TRIM_BOX;
    public static $TRUE_TYPE;
    public static $TRUSTED_MODE;
    public static $TU;
    /** Acro form field type for text field. */
    public static $TX;
    public static $TYPE;
    public static $TYPE0;
    public static $TYPE1;
    public static $TYPE3;
    // U
    public static $U;
    public static $UE;
    public static $UF;
    public static $UNCHANGED;
    public static $UNIX;
    public static $URI;
    public static $URL;
    // V
    public static $V;
    public static $VERISIGN_PPKVS;
    public static $VERSION;
    public static $VERTICES;
    public static $VERTICES_PER_ROW;
    public static $VIEW_AREA;
    public static $VIEW_CLIP;
    public static $VIEWER_PREFERENCES;
    // W
    public static $W;
    public static $W2;
    public static $WHITE_POINT;
    public static $WIDTH;
    public static $WIDTHS;
    public static $WIN_ANSI_ENCODING;
    // X
    public static $XFA;
    public static $X_STEP;
    public static $XHEIGHT;
    public static $XOBJECT;
    public static $XREF;
    public static $XREF_STM;
    // Y
    public static $Y_STEP;
    public static $YES;

	// fields
    private $name;	// String
    private $hashCode;	// int
	
    /**
     * Private constructor.  This will limit the number of COSName objects.
     * that are created.
     *
     * @param aName The name of the COSName object.
     * @param staticValue Indicates if the COSName object is static so that it can
     *        be stored in the HashMap without synchronizing.
     */
   function __construct( $aName, $staticValue=true )
    {
		if (!is_string($aName) || !is_bool($staticValue)) return;
        $this->name = $aName;
        if ( $staticValue ) {
            $this->commonNameMap[$aName]=$this;
        } else {
            $this->nameMap[$aName]=$this;
        }
        $this->hashCode = $this->javahash($this->name);
    }
	public function javahash($s) {
		// TODO: Handle numeric overflows.
		if (!is_string($s)) return 0;
		$h = 0;
		for ($i=0;$i<strlen($s);$i++) {
			$h = (31*$h)+ord(substr($s,$i,1));
		}
		return $h;
	}
	public static function getPDFName($aName) {
		if (!is_string($aName)) return null;
		if (isset($this->commonNameMap[$aName])) return $this->commonNameMap[$aName];
		if (isset($this->nameMap[$aName])) return $this->nameMap[$aName];
		return new COSName($aName,false);
	}
    /**
     * This will get the name of this COSName object.
     * 
     * @return The name of the object.
     */	
	public function getName() {
		return $this->name;
	}
	public function toString() {
		return 'COSName{'.$this->name.'}';
	}
	public function hashCode() {
		return $this->hashCode;
	}
    /**
     * Returns true if the name is the empty string.
     * @return true if the name is the empty string.
     */
    public function isEmpty() {
        return empty($this->name);
    }
	public function accept($visitor) {
		if (!($visitor instanceof ICOSVisitor)) return;
		return $visitor->visitFromName($this);
	}
    /**
     * This will output this string as a PDF object.
     * 
     * @param out The stream to write to.
     * @throws IOException If there is an error writing to the stream.
     */
	public function writePDF($out) {
		fwrite($out,'/');
		$s = $this->getName();
		for ($i=0;$i<strlen($s);$i++) {
			$char = substr($s,$i,1);
			$current = (ord($char) + 256) % 256;
            // be more restrictive than the PDF spec, "Name Objects", see PDFBOX-2073
            if (($char >= 'A' && $char <= 'Z') ||
				($char >= 'a' && $char <= 'z') ||
				($char >= '0' && $char <= '9') ||
				$char == '+' ||
				$char == '-' ||
				$char == '_' ||
				$char == '@' ||
				$char == '*' ||
				$char == '$' ||
				$char == ';' ||
				$char == '.') {
					fwrite($out,$char);
			} else {
				fprintf($out,"#%02X",$current);
			}
		}
	}
    /**
     * Not usually needed except if resources need to be reclaimed in a long running process.
     */
	public static function clearResources() {
		$this->nameMap = array();
	}
}
// http://php.net/manual/en/language.oop5.static.php
// michaelnospamdotnospamdaly at kayakwiki says, "You have to do external initialization."
// A
COSName::$A = new COSName("A");
COSName::$AA = new COSName("AA");
COSName::$ACRO_FORM = new COSName("AcroForm");
COSName::$ACTUAL_TEXT = new COSName("ActualText");
COSName::$ADBE_PKCS7_DETACHED = new COSName("adbe.pkcs7.detached");
COSName::$ADBE_PKCS7_SHA1 = new COSName("adbe.pkcs7.sha1");
COSName::$ADBE_X509_RSA_SHA1 = new COSName("adbe.x509.rsa_sha1");
COSName::$ADOBE_PPKLITE = new COSName("Adobe.PPKLite");
COSName::$AESV3 = new COSName("AESV3");
COSName::$AFTER = new COSName("After");
COSName::$AIS = new COSName("AIS");
COSName::$ALT = new COSName("Alt");
COSName::$ALPHA = new COSName("Alpha");
COSName::$ALTERNATE = new COSName("Alternate");
COSName::$ANNOT = new COSName("Annot");
COSName::$ANNOTS = new COSName("Annots");
COSName::$ANTI_ALIAS = new COSName("AntiAlias");
COSName::$AP = new COSName("AP");
COSName::$AP_REF = new COSName("APRef");
COSName::$APP = new COSName("App");
COSName::$ART_BOX = new COSName("ArtBox");
COSName::$ARTIFACT = new COSName("Artifact");
COSName::$AS = new COSName("AS");
COSName::$ASCENT = new COSName("Ascent");
COSName::$ASCII_HEX_DECODE = new COSName("ASCIIHexDecode");
COSName::$ASCII_HEX_DECODE_ABBREVIATION = new COSName("AHx");
COSName::$ASCII85_DECODE = new COSName("ASCII85Decode");
COSName::$ASCII85_DECODE_ABBREVIATION = new COSName("A85");
COSName::$ATTACHED = new COSName("Attached");
COSName::$AUTHOR = new COSName("Author");
COSName::$AVG_WIDTH = new COSName("AvgWidth");
// B
COSName::$B = new COSName("B");
COSName::$BACKGROUND = new COSName("Background");
COSName::$BASE_ENCODING = new COSName("BaseEncoding");
COSName::$BASE_FONT = new COSName("BaseFont");
COSName::$BASE_STATE = new COSName("BaseState");
COSName::$BBOX = new COSName("BBox");
COSName::$BC = new COSName("BC");
COSName::$BE = new COSName("BE");
COSName::$BEFORE = new COSName("Before");
COSName::$BG = new COSName("BG");
COSName::$BITS_PER_COMPONENT = new COSName("BitsPerComponent");
COSName::$BITS_PER_COORDINATE = new COSName("BitsPerCoordinate");
COSName::$BITS_PER_FLAG = new COSName("BitsPerFlag");
COSName::$BITS_PER_SAMPLE = new COSName("BitsPerSample");
COSName::$BLACK_IS_1 = new COSName("BlackIs1");
COSName::$BLACK_POINT = new COSName("BlackPoint");
COSName::$BLEED_BOX = new COSName("BleedBox");
COSName::$BM = new COSName("BM");
COSName::$BOUNDS = new COSName("Bounds");
COSName::$BPC = new COSName("BPC");
COSName::$BS = new COSName("BS");
//** Acro form field type for button fields.
COSName::$BTN = new COSName("Btn");
COSName::$BYTERANGE = new COSName("ByteRange");
// C
COSName::$C = new COSName("C");
COSName::$C0 = new COSName("C0");
COSName::$C1 = new COSName("C1");
COSName::$CA = new COSName("CA");
COSName::$CA_NS = new COSName("ca");
COSName::$CALGRAY = new COSName("CalGray");
COSName::$CALRGB = new COSName("CalRGB");
COSName::$CAP = new COSName("Cap");
COSName::$CAP_HEIGHT = new COSName("CapHeight");
COSName::$CATALOG = new COSName("Catalog");
COSName::$CCITTFAX_DECODE = new COSName("CCITTFaxDecode");
COSName::$CCITTFAX_DECODE_ABBREVIATION = new COSName("CCF");
COSName::$CENTER_WINDOW = new COSName("CenterWindow");
COSName::$CF = new COSName("CF");
COSName::$CFM = new COSName("CFM");
//** Acro form field type for choice fields.
COSName::$CH = new COSName("Ch");
COSName::$CHAR_PROCS = new COSName("CharProcs");
COSName::$CHAR_SET = new COSName("CharSet");
COSName::$CICI_SIGNIT = new COSName("CICI.SignIt");
COSName::$CID_FONT_TYPE0 = new COSName("CIDFontType0");
COSName::$CID_FONT_TYPE2 = new COSName("CIDFontType2");
COSName::$CID_TO_GID_MAP = new COSName("CIDToGIDMap");
COSName::$CID_SET = new COSName("CIDSet");
COSName::$CIDSYSTEMINFO = new COSName("CIDSystemInfo");
COSName::$CLR_F = new COSName("ClrF");
COSName::$CLR_FF = new COSName("ClrFf");
COSName::$CMAP = new COSName("CMap");
COSName::$CMAPNAME = new COSName("CMapName");
COSName::$CMYK = new COSName("CMYK");
COSName::$CO = new COSName("CO");
COSName::$COLOR_BURN = new COSName("ColorBurn");
COSName::$COLOR_DODGE = new COSName("ColorDodge");
COSName::$COLORANTS = new COSName("Colorants");
COSName::$COLORS = new COSName("Colors");
COSName::$COLORSPACE = new COSName("ColorSpace");
COSName::$COLUMNS = new COSName("Columns");
COSName::$COMPATIBLE = new COSName("Compatible");
COSName::$COMPONENTS = new COSName("Components");
COSName::$CONTACT_INFO = new COSName("ContactInfo");
COSName::$CONTENTS = new COSName("Contents");
COSName::$COORDS = new COSName("Coords");
COSName::$COUNT = new COSName("Count");
COSName::$CP = new COSName("CP");
COSName::$CREATION_DATE = new COSName("CreationDate");
COSName::$CREATOR = new COSName("Creator");
COSName::$CROP_BOX = new COSName("CropBox");
COSName::$CRYPT = new COSName("Crypt");
COSName::$CS = new COSName("CS");
// D
COSName::$D = new COSName("D");
COSName::$DA = new COSName("DA");
COSName::$DARKEN = new COSName("Darken");
COSName::$DATE = new COSName("Date");
COSName::$DCT_DECODE = new COSName("DCTDecode");
COSName::$DCT_DECODE_ABBREVIATION = new COSName("DCT");
COSName::$DECODE = new COSName("Decode");
COSName::$DECODE_PARMS = new COSName("DecodeParms");
COSName::$DEFAULT = new COSName("default");
COSName::$DEFAULT_CMYK = new COSName("DefaultCMYK");
COSName::$DEFAULT_GRAY = new COSName("DefaultGray");
COSName::$DEFAULT_RGB = new COSName("DefaultRGB");
COSName::$DESC = new COSName("Desc");
COSName::$DESCENDANT_FONTS = new COSName("DescendantFonts");
COSName::$DESCENT = new COSName("Descent");
COSName::$DEST = new COSName("Dest");
COSName::$DEST_OUTPUT_PROFILE = new COSName("DestOutputProfile");
COSName::$DESTS = new COSName("Dests");
COSName::$DEVICECMYK = new COSName("DeviceCMYK");
COSName::$DEVICEGRAY = new COSName("DeviceGray");
COSName::$DEVICEN = new COSName("DeviceN");
COSName::$DEVICERGB = new COSName("DeviceRGB");
COSName::$DI = new COSName("Di");
COSName::$DIFFERENCE = new COSName("Difference");
COSName::$DIFFERENCES = new COSName("Differences");
COSName::$DIGEST_METHOD = new COSName("DigestMethod");
COSName::$DIGEST_RIPEMD160 = new COSName("RIPEMD160");
COSName::$DIGEST_SHA1 = new COSName("SHA1");
COSName::$DIGEST_SHA256 = new COSName("SHA256");
COSName::$DIGEST_SHA384 = new COSName("SHA384");
COSName::$DIGEST_SHA512 = new COSName("SHA512");
COSName::$DIRECTION = new COSName("Direction");
COSName::$DISPLAY_DOC_TITLE = new COSName("DisplayDocTitle");
COSName::$DL = new COSName("DL");
COSName::$DM = new COSName("Dm");
COSName::$DOC = new COSName("Doc");
COSName::$DOC_CHECKSUM = new COSName("DocChecksum");
COSName::$DOC_TIME_STAMP = new COSName("DocTimeStamp");
COSName::$DOMAIN = new COSName("Domain");
COSName::$DOS = new COSName("DOS");
COSName::$DP = new COSName("DP");
COSName::$DR = new COSName("DR");
COSName::$DS = new COSName("DS");    
COSName::$DUPLEX = new COSName("Duplex");
COSName::$DUR = new COSName("Dur");
COSName::$DV = new COSName("DV");
COSName::$DW = new COSName("DW");
COSName::$DW2 = new COSName("DW2");
// E
COSName::$E = new COSName("E");
COSName::$EARLY_CHANGE = new COSName("EarlyChange");
COSName::$EF = new COSName("EF");
COSName::$EMBEDDED_FDFS = new COSName("EmbeddedFDFs");
COSName::$EMBEDDED_FILES = new COSName("EmbeddedFiles");
COSName::$EMPTY = new COSName("");
COSName::$ENCODE = new COSName("Encode");
COSName::$ENCODED_BYTE_ALIGN = new COSName("EncodedByteAlign");    
COSName::$ENCODING = new COSName("Encoding");
COSName::$ENCODING_90MS_RKSJ_H = new COSName("90ms-RKSJ-H");
COSName::$ENCODING_90MS_RKSJ_V = new COSName("90ms-RKSJ-V");
COSName::$ENCODING_ETEN_B5_H = new COSName("ETen-B5-H");
COSName::$ENCODING_ETEN_B5_V = new COSName("ETen-B5-V");
COSName::$ENCRYPT = new COSName("Encrypt");
COSName::$ENCRYPT_META_DATA = new COSName("EncryptMetadata");
COSName::$END_OF_LINE = new COSName("EndOfLine");
COSName::$ENTRUST_PPKEF = new COSName("Entrust.PPKEF");
COSName::$EXCLUSION = new COSName("Exclusion");
COSName::$EXT_G_STATE = new COSName("ExtGState");
COSName::$EXTEND = new COSName("Extend");
COSName::$EXTENDS = new COSName("Extends");
// F
COSName::$F = new COSName("F");
COSName::$F_DECODE_PARMS = new COSName("FDecodeParms");
COSName::$F_FILTER = new COSName("FFilter");
COSName::$FB = new COSName("FB");
COSName::$FDF = new COSName("FDF");
COSName::$FF = new COSName("Ff");
COSName::$FIELDS = new COSName("Fields");
COSName::$FILESPEC = new COSName("Filespec");
COSName::$FILTER = new COSName("Filter");
COSName::$FIRST = new COSName("First");
COSName::$FIRST_CHAR = new COSName("FirstChar");
COSName::$FIT_WINDOW = new COSName("FitWindow");
COSName::$FL = new COSName("FL");
COSName::$FLAGS = new COSName("Flags");
COSName::$FLATE_DECODE = new COSName("FlateDecode");
COSName::$FLATE_DECODE_ABBREVIATION = new COSName("Fl");
COSName::$FONT = new COSName("Font");
COSName::$FONT_BBOX = new COSName("FontBBox");
COSName::$FONT_DESC = new COSName("FontDescriptor");
COSName::$FONT_FAMILY = new COSName("FontFamily");
COSName::$FONT_FILE = new COSName("FontFile");
COSName::$FONT_FILE2 = new COSName("FontFile2");
COSName::$FONT_FILE3 = new COSName("FontFile3");
COSName::$FONT_MATRIX = new COSName("FontMatrix");
COSName::$FONT_NAME = new COSName("FontName");
COSName::$FONT_STRETCH = new COSName("FontStretch");
COSName::$FONT_WEIGHT = new COSName("FontWeight");
COSName::$FORM = new COSName("Form");
COSName::$FORMTYPE = new COSName("FormType");
COSName::$FRM = new COSName("FRM");
COSName::$FT = new COSName("FT");
COSName::$FUNCTION = new COSName("Function");
COSName::$FUNCTION_TYPE = new COSName("FunctionType");
COSName::$FUNCTIONS = new COSName("Functions");
// G
COSName::$G = new COSName("G");
COSName::$GAMMA = new COSName("Gamma");
COSName::$GROUP = new COSName("Group");
COSName::$GTS_PDFA1 = new COSName("GTS_PDFA1");
// H
COSName::$H = new COSName("H");
COSName::$HARD_LIGHT = new COSName("HardLight");
COSName::$HEIGHT = new COSName("Height");
COSName::$HIDE_MENUBAR = new COSName("HideMenubar");
COSName::$HIDE_TOOLBAR = new COSName("HideToolbar");
COSName::$HIDE_WINDOWUI = new COSName("HideWindowUI");
// I
COSName::$I = new COSName("I");
COSName::$IC = new COSName("IC");
COSName::$ICCBASED = new COSName("ICCBased");
COSName::$ID = new COSName("ID");
COSName::$ID_TREE = new COSName("IDTree");
COSName::$IDENTITY = new COSName("Identity");
COSName::$IDENTITY_H = new COSName("Identity-H");
COSName::$IF = new COSName("IF");
COSName::$IM = new COSName("IM");
COSName::$IMAGE = new COSName("Image");
COSName::$IMAGE_MASK = new COSName("ImageMask");
COSName::$INDEX = new COSName("Index");
COSName::$INDEXED = new COSName("Indexed");
COSName::$INFO = new COSName("Info");
COSName::$INKLIST = new COSName("InkList");
COSName::$INTERPOLATE = new COSName("Interpolate");
COSName::$IT = new COSName("IT");
COSName::$ITALIC_ANGLE = new COSName("ItalicAngle");
// J
COSName::$JAVA_SCRIPT = new COSName("JavaScript");
COSName::$JBIG2_DECODE = new COSName("JBIG2Decode");
COSName::$JBIG2_GLOBALS = new COSName("JBIG2Globals");
COSName::$JPX_DECODE = new COSName("JPXDecode");
COSName::$JS = new COSName("JS");
// K
COSName::$K = new COSName("K");
COSName::$KEYWORDS = new COSName("Keywords");
COSName::$KIDS = new COSName("Kids");
// L
COSName::$L = new COSName("L");
COSName::$LAB = new COSName("Lab");
COSName::$LANG = new COSName("Lang");
COSName::$LAST = new COSName("Last");
COSName::$LAST_CHAR = new COSName("LastChar");
COSName::$LAST_MODIFIED = new COSName("LastModified");
COSName::$LC = new COSName("LC");
COSName::$LE = new COSName("LE");
COSName::$LEADING = new COSName("Leading");
COSName::$LEGAL_ATTESTATION = new COSName("LegalAttestation");
COSName::$LENGTH = new COSName("Length");
COSName::$LENGTH1 = new COSName("Length1");
COSName::$LENGTH2 = new COSName("Length2");
COSName::$LIGHTEN = new COSName("Lighten");
COSName::$LIMITS = new COSName("Limits");
COSName::$LJ = new COSName("LJ");
COSName::$LL = new COSName("LL");
COSName::$LLE = new COSName("LLE");
COSName::$LLO = new COSName("LLO");
COSName::$LOCATION = new COSName("Location");
COSName::$LUMINOSITY = new COSName("Luminosity");
COSName::$LW = new COSName("LW");
COSName::$LZW_DECODE = new COSName("LZWDecode");
COSName::$LZW_DECODE_ABBREVIATION = new COSName("LZW");
// M
COSName::$M = new COSName("M");
COSName::$MAC = new COSName("Mac");
COSName::$MAC_ROMAN_ENCODING = new COSName("MacRomanEncoding");
COSName::$MARK_INFO = new COSName("MarkInfo");
COSName::$MASK = new COSName("Mask");
COSName::$MATRIX = new COSName("Matrix");
COSName::$MAX_LEN = new COSName("MaxLen");
COSName::$MAX_WIDTH = new COSName("MaxWidth");
COSName::$MCID = new COSName("MCID");
COSName::$MDP = new COSName("MDP");
COSName::$MEDIA_BOX = new COSName("MediaBox");
COSName::$METADATA = new COSName("Metadata");
COSName::$MISSING_WIDTH = new COSName("MissingWidth");
COSName::$MK = new COSName("MK");
COSName::$ML = new COSName("ML");
COSName::$MM_TYPE1 = new COSName("MMType1");
COSName::$MOD_DATE = new COSName("ModDate");
COSName::$MULTIPLY = new COSName("Multiply");
// N
COSName::$N = new COSName("N");
COSName::$NAME = new COSName("Name");
COSName::$NAMES = new COSName("Names");
COSName::$NEED_APPEARANCES = new COSName("NeedAppearances");
COSName::$NEXT = new COSName("Next");
COSName::$NM = new COSName("NM");
COSName::$NON_EFONT_NO_WARN = new COSName("NonEFontNoWarn");
COSName::$NON_FULL_SCREEN_PAGE_MODE = new COSName("NonFullScreenPageMode");
COSName::$NONE = new COSName("None");
COSName::$NORMAL = new COSName("Normal");
COSName::$NUMS = new COSName("Nums");
// O
COSName::$O = new COSName("O");
COSName::$OBJ = new COSName("Obj");
COSName::$OBJ_STM = new COSName("ObjStm");
COSName::$OC = new COSName("OC");
COSName::$OCG = new COSName("OCG");
COSName::$OCGS = new COSName("OCGs");
COSName::$OCPROPERTIES = new COSName("OCProperties");
COSName::$OE = new COSName("OE");

/**
 * "OFF", to be used for OCGs, not for Acroform
 */
COSName::$OFF = new COSName("OFF");

/**
 * "Off", to be used for Acroform, not for OCGs
 */
COSName::$Off = new COSName("Off");    

COSName::$ON = new COSName("ON");
COSName::$OP = new COSName("OP");
COSName::$OP_NS = new COSName("op");
COSName::$OPEN_ACTION = new COSName("OpenAction");
COSName::$OPEN_TYPE = new COSName("OpenType");
COSName::$OPM = new COSName("OPM");
COSName::$OPT = new COSName("Opt");
COSName::$ORDER = new COSName("Order");
COSName::$ORDERING = new COSName("Ordering");
COSName::$OS = new COSName("OS");
COSName::$OUTLINES = new COSName("Outlines");
COSName::$OUTPUT_CONDITION = new COSName("OutputCondition");
COSName::$OUTPUT_CONDITION_IDENTIFIER = new COSName(
		"OutputConditionIdentifier");
COSName::$OUTPUT_INTENT = new COSName("OutputIntent");
COSName::$OUTPUT_INTENTS = new COSName("OutputIntents");
COSName::$OVERLAY = new COSName("Overlay");
// P
COSName::$P = new COSName("P");
COSName::$PAGE = new COSName("Page");
COSName::$PAGE_LABELS = new COSName("PageLabels");
COSName::$PAGE_LAYOUT = new COSName("PageLayout");
COSName::$PAGE_MODE = new COSName("PageMode");
COSName::$PAGES = new COSName("Pages");
COSName::$PAINT_TYPE = new COSName("PaintType");
COSName::$PANOSE = new COSName("Panose");    
COSName::$PARAMS = new COSName("Params");
COSName::$PARENT = new COSName("Parent");
COSName::$PARENT_TREE = new COSName("ParentTree");
COSName::$PARENT_TREE_NEXT_KEY = new COSName("ParentTreeNextKey");
COSName::$PATTERN = new COSName("Pattern");
COSName::$PATTERN_TYPE = new COSName("PatternType");
COSName::$PDF_DOC_ENCODING = new COSName("PDFDocEncoding");
COSName::$PERMS = new COSName("Perms");
COSName::$PG = new COSName("Pg");
COSName::$PRE_RELEASE = new COSName("PreRelease");
COSName::$PREDICTOR = new COSName("Predictor");
COSName::$PREV = new COSName("Prev");
COSName::$PRINT_AREA = new COSName("PrintArea");
COSName::$PRINT_CLIP = new COSName("PrintClip");
COSName::$PRINT_SCALING = new COSName("PrintScaling");
COSName::$PROC_SET = new COSName("ProcSet");
COSName::$PROCESS = new COSName("Process");
COSName::$PRODUCER = new COSName("Producer");
COSName::$PROP_BUILD = new COSName("Prop_Build");
COSName::$PROPERTIES = new COSName("Properties");
COSName::$PS = new COSName("PS");
COSName::$PUB_SEC = new COSName("PubSec");
// Q
COSName::$Q = new COSName("Q");
COSName::$QUADPOINTS = new COSName("QuadPoints");
// R
COSName::$R = new COSName("R");
COSName::$RANGE = new COSName("Range");
COSName::$RC = new COSName("RC");
COSName::$RD = new COSName("RD");
COSName::$REASON = new COSName("Reason");
COSName::$REASONS = new COSName("Reasons");
COSName::$RECIPIENTS = new COSName("Recipients");
COSName::$RECT = new COSName("Rect");
COSName::$REGISTRY = new COSName("Registry");
COSName::$REGISTRY_NAME = new COSName("RegistryName");
COSName::$RENAME = new COSName("Rename");
COSName::$RESOURCES = new COSName("Resources");
COSName::$RGB = new COSName("RGB");
COSName::$RI = new COSName("RI");
COSName::$ROLE_MAP = new COSName("RoleMap");
COSName::$ROOT = new COSName("Root");
COSName::$ROTATE = new COSName("Rotate");
COSName::$ROWS = new COSName("Rows");
COSName::$RUN_LENGTH_DECODE = new COSName("RunLengthDecode");
COSName::$RUN_LENGTH_DECODE_ABBREVIATION = new COSName("RL");
COSName::$RV = new COSName("RV");
// S
COSName::$S = new COSName("S");
COSName::$SA = new COSName("SA");
COSName::$SCREEN = new COSName("Screen");
COSName::$SE = new COSName("SE");
COSName::$SEPARATION = new COSName("Separation");
COSName::$SET_F = new COSName("SetF");
COSName::$SET_FF = new COSName("SetFf");
COSName::$SHADING = new COSName("Shading");
COSName::$SHADING_TYPE = new COSName("ShadingType");
COSName::$SIG = new COSName("Sig");
COSName::$SIG_FLAGS = new COSName("SigFlags");
COSName::$SIZE = new COSName("Size");
COSName::$SM = new COSName("SM");
COSName::$SMASK = new COSName("SMask");
COSName::$SOFT_LIGHT = new COSName("SoftLight");
COSName::$SS = new COSName("SS");
COSName::$ST = new COSName("St");
COSName::$STANDARD_ENCODING = new COSName("StandardEncoding");
COSName::$STATE = new COSName("State");
COSName::$STATE_MODEL = new COSName("StateModel");
COSName::$STATUS = new COSName("Status");
COSName::$STD_CF = new COSName("StdCF");
COSName::$STEM_H = new COSName("StemH");
COSName::$STEM_V = new COSName("StemV");
COSName::$STM_F = new COSName("StmF");
COSName::$STR_F = new COSName("StrF");
COSName::$STRUCT_PARENT = new COSName("StructParent");
COSName::$STRUCT_PARENTS = new COSName("StructParents");
COSName::$STRUCT_TREE_ROOT = new COSName("StructTreeRoot");
COSName::$STYLE = new COSName("Style");
COSName::$SUB_FILTER = new COSName("SubFilter");
COSName::$SUBJ = new COSName("Subj");
COSName::$SUBJECT = new COSName("Subject");
COSName::$SUBTYPE = new COSName("Subtype");
COSName::$SUPPLEMENT = new COSName("Supplement");
COSName::$SV = new COSName("SV");
COSName::$SW = new COSName("SW");
COSName::$SY = new COSName("Sy");
// T
COSName::$T = new COSName("T");
COSName::$TARGET = new COSName("Target");
COSName::$TEMPLATES = new COSName("Templates");
COSName::$THREADS = new COSName("Threads");
COSName::$THUMB = new COSName("Thumb");
COSName::$TI = new COSName("TI");
COSName::$TILING_TYPE = new COSName("TilingType");
COSName::$TIME_STAMP = new COSName("TimeStamp");
COSName::$TITLE = new COSName("Title");
COSName::$TK = new COSName("TK");
COSName::$TM = new COSName("TM");
COSName::$TO_UNICODE = new COSName("ToUnicode");
COSName::$TR = new COSName("TR");
COSName::$TRAPPED = new COSName("Trapped");
COSName::$TRANS = new COSName("Trans");
COSName::$TRANSPARENCY = new COSName("Transparency");
COSName::$TREF = new COSName("TRef");
COSName::$TRIM_BOX = new COSName("TrimBox");
COSName::$TRUE_TYPE = new COSName("TrueType");
COSName::$TRUSTED_MODE = new COSName("TrustedMode");
COSName::$TU = new COSName("TU");
/** Acro form field type for text field. */
COSName::$TX = new COSName("Tx");
COSName::$TYPE = new COSName("Type");
COSName::$TYPE0 = new COSName("Type0");
COSName::$TYPE1 = new COSName("Type1");
COSName::$TYPE3 = new COSName("Type3");
// U
COSName::$U = new COSName("U");
COSName::$UE = new COSName("UE");
COSName::$UF = new COSName("UF");
COSName::$UNCHANGED = new COSName("Unchanged");
COSName::$UNIX = new COSName("Unix");
COSName::$URI = new COSName("URI");
COSName::$URL = new COSName("URL");
// V
COSName::$V = new COSName("V");
COSName::$VERISIGN_PPKVS = new COSName("VeriSign.PPKVS");
COSName::$VERSION = new COSName("Version");
COSName::$VERTICES = new COSName("Vertices");
COSName::$VERTICES_PER_ROW = new COSName("VerticesPerRow");
COSName::$VIEW_AREA = new COSName("ViewArea");
COSName::$VIEW_CLIP = new COSName("ViewClip");
COSName::$VIEWER_PREFERENCES = new COSName("ViewerPreferences");
// W
COSName::$W = new COSName("W");
COSName::$W2 = new COSName("W2");
COSName::$WHITE_POINT = new COSName("WhitePoint");
COSName::$WIDTH = new COSName("Width");
COSName::$WIDTHS = new COSName("Widths");
COSName::$WIN_ANSI_ENCODING = new COSName("WinAnsiEncoding");
// X
COSName::$XFA = new COSName("XFA");
COSName::$X_STEP = new COSName("XStep");
COSName::$XHEIGHT = new COSName("XHeight");
COSName::$XOBJECT = new COSName("XObject");
COSName::$XREF = new COSName("XRef");
COSName::$XREF_STM = new COSName("XRefStm");
// Y
COSName::$Y_STEP = new COSName("YStep");
COSName::$YES = new COSName("Yes");
?>