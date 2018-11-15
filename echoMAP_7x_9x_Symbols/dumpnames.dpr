{$apptype console}
uses sysutils;

function GetName(var Buf: String; var CurPos: Integer): String;
begin
  Result := PChar(@Buf[CurPos]);
  inc(CurPos, StrLen(@Buf[CurPos]) + 1);
end;

function GetPackedInt(var Buf: String; var CurPos: Integer): Integer;
var
  Shift: Integer;
  OneMoreByte: Boolean;
begin
  Result := 0; Shift := 0; OneMoreByte := True;
  while OneMoreByte do
  begin
    inc(Result, (byte(Buf[CurPos]) and $7F) shl Shift);
    OneMoreByte := (byte(Buf[CurPos]) and $80 <> 0);

    inc(CurPos);
    inc(Shift, 7);
  end;
end;

const
  ArmThumb: array [Boolean] of String = ('ARM Code', 'Thumb Code');

  CodeDelta = $801C68C6 + 1 - 1337543;

var
  f: file;
  Buf: String;
  StartPos, CurPos: Integer;
  FuncName: String;
  v1, v2, sum: Integer;
  pref: String;
begin
  assign(f, ParamStr(1)); reset(f, 1);
  SetLength(Buf, FileSize(f));
  blockread(f, Buf[1], FileSize(f));
  close(f);

  StartPos := pos('HWM_boot', Buf);
  CurPos := StartPos;
  sum := 0;
  while Buf[CurPos] <> #0 do
  begin
    FuncName := GetName(Buf, CurPos);
    pref := copy(FuncName, 1, 4);
    if SameText(pref, 'sub_') or SameText(pref, 'loc_') or SameText(pref, 'var_') or SameText(pref, 'off_') then
      FuncName := '_' + FuncName;
      
    v1 := GetPackedInt(Buf, CurPos);
    v2 := GetPackedInt(Buf, CurPos);
    if v1 <> 0 then
      writeln('0x', IntToHex(sum + CodeDelta, 8), ',', FuncName, ',', ArmThumb[odd(sum)], ',', IntToStr(v1));
    inc(sum, v1);
  end;
end.
