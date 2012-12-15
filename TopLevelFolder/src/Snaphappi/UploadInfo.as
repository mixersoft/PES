/**
 * Autogenerated by Thrift Compiler (0.9.0-dev)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 *  @generated
 */
package Snaphappi {

import org.apache.thrift.Set;
import flash.utils.ByteArray;
import flash.utils.Dictionary;
import com.hurlant.math.BigInteger;

import org.apache.thrift.*;
import org.apache.thrift.meta_data.*;
import org.apache.thrift.protocol.*;
import Snaphappi.UploadType;


  public class UploadInfo implements TBase   {
    private static const STRUCT_DESC:TStruct = new TStruct("UploadInfo");
    private static const UPLOAD_TYPE_FIELD_DESC:TField = new TField("UploadType", TType.I32, 1);

    /**
     * The type of file being uploaded.
     */
    private var _UploadType:int;
    public static const UPLOADTYPE:int = 1;

    private var __isset_UploadType:Boolean = false;

    public static const metaDataMap:Dictionary = new Dictionary();
    {
      metaDataMap[UPLOADTYPE] = new FieldMetaData("UploadType", TFieldRequirementType.REQUIRED, 
          new FieldValueMetaData(TType.I32));
    }
    {
      FieldMetaData.addStructMetaDataMap(UploadInfo, metaDataMap);
    }

    public function UploadInfo() {
    }

    /**
     * The type of file being uploaded.
     */
    public function get UploadType():int {
      return this._UploadType;
    }

    /**
     * The type of file being uploaded.
     */
    public function set UploadType(UploadType:int):void {
      this._UploadType = UploadType;
      this.__isset_UploadType = true;
    }

    public function unsetUploadType():void {
      this.__isset_UploadType = false;
    }

    // Returns true if field UploadType is set (has been assigned a value) and false otherwise
    public function isSetUploadType():Boolean {
      return this.__isset_UploadType;
    }

    public function setFieldValue(fieldID:int, value:*):void {
      switch (fieldID) {
      case UPLOADTYPE:
        if (value == null) {
          unsetUploadType();
        } else {
          this.UploadType = value;
        }
        break;

      default:
        throw new ArgumentError("Field " + fieldID + " doesn't exist!");
      }
    }

    public function getFieldValue(fieldID:int):* {
      switch (fieldID) {
      case UPLOADTYPE:
        return this.UploadType;
      default:
        throw new ArgumentError("Field " + fieldID + " doesn't exist!");
      }
    }

    // Returns true if field corresponding to fieldID is set (has been assigned a value) and false otherwise
    public function isSet(fieldID:int):Boolean {
      switch (fieldID) {
      case UPLOADTYPE:
        return isSetUploadType();
      default:
        throw new ArgumentError("Field " + fieldID + " doesn't exist!");
      }
    }

    public function read(iprot:TProtocol):void {
      var field:TField;
      iprot.readStructBegin();
      while (true)
      {
        field = iprot.readFieldBegin();
        if (field.type == TType.STOP) { 
          break;
        }
        switch (field.id)
        {
          case UPLOADTYPE:
            if (field.type == TType.I32) {
              this.UploadType = iprot.readI32();
              this.__isset_UploadType = true;
            } else { 
              TProtocolUtil.skip(iprot, field.type);
            }
            break;
          default:
            TProtocolUtil.skip(iprot, field.type);
            break;
        }
        iprot.readFieldEnd();
      }
      iprot.readStructEnd();


      // check for required fields of primitive type, which can't be checked in the validate method
      if (!__isset_UploadType) {
        throw new TProtocolError(TProtocolError.UNKNOWN, "Required field 'UploadType' was not found in serialized data! Struct: " + toString());
      }
      validate();
    }

    public function write(oprot:TProtocol):void {
      validate();

      oprot.writeStructBegin(STRUCT_DESC);
      oprot.writeFieldBegin(UPLOAD_TYPE_FIELD_DESC);
      oprot.writeI32(this.UploadType);
      oprot.writeFieldEnd();
      oprot.writeFieldStop();
      oprot.writeStructEnd();
    }

    public function toString():String {
      var ret:String = new String("UploadInfo(");
      var first:Boolean = true;
		
	  trace(this.UploadType);
	  
      ret += "UploadType:";
      var UploadType_name:String = Snaphappi.UploadType.VALUES_TO_NAMES[this.UploadType];
      if (UploadType_name != null) {
        ret += UploadType_name;
        ret += " (";
      }
      ret += this.UploadType;
      if (UploadType_name != null) {
        ret += ")";
      }
      first = false;
      ret += ")";
      return ret;
    }

    public function validate():void {
      // check for required fields
      // alas, we cannot check 'UploadType' because it's a primitive and you chose the non-beans generator.
      // check that fields of type enum have valid values
      if (isSetUploadType() && !Snaphappi.UploadType.VALID_VALUES.contains(UploadType)){
        throw new TProtocolError(TProtocolError.UNKNOWN, "The field 'UploadType' has been assigned the invalid value " + UploadType);
      }
    }

  }

}