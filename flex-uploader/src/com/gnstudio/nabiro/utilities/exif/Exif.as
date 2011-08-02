package com.gnstudio.nabiro.utilities.exif
{
	/**
	 *
	 * GNstudio nabiro
	 * =====================================================================
	 * Copyright(c) 2009
	 * http://www.gnstudio.com
	 *
	 *
	 *
	 * This file is part of the nabiro flash platform framework
	 *
	 *
	 * nabiro is free software; you can redistribute it and/or modify
	 * it under the terms of the GNU Lesser General Public License as published by
	 * the Free Software Foundation; either version 3 of the License, or
	 * at your option) any later version.
	 *
	 * nabiro is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU Lesser General Public License
	 * along with Intelligere SCS; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	 * =====================================================================
	 *
	 *
	 *
	 *   @package  nabiro
	 *
	 *   @version  0.9
	 *   @idea maker 			Giorgio Natili [ g.natili@gnstudio.com ]
	 *   @author 					Giorgio Natili [ g.natili@gnstudio.com ]
	 *   
	 *	 
	 */
	
	import flash.display.Bitmap;
	import flash.display.BitmapData;
	import flash.display.Loader;
	import flash.events.Event;
	import flash.events.ProgressEvent;
	import flash.net.URLRequest;
	import flash.net.URLStream;
	import flash.utils.ByteArray;
	import flash.utils.Dictionary;
	import flash.utils.Endian;

	public class Exif extends URLStream{
		
		// Exif directory tag definition
	    /** Identifies NEWSUBFILETYPE tag */
	    public static const NEWSUBFILETYPE:uint = 0xFE;
	
	    /** Identifies the IMAGEWIDTH tag */
	    public static const IMAGEWIDTH:uint = 0x100;
	
	    /** Identifies the IMAGELENGTH tag */
	    public static const IMAGELENGTH:uint = 0x101;
	
	    /** Identifies the BITSPERSAMPLE tag */
	    public static const BITSPERSAMPLE:uint = 0x102;
	
	    /** Identifies the COMPRESSION tag */
	    public static const COMPRESSION:uint = 0x103;
	
	    /** Identifies the PHOTOMETRICINTERPRETATION tag */
	    public static const PHOTOMETRICINTERPRETATION:uint = 0x106;
	
	    /** Identifies the FILLORDER tag */
	    public static const FILLORDER:uint = 0x10A;
	
	    /** Identifies the DOCUMENTNAME tag */
	    public static const DOCUMENTNAME:uint = 0x10D;
	
	    /** Identifies the IMAGEDESCRIPTION tag */
	    public static const IMAGEDESCRIPTION:uint = 0x10E;
	
	    /** Identifies the MAKE tag */
	    public static const MAKE:uint = 0x10F;
	
	    /** Identifies the MODEL tag */
	    public static const MODEL:uint = 0x110;
	
	    /** Identifies the STRIPOFFSETS tag */
	    public static const STRIPOFFSETS:uint = 0x111;
	
	    /** Identifies the ORIENTATION tag */
	    public static const ORIENTATION:uint = 0x112;
	
	    /** Identifies the SAMPLESPERPIXEL tag */
	    public static const SAMPLESPERPIXEL:uint = 0x115;
	
	    /** Identifies the ROWSPERSTRIP tag */
	    public static const ROWSPERSTRIP:uint = 0x116;
	
	    /** Identifies the STRIPBYTECOUNTS tag */
	    public static const STRIPBYTECOUNTS:uint = 0x117;
	
	    /** Identifies the XRESOLUTION tag */
	    public static const XRESOLUTION:uint = 0x11A;
	
	    /** Identifies the YRESOLUTION tag */
	    public static const YRESOLUTION:uint = 0x11B;
	
	    /** Identifies the PLANARCONFIGURATION tag */
	    public static const PLANARCONFIGURATION:uint = 0x11C;
	
	    /** Identifies the RESOLUTIONUNIT tag */
	    public static const RESOLUTIONUNIT:uint = 0x128;
	
	    /** Identifies the TRANSFERFUNCTION tag */
	    public static const TRANSFERFUNCTION:uint = 0x12D;
	
	    /** Identifies the SOFTWARE tag */
	    public static const SOFTWARE:uint = 0x131;
	
	    /** Identifies the DATETIME tag */
	    public static const DATETIME:uint = 0x132;
	
	    /** Identifies the ARTIST tag */
	    public static const ARTIST:uint = 0x13B;
	
	    /** Identifies the WHITEPOINT tag */
	    public static const WHITEPOINT:uint = 0x13E;
	
	    /** Identifies the PRIMARYCHROMATICITIES tag */
	    public static const PRIMARYCHROMATICITIES:uint = 0x13F;
	
	    /** Identifies the SUBIFDS tag */
	    public static const SUBIFDS:uint = 0x14A;
	
	    /** Identifies the JPEGTABLES tag */
	    public static const JPEGTABLES:uint = 0x15B;
	
	    /** Identifies the TRANSFERRANGE tag */
	    public static const TRANSFERRANGE:uint = 0x156;
	
	    /** Identifies the JPEGPROC tag */
	    public static const JPEGPROC:uint = 0x200;
	
	    /** Identifies the JPEGINTERCHANGEFORMAT tag */
	    public static const JPEGINTERCHANGEFORMAT:uint = 0x201;
	
	    /** Identifies the JPEGINTERCHANGEFORMATLENGTH tag */
	    public static const JPEGINTERCHANGEFORMATLENGTH:uint = 0x202;
	
	    /** Identifies the YCBCRCOEFFICIENTS tag */
	    public static const YCBCRCOEFFICIENTS:uint = 0x211;
	
	    /** Identifies the YCBCRSUBSAMPLING tag */
	    public static const YCBCRSUBSAMPLING:uint = 0x212;
	
	    /** Identifies the YCBCRPOSITIONING tag */
	    public static const YCBCRPOSITIONING:uint = 0x213;
	
	    /** Identifies the REFERENCEBLACKWHITE tag */
	    public static const REFERENCEBLACKWHITE:uint = 0x214;
	
	    /** Identifies the CFAREPEATPATTERNDIM tag */
	    public static const CFAREPEATPATTERNDIM:uint = 0x828D;
	
	    /** Identifies the CFAPATTERN tag */
	    public static const CFAPATTERN:uint = 0x828E;
	
	    /** Identifies the BATTERYLEVEL tag */
	    public static const BATTERYLEVEL:uint = 0x828F;
	
	    /** Identifies the COPYRIGHT tag */
	    public static const COPYRIGHT:uint = 0x8298;
	
	    /** Identifies the EXPOSURETIME tag */
	    public static const EXPOSURETIME:uint = 0x829A;
	
	    /** Identifies the FNUMBER tag */
	    public static const FNUMBER:uint = 0x829D;
	
	    /** Identifies the IPTC_NAA tag */
	    public static const IPTC_NAA:uint = 0x83BB;
	
	    /** Identifies the EXIFOFFSET tag */
	    public static const EXIFOFFSET:uint = 0x8769;
	
	    /** Identifies the ERCOLORPROFILE tag */
	    public static const INTERCOLORPROFILE:uint = 0x8773;
	
	    /** Identifies the EXPOSUREPROGRAM tag */
	    public static const EXPOSUREPROGRAM:uint = 0x8822;
	
	    /** Identifies the SPECTRALSENSITIVITY tag */
	    public static const SPECTRALSENSITIVITY:uint = 0x8824;
	
	    /** Identifies the GPSINFO tag */
	    public static const GPSINFO:uint = 0x8825;
	
	    /** Identifies the ISOSPEEDRATINGS tag */
	    public static const ISOSPEEDRATINGS:uint = 0x8827;
	
	    /** Identifies the OECF tag */
	    public static const OECF:uint = 0x8828;
	
	    /** Identifies the EXIFVERSION tag */
	    public static const EXIFVERSION:uint = 0x9000;
	
	    /** Identifies the DATETIMEORIGINAL tag */
	    public static const DATETIMEORIGINAL:uint = 0x9003;
	
	    /** Identifies the DATETIMEDIGITIZED tag */
	    public static const DATETIMEDIGITIZED:uint = 0x9004;
	
	    /** Identifies the COMPONENTSCONFIGURATION tag */
	    public static const COMPONENTSCONFIGURATION:uint = 0x9101;
	
	    /** Identifies the COMPRESSEDBITSPERPIXEL tag */
	    public static const COMPRESSEDBITSPERPIXEL:uint = 0x9102;
	
	    /** Identifies the SHUTTERSPEEDVALUE tag */
	    public static const SHUTTERSPEEDVALUE:uint = 0x9201;
	
	    /** Identifies the APERTUREVALUE tag */
	    public static const APERTUREVALUE:uint = 0x9202;
	
	    /** Identifies the BRIGHTNESSVALUE tag */
	    public static const BRIGHTNESSVALUE:uint = 0x9203;
	
	    /** Identifies the EXPOSUREBIASVALUE tag */
	    public static const EXPOSUREBIASVALUE:uint = 0x9204;
	
	    /** Identifies the MAXAPERTUREVALUE tag */
	    public static const MAXAPERTUREVALUE:uint = 0x9205;
	
	    /** Identifies the SUBJECTDISTANCE tag */
	    public static const SUBJECTDISTANCE:uint = 0x9206;
	
	    /** Identifies the METERINGMODE tag */
	    public static const METERINGMODE:uint = 0x9207;
	
	    /** Identifies the LIGHTSOURCE tag */
	    public static const LIGHTSOURCE:uint = 0x9208;
	
	    /** Identifies the FLASH tag */
	    public static const FLASH:uint = 0x9209;
	
	    /** Identifies the FOCALLENGTH tag */
	    public static const FOCALLENGTH:uint = 0x920A;
	
	    /** Identifies the MAKERNOTE tag */
	    public static const MAKERNOTE:uint = 0x927C;
	
	    /** Identifies the USERCOMMENT tag */
	    public static const USERCOMMENT:uint = 0x9286;
	
	    /** Identifies the SUBSECTIME tag */
	    public static const SUBSECTIME:uint = 0x9290;
	
	    /** Identifies the SUBSECTIMEORIGINAL tag */
	    public static const SUBSECTIMEORIGINAL:uint = 0x9291;
	
	    /** Identifies the SUBSECTIMEDIGITIZED tag */
	    public static const SUBSECTIMEDIGITIZED:uint = 0x9292;
	
	    /** Identifies the FLASHPIXVERSION tag */
	    public static const FLASHPIXVERSION:uint = 0xA000;
	
	    /** Identifies the COLORSPACE tag */
	    public static const COLORSPACE:uint = 0xA001;
	
	    /** Identifies the EXIFIMAGEWIDTH tag */
	    public static const EXIFIMAGEWIDTH:uint = 0xA002;
	
	    /** Identifies the EXIFIMAGELENGTH tag */
	    public static const EXIFIMAGELENGTH:uint = 0xA003;
	
	    /** Identifies the EROPERABILITYOFFSET tag */
	    public static const INTEROPERABILITYOFFSET:uint = 0xA005;
	
	    /** Identifies the FLASHENERGY tag */
	    public static const FLASHENERGY:uint = 0xA20B; // :uint = 0x920B in TIFF/EP
	
	    /** Identifies the SPATIALFREQUENCYRESPONSE tag */
	    public static const SPATIALFREQUENCYRESPONSE:uint = 0xA20C; // :uint = 0x920C    -  -
	
	    /** Identifies the FOCALPLANEXRESOLUTION tag */
	    public static const FOCALPLANEXRESOLUTION:uint = 0xA20E; // :uint = 0x920E    -  -
	
	    /** Identifies the FOCALPLANEYRESOLUTION tag */
	    public static const FOCALPLANEYRESOLUTION:uint = 0xA20F; // :uint = 0x920F    -  -
	
	    /** Identifies the FOCALPLANERESOLUTIONUNIT tag */
	    public static const FOCALPLANERESOLUTIONUNIT:uint = 0xA210; // :uint = 0x9210    -  -
	
	    /** Identifies the SUBJECTLOCATION tag */
	    public static const SUBJECTLOCATION:uint = 0xA214; // :uint = 0x9214    -  -
	
	    /** Identifies the EXPOSUREINDEX tag */
	    public static const EXPOSUREINDEX:uint = 0xA215; // :uint = 0x9215    -  -
	
	    /** Identifies the SENSINGMETHOD tag */
	    public static const SENSINGMETHOD:uint = 0xA217; // :uint = 0x9217    -  -
	
	    /** Identifies the FILESOURCE tag */
	    public static const FILESOURCE:uint = 0xA300;
	
	    /** Identifies the SCENETYPE tag */
	    public static const SCENETYPE:uint = 0xA301;
	
	    /** Identifies the FOCALLENGTHIN35MMFILM tag */
	    public static const FOCALLENGTHIN35MMFILM:uint = 0xA405;
	
	    /** Identifies the SHARPNESS tag */
	    public static const SHARPNESS:uint = 0xA40A;
	
	    /** Identifies the CUSTOMRENDERED tag */
	    public static const CUSTOMRENDERED:uint = 0xA401;
	
	    /** Identifies the EXPOSUREMODE tag */
	    public static const EXPOSUREMODE:uint = 0xA402;
	
	    /** Identifies the WHITEBALANCE tag */
	    public static const WHITEBALANCE:uint = 0xA403;
	
	    /** Identifies the DIGITALZOOMRATIO tag */
	    public static const DIGITALZOOMRATIO:uint = 0xA404;
	
	    /** Identifies the SATURATION tag */
	    public static const SATURATION:uint = 0xA409;
	
	    /** Identifies the SCENECAPTURETYPE tag */
	    public static const SCENECAPTURETYPE:uint = 0xA406;
	
	    /** Identifies the GAINCONTROL tag */
	    public static const GAINCONTROL:uint = 0xA407;
	
	    /** Identifies the CONTRAST tag */
	    public static const CONTRAST:uint = 0xA408;
	
	    /** Identifies the PRINTMODE tag */
	    public static const PRINTMODE:uint = 0xC4A5;
	
	    // Exif directory type of tag definition
	    /** Identifies the Byte Data Type */
	    public static const BYTE:uint = 1;
	
	    /** Identifies the ASCII Data Type */
	    public static const ASCII:uint = 2;
	
	    /** Identifies the  SHORT Data Type */
	    public static const SHORT:uint = 3;
	
	    /** Identifies the LONG Data Type */
	    public static const LONG:uint = 4;
	
	    /** Identifies the RATIONAL Data Type */
	    public static const RATIONAL:uint = 5;
	
	    /** Identifies the Signed BYTE Data Type */
	    public static const SBYTE:uint = 6;
	
	    /** Identifies the UNDEFINED Data Type */
	    public static const UNDEFINED:uint = 7;
	
	    /** Identifies the Signed SHORT Data Type */
	    public static const SSHORT:uint = 8;
	
	    /** Identifies the Signed LONG Data Type */
	    public static const SLONG:uint = 9;
	
	    /** Identifies the Signed RATIONAL Data Type */
	    public static const SRATIONAL:uint = 10;
	
	    public static const ORIENTATION_TOPLEFT:uint = 1;
	
	    public static const ORIENTATION_TOPRIGHT:uint = 2;
	
	    public static const ORIENTATION_BOTRIGHT:uint = 3;
	
	    public static const ORIENTATION_BOTLEFT:uint = 4;
	
	    public static const ORIENTATION_LEFTTOP:uint = 5;
	
	    public static const ORIENTATION_RIGHTTOP:uint = 6;
	
	    public static const ORIENTATION_RIGHTBOT:uint = 7;
	
	    public static const ORIENTATION_LEFTBOT:uint = 8;
		
		// Events dispatched by the class
		public static const PARSE_COMPLETE : String = "parseComplete";
		public static const PARSE_FAILED : String = "parseFailed";
		public static const PARSING_ERRORS : String = "parseErrors";
		public static const DATA_READY : String = "dataReady";
		public static const THUMBNAIL_READY : String = "thumbnailReady";
		
		// Calculation values
		private const EXIF_HEADER:Array = [0x45, 0x78, 0x69, 0x66, 0x00, 0x00];
		private const BYTES_PER_FORMAT:Array = [null, 1, 1, 2, 4, 8, 1, 1, 2, 4, 8, 4, 8 ];		
		private const K_BYTES:int = 64;
		
		// Exif core informations
		private var rawData:ByteArray = new ByteArray();
		private var headerData:ByteArray = new ByteArray();
		private var intelOrder:Boolean;
		private var headerDataLenght:uint;
		
		// Thumb raw data parser
		private var thumbLoader:Loader;
		
		private var _dataLoaded:Number;
		
		// Parsing variables
		private var subexif:uint;
		private var gps:uint;
		private var _thumbnailSize:uint;
		private var _thumbnailAddress:uint;
		private var exifStart:int;
		
		// Naming parsing for human readable values, 
		// extend this class for your localized version
		private var naming:Naming;
		
		// The current Image File Directory
		private var currentIFD:IFD;
		
		// The overall IFD stored in the image
		private var _availableIFDs:Array;
		
		// Error log queue
		private var _errorLogQueue:String;
		
		// Current loaded file
		private var _fileName:String;
		
		// Dictionary of duplicates
		private var stored:Dictionary;
		
		public function Exif(namingReference:Naming = null){
			
			super();
			
			if(namingReference){
				
				naming = namingReference;
				
			}else{
				
				naming = new Naming();
				
			}
			
			_errorLogQueue = "";
			
			_availableIFDs = [];
			
			stored = new Dictionary(true);
			
		}
		public var hasThumbnail:Boolean = false;
		protected function initParsing():void{
	
			if(!isJPG()){
				
				dispatchEvent(new Event(PARSE_FAILED));
				return;
				
			}
			
			if(app1Marker() != 0xe1){
				
				dispatchEvent(new Event(PARSE_FAILED));
				return;
				
			}
			
			if(!isExif()){
				
				dispatchEvent(new Event(PARSE_FAILED));
				return;
				
			}
				
			// Very important value used to start the value extraction
			exifStart = rawData.position;
			
			// Read the bytes one by one in order to get the header info
			parseBytes(headerData, rawData, exifStart);
			
			headerData.position = 4;
			
			// This has to be confirmed but actually from the first tests
			// seems that when the exif is not starting at the 12 byte the
			// lenght of the information is stored with a different format
			if(exifStart > 12){
				
				headerDataLenght = headerData.readUnsignedByte();
				
			}else{
				
				headerDataLenght = headerData.readUnsignedShort();
				
			}
			
			trace("EXIF head length: " + headerDataLenght, rawData.length);
			
			// Store the exif information in a single byte array
			rawData.readBytes(headerData, 0, headerDataLenght);
			
			trace("headerData length:" + headerData.length + " bytes", headerData.position)
			
			// Intel images starts with the II, motorola ones with MM
			intelOrder = (headerData[0] != 0x4d);
				
			trace("is intel", intelOrder)
			
			// The kind of processor change the endian of the exif data
			if(intelOrder){
				
				headerData.endian = Endian.LITTLE_ENDIAN;
				
			}else{
				
				headerData.endian = Endian.BIG_ENDIAN;
				
			}
			
			// Recover the position of the first Image File Directory (IFD)
			var ifd0:uint = getIFD0Position(headerData);
			
			trace("ifd0 value", ifd0);
			
			var ifdn:uint
			
			// If there are others data get the position of the second Image File Directory (IFD)
			if (ifd0 < headerData.length) {
				
				ifdn = getIFDN(ifd0, headerData);
				trace("ifdn value", ifdn);
				
			}
			
			// IFD0 Entries
			headerData.position = ifd0;
			var ifd0Entries:uint =  get16s();
			
			trace("\nifd0 entries", ifd0Entries, "\n");
			
			// The IFD0 tags container
			currentIFD = new IFD();
			_availableIFDs.push(currentIFD);
			
			exploreEntries(ifd0Entries);
			
			if (ifdn < headerData.length) {
			
				// IFD1 Entries
				headerData.position = ifdn;
				var ifd1Entries:uint =  get16s();
				trace("\nifd1 Entries", ifd1Entries, "\n");
				
				// The IFD1 tags container
				currentIFD = new IFD();
				_availableIFDs.push(currentIFD);
				
				exploreEntries(ifd1Entries);
				
			}
			
			// If the parsing has found a sub exif tag (another IFD) then explore it
			if(subexif){
				
				headerData.position = subexif;
				
				// The subexif tags container
				currentIFD = new IFD();
				_availableIFDs.push(currentIFD);
				
				exploreEntries(get16s());
				
			}
			
			// If the parsing has found a gps tag (another IFD) then explore it
			if(gps){
				
				headerData.position = gps;
				
				// The gps tags container
				currentIFD = new IFD();
				_availableIFDs.push(currentIFD);
				
				exploreEntries(get16s());
				
			}
			
			// Actually it get only JPG thumb
			// TDOD parse also bitmap thumb
			this.hasThumbnail = false;
			if(_thumbnailAddress){
				
				headerData.position = _thumbnailAddress
								
				var _thumbnailData:ByteArray = new ByteArray();
				headerData.readBytes(_thumbnailData, 0, _thumbnailSize);
						
				// Load the thumb and make it available
				if(!thumbLoader){
					this.hasThumbnail = true;		
					thumbLoader = new Loader();
					thumbLoader.contentLoaderInfo.addEventListener(Event.COMPLETE, onThumbLoaded);
					thumbLoader.loadBytes(_thumbnailData);
							
				}
				
			}
			
			// Data are ready
			dispatchEvent(new Event(DATA_READY));
			
			// Not blocking errors have been detected and stored
			if(_errorLogQueue.length > 0){
				
				dispatchEvent(new Event(PARSING_ERRORS));
				
			}
			
		}
		
						
		/**
		 * Handling of the internal events 
		 */		
		protected function onComplete(e:Event):void{
			
			e.target.removeEventListener(e.type, arguments.callee);
			
			_dataLoaded = bytesAvailable; 
			
			readBytes(rawData, 0, bytesAvailable);
			close();
			
			initParsing();
			
		}
				
		protected function onProgress(e:ProgressEvent):void{
	
			if(bytesAvailable >= 12 + K_BYTES * 1024){
				
				removeEventListener(e.type, arguments.callee);
				removeEventListener(Event.COMPLETE, onComplete);
				
				_dataLoaded = bytesAvailable;
				
				readBytes(rawData, 0, 12 + K_BYTES * 1024);
				
				close();
				
				initParsing();
				
			}
			
		}
		
		protected function onThumbLoaded(e:Event):void{
			
			e.target.removeEventListener(e.type, arguments.callee);
			
			dispatchEvent(new Event(THUMBNAIL_READY));
						
		}
		
		/**
		 * Parsing of the main data
		 */		
		private function isExif():Boolean{
			
			var byte:uint;
			
			for (var i:int = 0; i < EXIF_HEADER.length; i++) {
						
				byte = rawData.readUnsignedByte();
						
				if (byte != EXIF_HEADER[i]){
							
					return false;
							
				} 
						
			}
					
			return true;
					
		}
		
		/**
		 * Get the starting point of the TIF data
		 */
		private function app1Marker():uint{
					
			var marker:uint = 0;
					
			for(var i:int = 0; i < 5; ++i){	//cap iterations
						
				rawData.position += 1
				marker = rawData.readUnsignedByte();
						
				var size:uint = (rawData.readUnsignedByte()<<8) + rawData.readUnsignedByte();
						
				if(marker == 0x00e1){
							
					break;
							
				}else{
							
					for(var x:int = 0; x < size - 2; ++x){
								
						rawData.readUnsignedByte();
								
					} 
				}
			}
				
			return marker
			
		}
		
		/**
		 * Check if it's a JPG, the first two bytes has to be
		 * the values 0xff and 0xd8
		 */		
		private function isJPG():Boolean{
			
			var value:Boolean;
			
			if(!(rawData.readUnsignedByte() == 0xff && rawData.readUnsignedByte() == 0xd8)) {
						
				value = false;
						
			}else{
				
				value = true;
				
			}
								
			return value;
			
		}
		
		private function exploreEntries(entries:uint):void{
	
			for(var i:int = 0; i < entries; i++){
						
				try {
					var currentTag:uint = get16s();
					var currentType:uint = get16s();
					var currentCount:uint = get32s();
				}catch(e:Error){
					_errorLogQueue +=  "*****************\n" +
						e.message + "\n" +
						"file: " + _fileName + "\n" + 
						"*****************\n";
					dispatchEvent(new Event(PARSE_FAILED));
					break;
				}
				
				var currentValue:*;
				
				try{
					
					if (currentTag == 0x8769) {
						
						subexif = parseTag(currentType, headerData, currentCount);
					
					} else if(currentTag == 0x8825) {
						
						gps = parseTag(currentType, headerData, currentCount);
						
					} else if(currentTag == 0x0201) {
						
						_thumbnailAddress = parseTag(currentType, headerData, currentCount);
					
					} else if(currentTag == 0x0202) {
						
						_thumbnailSize = parseTag(currentType, headerData, currentCount);
					
					}else{
					
						currentValue = parseTag(currentType, headerData, currentCount);
						
						var processing:ExifTag = naming.tagsTable.find(currentTag);
						
						if(processing && !stored[currentTag]){
							if(currentTag == Exif.ORIENTATION){
								if(currentValue >= 1 && currentValue <= 8){
									processing.rawValue = currentValue;								
									processing.value = naming.orientationNames[currentValue];
								}else{
									processing.rawValue = 0;
									processing.value = naming.orientationNames[0];
								}
								
							}else if(currentTag == Exif.COLORSPACE){
									if(currentValue == 1){
										processing.rawValue = currentValue;
										processing.value = naming.colorSpaceNames[1];
										
									}else if(currentValue == 0xffff){
										processing.rawValue = 0;
										processing.value = naming.colorSpaceNames[0];
										
									}else{
										processing.rawValue = 2;
										processing.value = naming.colorSpaceNames[2];
									}
									
								}else if(currentTag == Exif.YCBCRPOSITIONING){
								
									if(currentValue == 1 || currentValue == 2){
										processing.rawValue = currentValue;										
										processing.value = naming.ycbcrPositionNames[currentValue];
									}else{
										processing.rawValue = 0;
										processing.value = naming.ycbcrPositionNames[0];
									}
								}else if(currentTag == Exif.METERINGMODE){
									processing.rawValue = currentValue;
									if(currentValue >= 0 && currentValue <= 6){
										processing.value = naming.meteringModeNames[currentValue];
									}else if(currentValue == 255){
										processing.value = naming.meteringModeNames[7];
									}else{
										processing.value = naming.meteringModeNames[8];
									}
								}else if(currentTag == Exif.FLASH){
									processing.rawValue = currentValue;
									processing.value = naming.flashStatusNames[currentValue] || naming.flashStatusNames[99];
								}else if(currentTag == Exif.RESOLUTIONUNIT){
									if(currentValue == 2 || currentValue == 3){
										processing.rawValue = currentValue -1;
										processing.value = naming.resolutionNames[currentValue - 1];
									}else{
										processing.rawValue = 0;
										processing.value = naming.resolutionNames[0];
									}
									
								}else if(currentTag == Exif.EXPOSUREPROGRAM){
								
									if(currentValue >= 0 && currentValue <= 7){
										processing.rawValue = currentValue;
										processing.value = naming.exposureNames[currentValue];
										
									}else{
										processing.rawValue = 8;
										processing.value = naming.exposureNames[8];
										
									}
								
								}else if(currentTag == Exif.FLASHPIXVERSION){
								
									if(currentValue == 0x64){
										processing.rawValue = 0;
										processing.value = naming.flashPixNames[0];
										
									}else{
										processing.rawValue = 1;
										processing.value = naming.flashPixNames[1];
										
									}
								
								}else{
									processing.rawValue = currentValue;
									processing.value = currentValue;
								
							}
							
							// Store the recovered value
							currentIFD.addEntry(processing);
							
							stored[currentTag] = {};
							
						}
						
					}
					
				}catch(e:Error){
					
					_errorLogQueue +=  "*****************\n" +
									   e.message + "\n" +
									   "uint: " + currentTag + "\n" +
									   "current tag: 0x" + currentTag.toString(16) + "\n" +
									   "type: " + currentType + "\n" +
									   "currentCount:" + currentCount + "\n" +
									   "file: " + _fileName + "\n" + 
									   "*****************\n"
				
					break;
				}
				
			//	trace(currentTag, "current tag: 0x" + currentTag.toString(16), "type: " + currentType, "currentCount:", currentCount, "*** " + currentValue + " ***")
									
			}
			
		}
		
		/**
		 * Parse the value of each single tag considering the exif datatypes
		 * (1) BYTE
         * (2) ASCII
         * (3) SHORT
		 * (4) LONG
         * (5) RATIONAL
	     * (7) UNDEFINED
		 * (9) SLONG
		 * (10) SRATIONAL 
		 * @param type uint
		 * @param currentData ByteArray
		 * @param count uint
		 * @return *
		 */		
		private function parseTag(type:uint, currentData:ByteArray, count:uint):*{
		
			var value:*;
			
			var addressManager:AddressManager;
			
			// Just in case we need to parse some data
			var tempData:ByteArray = new ByteArray();
			var marker:uint;
			
			switch(true){
				
				case type == 1:
				value = get16s()
				break;
				
				case type == 2:
				value = eightBitParser(count, currentData);
				break;
				
				case type == 3:
				value = currentData.readShort()//get32s();
				currentData.position += 2;
				break;
				
				case type == 4:
				value = get32s();
				break;
				
				case type == 5:
				
				marker = currentData.readUnsignedInt();
				addressManager = new AddressManager(currentData, marker)
				
				var numerator:uint = currentData.readInt();
				var denominator:uint = currentData.readUnsignedInt();
				
				addressManager.restore();
														
				value = numerator / denominator;
				break;
				
				case type == 7:
				value = eightBitParser(count, currentData);
				break;
				
				case type == 9:
				value = get32s();
				break;
				
				case type == 10:
				
				marker = currentData.readInt();
				addressManager = new AddressManager(currentData, marker)
			
				value = currentData.readInt() / currentData.readInt();
				
				addressManager.restore();
														
				break;
				
				
			}
		
			return value;
			
		}
		
		/**
		 * Parser defined for the ASCII and UNDEFINED data types of the exif format,
		 * usually in this kind of tags there are strings like date, printim, make, etc. 
		 * @param count uint
		 * @param data ByteArray
		 * @return Strin
		 * 
		 */		
		private function eightBitParser(count:uint, data:ByteArray):String{
			
			var addressManager:AddressManager;
			var tempData:ByteArray = new ByteArray();
			var marker:uint;
			
			var value:String;
				
			if(count <= 4){
				
				var tempStr:String = "";
			
				for (var j:int = 0; j < count; j++) {
							
					var asc:uint = data.readUnsignedByte();
					tempStr += String.fromCharCode(asc);
						
				}
					
				value = tempStr;
				
			}else{
					
				marker = data.readUnsignedInt();
					
				addressManager = new AddressManager(data, marker);
					
				data.readBytes(tempData, 0, count);
				addressManager.restore();
					
				value = tempData.toString();
					
			}
			
			return value;
			
		}
		/**
		 * Get additional IFD position, check here if the exifStart
		 * usage (instead of the old 12) is correct in production
		 * @param startPos uint
		 * @param byteData ByteArray
		 * @return uint
		 */		
		private function getIFDN(startPos:uint, byteData:ByteArray):uint {
					
			trace("\tgetIFDN", startPos, byteData.position)
					
			var entries:uint = byteData.readUnsignedShort();
			byteData.position = startPos + 2 + 12 * entries;
					
			return byteData.readUnsignedInt();
		
		}
		
		/**
		 * Recover 32 bit (4-byte) values considering the kind of endian (intel or motorola)
		 * and shift the values correctly 
		 * @return uint
		 */				
		private function get32s():uint{
					
			var value:uint;
			
			if(intelOrder){
						
				value = headerData.readUnsignedByte() + (headerData.readUnsignedByte() << 8) + (headerData.readUnsignedByte() << 16) + (headerData.readUnsignedByte() << 24);
						
			}else{
						
				value = (headerData.readUnsignedByte() << 24) + (headerData.readUnsignedByte() << 16) + (headerData.readUnsignedByte() << 8) + (headerData.readUnsignedByte() << 0);
						
			} 
					
			return value;
					
		}
		
		/**
		 * Recover 16 bit (2-byte) values considering the kind of endian (intel or motorola)
		 * and shift the values correctly 
		 * @return uint
		 */		
		private function get16s():uint{
					
			var value:uint;
					
			if(intelOrder) {
						
				value = (headerData.readUnsignedByte()) + (headerData.readUnsignedByte() << 8);
					
			}else{
						
				value = (headerData.readUnsignedByte() << 8) + (headerData.readUnsignedByte() << 0);
						
			} 
					
			return value;
					
		}
		
		/**
		 * Recover the index of the IFD0 element of the exif data 
		 * @param data ByteArray
		 * @return uint
		 * 
		 */		
		private function getIFD0Position(data:ByteArray):uint {
				
				data.position = 4;
				
				return data.readUnsignedInt();
				
		}
		
		private function parseBytes(ba:ByteArray, source:ByteArray, length:uint, start:uint = 0):void{
			
			for(var i:int = start; i < start + length; i++){
				
				var byte:uint = source[i];
				ba.writeByte(byte);
				
			}
			
		}
		
		/**
		 * Load and automatically define the listeners needed
		 * to parse the exif information of the image 
		 * @param url URLRequest
		 */		
		override public function load(url:URLRequest) : void {
			
			addEventListener( ProgressEvent.PROGRESS, onProgress);
			addEventListener( Event.COMPLETE, onComplete);
			
			_fileName = url.url;
			
			super.load(url);
			
		}
		
		/**
		 * Public API available to the end user in order
		 * to get the infromation about the exif data
		 */
		public function get loaded( ) : uint {
			
			return _dataLoaded;
			
		}
		
		public function get availableIFDs():Array{
			
			return _availableIFDs;
			
		}
		
		public function get thumnailLoader():Loader{
			return thumbLoader;
		}		
		public function get thumbnailData():BitmapData{
			
			var image:Bitmap = Bitmap(thumbLoader.content);
			var bitmapData:BitmapData = image.bitmapData;

			return bitmapData;
			
		}
		
		public function get errorLogQueue():String{
			
			return _errorLogQueue;
			
		}
		
		public function getTagValue(tag:uint):ExifTag{
			
			return stored[tag]
			
		}
		
	}
}