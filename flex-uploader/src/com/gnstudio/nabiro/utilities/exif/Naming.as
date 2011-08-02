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
	
	import com.gnstudio.nabiro.utilities.data.HashTable;
	
	public class Naming
	{
		
		private var _tagsTable:HashTable;
		
		protected var _orientationNames:Array;
		protected var _colorSpaceNames:Array;
		protected var _ycbcrPositionNames:Array;
		protected var _meteringModeNames:Array;
		protected var _flashStatusNames:Array;
		protected var _resolutionNames:Array;
		protected var _exposureNames:Array;
		protected var _flashPixNames:Array;
		
		public function Naming(){
			
			_tagsTable = new HashTable();
			
			_tagsTable.insert(Exif.NEWSUBFILETYPE, new ExifTag(Exif.NEWSUBFILETYPE, "NewSubFileType"));
	        _tagsTable.insert(Exif.IMAGEWIDTH, new ExifTag(Exif.IMAGEWIDTH, "ImageWidth"));
	        _tagsTable.insert(Exif.IMAGELENGTH, new ExifTag(Exif.IMAGELENGTH, "ImageLength"));
	        _tagsTable.insert(Exif.BITSPERSAMPLE, new ExifTag(Exif.BITSPERSAMPLE, "BitsPerSample"));
	        _tagsTable.insert(Exif.COMPRESSION, new ExifTag(Exif.COMPRESSION, "Compression"));
	        _tagsTable.insert(Exif.PHOTOMETRICINTERPRETATION, new ExifTag(Exif.PHOTOMETRICINTERPRETATION, "PhotometricInterpretation"));
	        _tagsTable.insert(Exif.FILLORDER, new ExifTag(Exif.FILLORDER ,	"FillOrder"));
	        _tagsTable.insert(Exif.DOCUMENTNAME, new ExifTag(Exif.DOCUMENTNAME ,	"DocumentName"));
	        _tagsTable.insert(Exif.IMAGEDESCRIPTION, new ExifTag(Exif.IMAGEDESCRIPTION ,	"ImageDescription"));
	        _tagsTable.insert(Exif.MAKE, new ExifTag(Exif.MAKE ,	"Make"));
	        _tagsTable.insert(Exif.MODEL, new ExifTag(Exif.MODEL ,	"Model"));
	        _tagsTable.insert(Exif.STRIPOFFSETS, new ExifTag(Exif.STRIPOFFSETS ,	"StripOffsets"));
	        _tagsTable.insert(Exif.ORIENTATION, new ExifTag(Exif.ORIENTATION ,	"Orientation"));
	        _tagsTable.insert(Exif.SAMPLESPERPIXEL, new ExifTag(Exif.SAMPLESPERPIXEL ,	"SamplesPerPixel"));
	        _tagsTable.insert(Exif.ROWSPERSTRIP, new ExifTag(Exif.ROWSPERSTRIP ,	"RowsPerStrip"));
	        _tagsTable.insert(Exif.STRIPBYTECOUNTS, new ExifTag(Exif.STRIPBYTECOUNTS ,	"StripByteCounts"));
	        _tagsTable.insert(Exif.XRESOLUTION, new ExifTag(Exif.XRESOLUTION ,	"XResolution"));
	        _tagsTable.insert(Exif.YRESOLUTION, new ExifTag(Exif.YRESOLUTION ,	"YResolution"));
	        _tagsTable.insert(Exif.PLANARCONFIGURATION, new ExifTag(Exif.PLANARCONFIGURATION ,	"PlanarConfiguration"));
	        _tagsTable.insert(Exif.RESOLUTIONUNIT, new ExifTag(Exif.RESOLUTIONUNIT ,	"ResolutionUnit"));
	        _tagsTable.insert(Exif.TRANSFERFUNCTION, new ExifTag(Exif.TRANSFERFUNCTION ,	"TransferFunction"));
	        _tagsTable.insert(Exif.SOFTWARE, new ExifTag(Exif.SOFTWARE ,	"Software"));
	        _tagsTable.insert(Exif.DATETIME, new ExifTag(Exif.DATETIME ,	"DateTime"));
	        _tagsTable.insert(Exif.ARTIST, new ExifTag(Exif.ARTIST ,	"Artist"));
	        _tagsTable.insert(Exif.WHITEPOINT, new ExifTag(Exif.WHITEPOINT ,	"WhitePoint"));
	        _tagsTable.insert(Exif.PRIMARYCHROMATICITIES, new ExifTag(Exif.PRIMARYCHROMATICITIES ,	"PrimaryChromaticities"));
	        _tagsTable.insert(Exif.SUBIFDS, new ExifTag(Exif.SUBIFDS ,	"SubIFDs"));
	        _tagsTable.insert(Exif.JPEGTABLES, new ExifTag(Exif.JPEGTABLES ,	"JPEGTables"));
	        _tagsTable.insert(Exif.TRANSFERRANGE, new ExifTag(Exif.TRANSFERRANGE ,	"TransferRange"));
	        _tagsTable.insert(Exif.JPEGPROC, new ExifTag(Exif.JPEGPROC ,	"JPEGProc"));
	        _tagsTable.insert(Exif.JPEGINTERCHANGEFORMAT, new ExifTag(Exif.JPEGINTERCHANGEFORMAT ,	"JPEGInterchangeFormat"));
	        _tagsTable.insert(Exif.JPEGINTERCHANGEFORMATLENGTH, new ExifTag(Exif.JPEGINTERCHANGEFORMATLENGTH ,	"JPEGInterchangeFormatLength"));
	        _tagsTable.insert(Exif.YCBCRCOEFFICIENTS, new ExifTag(Exif.YCBCRCOEFFICIENTS ,	"YCbCrCoefficients"));
	        _tagsTable.insert(Exif.YCBCRSUBSAMPLING, new ExifTag(Exif.YCBCRSUBSAMPLING ,	"YCbCrSubSampling"));
	        _tagsTable.insert(Exif.YCBCRPOSITIONING, new ExifTag(Exif.YCBCRPOSITIONING ,	"YCbCrPositioning"));
	        _tagsTable.insert(Exif.REFERENCEBLACKWHITE, new ExifTag(Exif.REFERENCEBLACKWHITE ,	"ReferenceBlackWhite"));
	        _tagsTable.insert(Exif.CFAREPEATPATTERNDIM, new ExifTag(Exif.CFAREPEATPATTERNDIM ,	"CFARepeatPatternDim"));
	        _tagsTable.insert(Exif.CFAPATTERN, new ExifTag(Exif.CFAPATTERN ,	"CFAPattern"));
	        _tagsTable.insert(Exif.BATTERYLEVEL, new ExifTag(Exif.BATTERYLEVEL ,	"BatteryLevel"));
	        _tagsTable.insert(Exif.COPYRIGHT, new ExifTag(Exif.COPYRIGHT ,	"Copyright"));
	        _tagsTable.insert(Exif.EXPOSURETIME, new ExifTag(Exif.EXPOSURETIME,	"ExposureTime"));
	        _tagsTable.insert(Exif.FNUMBER, new ExifTag(Exif.FNUMBER ,	"FNumber"));
	        _tagsTable.insert(Exif.IPTC_NAA, new ExifTag(Exif.IPTC_NAA ,	"IPTC/NAA"));
	        _tagsTable.insert(Exif.EXIFOFFSET, new ExifTag(Exif.EXIFOFFSET ,	"ExifOffset"));
	        _tagsTable.insert(Exif.INTERCOLORPROFILE, new ExifTag(Exif.INTERCOLORPROFILE ,	"InterColorProfile"));
	        _tagsTable.insert(Exif.EXPOSUREPROGRAM, new ExifTag(Exif.EXPOSUREPROGRAM ,	"ExposureProgram"));
	        _tagsTable.insert(Exif.SPECTRALSENSITIVITY, new ExifTag(Exif.SPECTRALSENSITIVITY ,	"SpectralSensitivity"));
	        _tagsTable.insert(Exif.GPSINFO, new ExifTag(Exif.GPSINFO ,	"GPSInfo"));
	        _tagsTable.insert(Exif.ISOSPEEDRATINGS, new ExifTag(Exif.ISOSPEEDRATINGS ,	"ISOSpeedRatings"));
	        _tagsTable.insert(Exif.OECF, new ExifTag(Exif.OECF ,	"OECF"));
	        _tagsTable.insert(Exif.EXIFVERSION, new ExifTag(Exif.EXIFVERSION ,	"ExifVersion"));
	        _tagsTable.insert(Exif.DATETIMEORIGINAL, new ExifTag(Exif.DATETIMEORIGINAL ,	"DateTimeOriginal"));
	        _tagsTable.insert(Exif.DATETIMEDIGITIZED, new ExifTag(Exif.DATETIMEDIGITIZED ,	"DateTimeDigitized"));
	        _tagsTable.insert(Exif.COMPONENTSCONFIGURATION, new ExifTag(Exif.COMPONENTSCONFIGURATION ,	"ComponentsConfiguration"));
	        _tagsTable.insert(Exif.COMPRESSEDBITSPERPIXEL, new ExifTag(Exif.COMPRESSEDBITSPERPIXEL ,	"CompressedBitsPerPixel"));
	        _tagsTable.insert(Exif.SHUTTERSPEEDVALUE, new ExifTag(Exif.SHUTTERSPEEDVALUE ,	"ShutterSpeedValue"));
	        _tagsTable.insert(Exif.APERTUREVALUE, new ExifTag(Exif.APERTUREVALUE ,	"ApertureValue"));
	        _tagsTable.insert(Exif.BRIGHTNESSVALUE, new ExifTag(Exif.BRIGHTNESSVALUE ,	"BrightnessValue"));
	        _tagsTable.insert(Exif.EXPOSUREBIASVALUE, new ExifTag(Exif.EXPOSUREBIASVALUE ,	"ExposureBiasValue"));
	        _tagsTable.insert(Exif.MAXAPERTUREVALUE, new ExifTag(Exif.MAXAPERTUREVALUE ,	"MaxApertureValue"));
	        _tagsTable.insert(Exif.SUBJECTDISTANCE, new ExifTag(Exif.SUBJECTDISTANCE ,	"SubjectDistance"));
	        _tagsTable.insert(Exif.METERINGMODE, new ExifTag(Exif.METERINGMODE ,	"MeteringMode"));
	        _tagsTable.insert(Exif.LIGHTSOURCE, new ExifTag(Exif.LIGHTSOURCE ,	"LightSource"));
	        _tagsTable.insert(Exif.FLASH, new ExifTag(Exif.FLASH ,	"Flash"));
	        _tagsTable.insert(Exif.FOCALLENGTH, new ExifTag(Exif.FOCALLENGTH ,	"FocalLength"));
	        _tagsTable.insert(Exif.MAKERNOTE, new ExifTag(Exif.MAKERNOTE ,	"MakerNote"));
	        _tagsTable.insert(Exif.USERCOMMENT, new ExifTag(Exif.USERCOMMENT ,	"UserComment"));
	        _tagsTable.insert(Exif.SUBSECTIME, new ExifTag(Exif.SUBSECTIME ,	"SubSecTime"));
	        _tagsTable.insert(Exif.SUBSECTIMEORIGINAL, new ExifTag(Exif.SUBSECTIMEORIGINAL ,	"SubSecTimeOriginal"));
	        _tagsTable.insert(Exif.SUBSECTIMEDIGITIZED, new ExifTag(Exif.SUBSECTIMEDIGITIZED ,	"SubSecTimeDigitized"));
	        _tagsTable.insert(Exif.FLASHPIXVERSION, new ExifTag(Exif.FLASHPIXVERSION ,	"FlashPixVersion"));
	        _tagsTable.insert(Exif.COLORSPACE, new ExifTag(Exif.COLORSPACE ,	"ColorSpace"));
	        _tagsTable.insert(Exif.EXIFIMAGEWIDTH, new ExifTag(Exif.EXIFIMAGEWIDTH ,	"ExifImageWidth"));
	        _tagsTable.insert(Exif.EXIFIMAGELENGTH, new ExifTag(Exif.EXIFIMAGELENGTH ,	"ExifImageLength"));
	        _tagsTable.insert(Exif.INTEROPERABILITYOFFSET, new ExifTag(Exif.INTEROPERABILITYOFFSET ,	"InteroperabilityOffset"));
	        _tagsTable.insert(Exif.FLASHENERGY, new ExifTag(Exif.FLASHENERGY ,	"FlashEnergy"));
	        _tagsTable.insert(Exif.SPATIALFREQUENCYRESPONSE, new ExifTag(Exif.SPATIALFREQUENCYRESPONSE ,	"SpatialFrequencyResponse"));
	        _tagsTable.insert(Exif.FOCALPLANEXRESOLUTION, new ExifTag(Exif.FOCALPLANEXRESOLUTION ,	"FocalPlaneXResolution"));
	        _tagsTable.insert(Exif.FOCALPLANEYRESOLUTION, new ExifTag(Exif.FOCALPLANEYRESOLUTION ,	"FocalPlaneYResolution"));
	        _tagsTable.insert(Exif.FOCALPLANERESOLUTIONUNIT, new ExifTag(Exif.FOCALPLANERESOLUTIONUNIT ,	"FocalPlaneResolutionUnit"));
	        _tagsTable.insert(Exif.SUBJECTLOCATION, new ExifTag(Exif.SUBJECTLOCATION,	"SubjectLocation"));
	        _tagsTable.insert(Exif.EXPOSUREINDEX, new ExifTag(Exif.EXPOSUREINDEX ,	"ExposureIndex"));
	        _tagsTable.insert(Exif.SENSINGMETHOD, new ExifTag(Exif.SENSINGMETHOD ,	"SensingMethod"));
	        _tagsTable.insert(Exif.FILESOURCE, new ExifTag(Exif.FILESOURCE ,	"FileSource"));
	        _tagsTable.insert(Exif.SCENETYPE, new ExifTag(Exif.SCENETYPE ,	"SceneType"));
	        _tagsTable.insert(Exif.FOCALLENGTHIN35MMFILM, new ExifTag(Exif.FOCALLENGTHIN35MMFILM ,  "FocalLengthIn35mmFilm"));
	        _tagsTable.insert(Exif.SHARPNESS, new ExifTag(Exif.SHARPNESS ,  "Sharpness"));
	        _tagsTable.insert(Exif.CUSTOMRENDERED, new ExifTag(Exif.CUSTOMRENDERED ,  "CustomRendered"));
	        _tagsTable.insert(Exif.SATURATION, new ExifTag(Exif.SATURATION ,  "Saturation"));
	        _tagsTable.insert(Exif.WHITEBALANCE, new ExifTag(Exif.WHITEBALANCE ,  "WhiteBalance"));
	        _tagsTable.insert(Exif.DIGITALZOOMRATIO, new ExifTag(Exif.DIGITALZOOMRATIO ,  "DigitalZoomRatio"));
	        _tagsTable.insert(Exif.CONTRAST, new ExifTag(Exif.CONTRAST ,  "Contrast"));
	        _tagsTable.insert(Exif.GAINCONTROL, new ExifTag(Exif.GAINCONTROL ,  "GainControl"));
	        _tagsTable.insert(Exif.EXPOSUREMODE, new ExifTag(Exif.EXPOSUREMODE ,  "ExposureMode"));
	        _tagsTable.insert(Exif.DIGITALZOOMRATIO, new ExifTag(Exif.DIGITALZOOMRATIO ,  "DigitalZoomRatio"));
	        _tagsTable.insert(Exif.PRINTMODE, new ExifTag(Exif.PRINTMODE ,  "PrintMode"));
	        _tagsTable.insert(Exif.SCENECAPTURETYPE, new ExifTag(Exif.SCENECAPTURETYPE ,  "SceneCaptureType"));
	        
	        initOrientationNames();
	        
	       	initColorSpaceNames();
	       	
	       	initYcbcrPositionNames();
	       	
	       	initMeteringMode();
	       	
	       	initFlashStatusNames();
						
			initResolutionNames();
			
			initExposureNames();
			
			initExposureNames();
			
			initFlashPixNames();
			
		}
		
		
		protected function initOrientationNames():void{
			
			_orientationNames = ["reserved", "TopLeft", "TopRight", "BotRight", "BotLeft", "LeftTop", "RightTop", "RightBot", "LeftBot"];
			
		}
		
		protected function initColorSpaceNames():void{
			
			_colorSpaceNames = ["Uncalibrated", "sRGB", "reserved"];
			
		}
		
		protected function initYcbcrPositionNames():void{
			
			_ycbcrPositionNames = ["reserved", "centered", "co-sited"];

		}
		
		protected function initMeteringMode():void{
			
			_meteringModeNames = ["unknown", "Average", "CenterWeightedAverage", "Spot", "MultiSpot", "Pattern", "Partial", "other", "reserved"];

		}
		
		protected function initFlashStatusNames():void{
			
			_flashStatusNames = [, "", "", "", "",  " Strobe return light not detected", "", "Strobe return light detected", "",
								 ""]
			_flashStatusNames[0] = "Flash did not fire";
			_flashStatusNames[1] = "Flash fired";
			_flashStatusNames[5] = "Strobe return light not detected";
			_flashStatusNames[7] = "Strobe return light detected";
			_flashStatusNames[9] = "Flash fired, compulsory flash mode";
			_flashStatusNames[13] = "Flash fired, compulsory flash mode, return light not detected";
			_flashStatusNames[15] = "Flash fired, compulsory flash mode, return light detected";
			_flashStatusNames[16] = "Flash did not fire, compulsory flash mode";
			_flashStatusNames[24] = "Flash did not fire, auto mode";
			_flashStatusNames[25] = "Flash fired, auto mode";
			_flashStatusNames[29] = "Flash fired, auto mode, return light not detected";
			_flashStatusNames[31] = "Flash fired, auto mode, return light detected";
			_flashStatusNames[32] = "No flash function";
			_flashStatusNames[65] = "Flash fired, red-eye reduction mode";
			_flashStatusNames[69] = "Flash fired, red-eye reduction mode, return light not detected";
			_flashStatusNames[71] = "Flash fired, red-eye reduction mode, return light detected";
			_flashStatusNames[73] = "Flash fired, compulsory flash mode, red-eye reduction mode";
			_flashStatusNames[77] = "Flash fired, compulsory flash mode, red-eye reduction mode, return light not detected";
			_flashStatusNames[79] = "Flash fired, compulsory flash mode, red-eye reduction mode, return light detected";
			_flashStatusNames[89] = "Flash fired, auto mode, red-eye reduction mode";
			_flashStatusNames[93] = "Flash fired, auto mode, return light not detected, red-eye reduction mode";
			_flashStatusNames[95] = "Flash fired, auto mode, return light detected, red-eye reduction mode";
			_flashStatusNames[100] = "reserved";
						
		}
		
		protected function initResolutionNames():void{
			
			_resolutionNames = ["reserved", "inches", "centimeters"];

		}
		
		protected function initExposureNames():void{
			
			_exposureNames = ["Not defined", "Manual", "Normal program", "Aperture priority", "Shutter priority", "Creative program", "Action program", "Portrait mode", "Landscape mode", "reserved"];
			
		}
		
		protected function initFlashPixNames():void{
			
			_flashPixNames = ["Flashpix Format Version 1.0", "reserved"];
			
		}
		
		public function get tagsTable():HashTable{
			
			return _tagsTable;
			
		}
		
		public function get orientationNames():Array{
			
			return _orientationNames;
			
		}
		
		public function get colorSpaceNames():Array{
			
			return _colorSpaceNames;	
			
		}
		
		public function get ycbcrPositionNames():Array{
			
			return _ycbcrPositionNames;	
			
		}
		
		public function get meteringModeNames():Array{
			
			return _meteringModeNames;	
			
		}
		
		public function get flashStatusNames():Array{
			
			return _flashStatusNames;	
			
		}
		
		public function get resolutionNames():Array{
			
			return _resolutionNames;	
			
		}
		
		public function get exposureNames():Array{
			
			return _exposureNames;	
			
		}
		
		public function get flashPixNames():Array{
			
			return _flashPixNames;	
			
		}

	}
}