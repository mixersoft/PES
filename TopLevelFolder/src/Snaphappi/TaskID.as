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


  /**
   * ID used to make sure the web page and the app stay in sync with each other.
   * At most one app instance could be running for any given ID.
   */
  public class TaskID implements TBase   {
    private static const STRUCT_DESC:TStruct = new TStruct("TaskID");
    private static const AUTH_TOKEN_FIELD_DESC:TField = new TField("AuthToken", TType.STRING, 1);
    private static const SESSION_FIELD_DESC:TField = new TField("Session", TType.STRING, 2);
    private static const DEVICE_ID_FIELD_DESC:TField = new TField("DeviceID", TType.STRING, 3);

    /**
     * An authentication token for the user.
     */
    private var _AuthToken:String;
    public static const AUTHTOKEN:int = 1;
    /**
     * A session ID, which should be reset when the user restarts a given task.
     */
    private var _Session:String;
    public static const SESSION:int = 2;
    /**
     * An ID unique to every device.
     */
    private var _DeviceID:String;
    public static const DEVICEID:int = 3;


    public static const metaDataMap:Dictionary = new Dictionary();
    {
      metaDataMap[AUTHTOKEN] = new FieldMetaData("AuthToken", TFieldRequirementType.REQUIRED, 
          new FieldValueMetaData(TType.STRING));
      metaDataMap[SESSION] = new FieldMetaData("Session", TFieldRequirementType.OPTIONAL, 
          new FieldValueMetaData(TType.STRING));
      metaDataMap[DEVICEID] = new FieldMetaData("DeviceID", TFieldRequirementType.REQUIRED, 
          new FieldValueMetaData(TType.STRING));
    }
    {
      FieldMetaData.addStructMetaDataMap(TaskID, metaDataMap);
    }

    public function TaskID() {
    }

    /**
     * An authentication token for the user.
     */
    public function get AuthToken():String {
      return this._AuthToken;
    }

    /**
     * An authentication token for the user.
     */
    public function set AuthToken(AuthToken:String):void {
      this._AuthToken = AuthToken;
    }

    public function unsetAuthToken():void {
      this.AuthToken = null;
    }

    // Returns true if field AuthToken is set (has been assigned a value) and false otherwise
    public function isSetAuthToken():Boolean {
      return this.AuthToken != null;
    }

    /**
     * A session ID, which should be reset when the user restarts a given task.
     */
    public function get Session():String {
      return this._Session;
    }

    /**
     * A session ID, which should be reset when the user restarts a given task.
     */
    public function set Session(Session:String):void {
      this._Session = Session;
    }

    public function unsetSession():void {
      this.Session = null;
    }

    // Returns true if field Session is set (has been assigned a value) and false otherwise
    public function isSetSession():Boolean {
      return this.Session != null;
    }

    /**
     * An ID unique to every device.
     */
    public function get DeviceID():String {
      return this._DeviceID;
    }

    /**
     * An ID unique to every device.
     */
    public function set DeviceID(DeviceID:String):void {
      this._DeviceID = DeviceID;
    }

    public function unsetDeviceID():void {
      this.DeviceID = null;
    }

    // Returns true if field DeviceID is set (has been assigned a value) and false otherwise
    public function isSetDeviceID():Boolean {
      return this.DeviceID != null;
    }

    public function setFieldValue(fieldID:int, value:*):void {
      switch (fieldID) {
      case AUTHTOKEN:
        if (value == null) {
          unsetAuthToken();
        } else {
          this.AuthToken = value;
        }
        break;

      case SESSION:
        if (value == null) {
          unsetSession();
        } else {
          this.Session = value;
        }
        break;

      case DEVICEID:
        if (value == null) {
          unsetDeviceID();
        } else {
          this.DeviceID = value;
        }
        break;

      default:
        throw new ArgumentError("Field " + fieldID + " doesn't exist!");
      }
    }

    public function getFieldValue(fieldID:int):* {
      switch (fieldID) {
      case AUTHTOKEN:
        return this.AuthToken;
      case SESSION:
        return this.Session;
      case DEVICEID:
        return this.DeviceID;
      default:
        throw new ArgumentError("Field " + fieldID + " doesn't exist!");
      }
    }

    // Returns true if field corresponding to fieldID is set (has been assigned a value) and false otherwise
    public function isSet(fieldID:int):Boolean {
      switch (fieldID) {
      case AUTHTOKEN:
        return isSetAuthToken();
      case SESSION:
        return isSetSession();
      case DEVICEID:
        return isSetDeviceID();
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
          case AUTHTOKEN:
            if (field.type == TType.STRING) {
              this.AuthToken = iprot.readString();
            } else { 
              TProtocolUtil.skip(iprot, field.type);
            }
            break;
          case SESSION:
            if (field.type == TType.STRING) {
              this.Session = iprot.readString();
            } else { 
              TProtocolUtil.skip(iprot, field.type);
            }
            break;
          case DEVICEID:
            if (field.type == TType.STRING) {
              this.DeviceID = iprot.readString();
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
      validate();
    }

    public function write(oprot:TProtocol):void {
      validate();

      oprot.writeStructBegin(STRUCT_DESC);
      if (this.AuthToken != null) {
        oprot.writeFieldBegin(AUTH_TOKEN_FIELD_DESC);
        oprot.writeString(this.AuthToken);
        oprot.writeFieldEnd();
      }
      if (this.Session != null) {
        oprot.writeFieldBegin(SESSION_FIELD_DESC);
        oprot.writeString(this.Session);
        oprot.writeFieldEnd();
      }
      if (this.DeviceID != null) {
        oprot.writeFieldBegin(DEVICE_ID_FIELD_DESC);
        oprot.writeString(this.DeviceID);
        oprot.writeFieldEnd();
      }
      oprot.writeFieldStop();
      oprot.writeStructEnd();
    }

    public function toString():String {
      var ret:String = new String("TaskID(");
      var first:Boolean = true;

      ret += "AuthToken:";
      if (this.AuthToken == null) {
        ret += "null";
      } else {
        ret += this.AuthToken;
      }
      first = false;
      if (isSetSession()) {
        if (!first) ret +=  ", ";
        ret += "Session:";
        if (this.Session == null) {
          ret += "null";
        } else {
          ret += this.Session;
        }
        first = false;
      }
      if (!first) ret +=  ", ";
      ret += "DeviceID:";
      if (this.DeviceID == null) {
        ret += "null";
      } else {
        ret += this.DeviceID;
      }
      first = false;
      ret += ")";
      return ret;
    }

    public function validate():void {
      // check for required fields
      if (AuthToken == null) {
        throw new TProtocolError(TProtocolError.UNKNOWN, "Required field 'AuthToken' was not present! Struct: " + toString());
      }
      if (DeviceID == null) {
        throw new TProtocolError(TProtocolError.UNKNOWN, "Required field 'DeviceID' was not present! Struct: " + toString());
      }
      // check that fields of type enum have valid values
    }

  }

}