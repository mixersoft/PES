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
   * Flags indicating the state of the task.
   */
  public class URTaskState implements TBase   {
    private static const STRUCT_DESC:TStruct = new TStruct("URTaskState");
    private static const IS_CANCELLED_FIELD_DESC:TField = new TField("IsCancelled", TType.BOOL, 1);
    private static const FOLDER_UPDATE_COUNT_FIELD_DESC:TField = new TField("FolderUpdateCount", TType.I32, 2);
    private static const FILE_UPDATE_COUNT_FIELD_DESC:TField = new TField("FileUpdateCount", TType.I32, 3);

    /**
     * To be set at when the task is completed or cancelled.
     */
    private var _IsCancelled:Boolean;
    public static const ISCANCELLED:int = 1;
    /**
     * A strictly increasing change counter for the folder list in a given task.
     */
    private var _FolderUpdateCount:int;
    public static const FOLDERUPDATECOUNT:int = 2;
    /**
     * A strictly increasing change counter for the file list in a given task.
     */
    private var _FileUpdateCount:int;
    public static const FILEUPDATECOUNT:int = 3;

    private var __isset_IsCancelled:Boolean = false;
    private var __isset_FolderUpdateCount:Boolean = false;
    private var __isset_FileUpdateCount:Boolean = false;

    public static const metaDataMap:Dictionary = new Dictionary();
    {
      metaDataMap[ISCANCELLED] = new FieldMetaData("IsCancelled", TFieldRequirementType.OPTIONAL, 
          new FieldValueMetaData(TType.BOOL));
      metaDataMap[FOLDERUPDATECOUNT] = new FieldMetaData("FolderUpdateCount", TFieldRequirementType.OPTIONAL, 
          new FieldValueMetaData(TType.I32));
      metaDataMap[FILEUPDATECOUNT] = new FieldMetaData("FileUpdateCount", TFieldRequirementType.OPTIONAL, 
          new FieldValueMetaData(TType.I32));
    }
    {
      FieldMetaData.addStructMetaDataMap(URTaskState, metaDataMap);
    }

    public function URTaskState() {
    }

    /**
     * To be set at when the task is completed or cancelled.
     */
    public function get IsCancelled():Boolean {
      return this._IsCancelled;
    }

    /**
     * To be set at when the task is completed or cancelled.
     */
    public function set IsCancelled(IsCancelled:Boolean):void {
      this._IsCancelled = IsCancelled;
      this.__isset_IsCancelled = true;
    }

    public function unsetIsCancelled():void {
      this.__isset_IsCancelled = false;
    }

    // Returns true if field IsCancelled is set (has been assigned a value) and false otherwise
    public function isSetIsCancelled():Boolean {
      return this.__isset_IsCancelled;
    }

    /**
     * A strictly increasing change counter for the folder list in a given task.
     */
    public function get FolderUpdateCount():int {
      return this._FolderUpdateCount;
    }

    /**
     * A strictly increasing change counter for the folder list in a given task.
     */
    public function set FolderUpdateCount(FolderUpdateCount:int):void {
      this._FolderUpdateCount = FolderUpdateCount;
      this.__isset_FolderUpdateCount = true;
    }

    public function unsetFolderUpdateCount():void {
      this.__isset_FolderUpdateCount = false;
    }

    // Returns true if field FolderUpdateCount is set (has been assigned a value) and false otherwise
    public function isSetFolderUpdateCount():Boolean {
      return this.__isset_FolderUpdateCount;
    }

    /**
     * A strictly increasing change counter for the file list in a given task.
     */
    public function get FileUpdateCount():int {
      return this._FileUpdateCount;
    }

    /**
     * A strictly increasing change counter for the file list in a given task.
     */
    public function set FileUpdateCount(FileUpdateCount:int):void {
      this._FileUpdateCount = FileUpdateCount;
      this.__isset_FileUpdateCount = true;
    }

    public function unsetFileUpdateCount():void {
      this.__isset_FileUpdateCount = false;
    }

    // Returns true if field FileUpdateCount is set (has been assigned a value) and false otherwise
    public function isSetFileUpdateCount():Boolean {
      return this.__isset_FileUpdateCount;
    }

    public function setFieldValue(fieldID:int, value:*):void {
      switch (fieldID) {
      case ISCANCELLED:
        if (value == null) {
          unsetIsCancelled();
        } else {
          this.IsCancelled = value;
        }
        break;

      case FOLDERUPDATECOUNT:
        if (value == null) {
          unsetFolderUpdateCount();
        } else {
          this.FolderUpdateCount = value;
        }
        break;

      case FILEUPDATECOUNT:
        if (value == null) {
          unsetFileUpdateCount();
        } else {
          this.FileUpdateCount = value;
        }
        break;

      default:
        throw new ArgumentError("Field " + fieldID + " doesn't exist!");
      }
    }

    public function getFieldValue(fieldID:int):* {
      switch (fieldID) {
      case ISCANCELLED:
        return this.IsCancelled;
      case FOLDERUPDATECOUNT:
        return this.FolderUpdateCount;
      case FILEUPDATECOUNT:
        return this.FileUpdateCount;
      default:
        throw new ArgumentError("Field " + fieldID + " doesn't exist!");
      }
    }

    // Returns true if field corresponding to fieldID is set (has been assigned a value) and false otherwise
    public function isSet(fieldID:int):Boolean {
      switch (fieldID) {
      case ISCANCELLED:
        return isSetIsCancelled();
      case FOLDERUPDATECOUNT:
        return isSetFolderUpdateCount();
      case FILEUPDATECOUNT:
        return isSetFileUpdateCount();
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
          case ISCANCELLED:
            if (field.type == TType.BOOL) {
              this.IsCancelled = iprot.readBool();
              this.__isset_IsCancelled = true;
            } else { 
              TProtocolUtil.skip(iprot, field.type);
            }
            break;
          case FOLDERUPDATECOUNT:
            if (field.type == TType.I32) {
              this.FolderUpdateCount = iprot.readI32();
              this.__isset_FolderUpdateCount = true;
            } else { 
              TProtocolUtil.skip(iprot, field.type);
            }
            break;
          case FILEUPDATECOUNT:
            if (field.type == TType.I32) {
              this.FileUpdateCount = iprot.readI32();
              this.__isset_FileUpdateCount = true;
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
      oprot.writeFieldBegin(IS_CANCELLED_FIELD_DESC);
      oprot.writeBool(this.IsCancelled);
      oprot.writeFieldEnd();
      oprot.writeFieldBegin(FOLDER_UPDATE_COUNT_FIELD_DESC);
      oprot.writeI32(this.FolderUpdateCount);
      oprot.writeFieldEnd();
      oprot.writeFieldBegin(FILE_UPDATE_COUNT_FIELD_DESC);
      oprot.writeI32(this.FileUpdateCount);
      oprot.writeFieldEnd();
      oprot.writeFieldStop();
      oprot.writeStructEnd();
    }

    public function toString():String {
      var ret:String = new String("URTaskState(");
      var first:Boolean = true;

      if (isSetIsCancelled()) {
        ret += "IsCancelled:";
        ret += this.IsCancelled;
        first = false;
      }
      if (isSetFolderUpdateCount()) {
        if (!first) ret +=  ", ";
        ret += "FolderUpdateCount:";
        ret += this.FolderUpdateCount;
        first = false;
      }
      if (isSetFileUpdateCount()) {
        if (!first) ret +=  ", ";
        ret += "FileUpdateCount:";
        ret += this.FileUpdateCount;
        first = false;
      }
      ret += ")";
      return ret;
    }

    public function validate():void {
      // check for required fields
      // check that fields of type enum have valid values
    }

  }

}
