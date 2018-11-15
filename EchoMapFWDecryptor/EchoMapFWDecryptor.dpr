{$apptype console}
uses
  SysUtils;
const
  Key =
    #$36#$1E#$61#$5A#$53#$08#$5A#$C3#$3E#$26#$69#$62#$5B#$10#$62#$CB +
    #$46#$2E#$71#$6A#$63#$18#$6A#$D3#$4E#$36#$79#$72#$6B#$20#$72#$DB +
    #$56#$3E#$81#$7A#$73#$28#$7A#$E3#$5E#$46#$89#$82#$7B#$30#$82#$EB +
    #$66#$4E#$91#$8A#$83#$38#$8A#$F3#$6E#$56#$99#$92#$8B#$40#$92#$FB +
    #$76#$5E#$A1#$9A#$93#$48#$9A#$03#$7E#$66#$A9#$A2#$9B#$50#$A2#$0B +
    #$86#$6E#$B1#$AA#$A3#$58#$AA#$13#$8E#$76#$B9#$B2#$AB#$60#$B2#$1B +
    #$96#$7E#$C1#$BA#$B3#$68#$BA#$23#$9E#$86#$C9#$C2#$BB#$70#$C2#$2B +
    #$A6#$8E#$D1#$CA#$C3#$78#$CA#$33#$AE#$96#$D9#$D2#$CB#$80#$D2#$3B +
    #$B6#$9E#$E1#$DA#$D3#$88#$DA#$43#$BE#$A6#$E9#$E2#$DB#$90#$E2#$4B +
    #$C6#$AE#$F1#$EA#$E3#$98#$EA#$53#$CE#$B6#$F9#$F2#$EB#$A0#$F2#$5B +
    #$D6#$BE#$01#$FA#$F3#$A8#$FA#$63#$DE#$C6#$09#$02#$FB#$B0#$02#$6B +
    #$E6#$CE#$11#$0A#$03#$B8#$0A#$73#$EE#$D6#$19#$12#$0B#$C0#$12#$7B +
    #$F6#$DE#$21#$1A#$13#$C8#$1A#$83#$FE#$E6#$29#$22#$1B#$D0#$22#$8B +
    #$06#$EE#$31#$2A#$23#$D8#$2A#$93#$0E#$F6#$39#$32#$2B#$E0#$32#$9B +
    #$16#$FE#$41#$3A#$33#$E8#$3A#$A3#$1E#$06#$49#$42#$3B#$F0#$42#$AB +
    #$26#$0E#$51#$4A#$43#$F8#$4A#$B3#$2E#$16#$59#$52#$4B#$00#$52#$BB;
var
  f: file;
  buf: AnsiString;
  i: Integer;
  Op: Integer;
begin
  if (ParamCount <> 3) or (Length(ParamStr(1)) <> 1) or not (LowerCase(ParamStr(1))[1] in ['c', 'd']) then
  begin
    writeln;  
    writeln('Usage:');
    writeln('  ' + ExtractFileName(ParamStr(0)) + ' <command> <Path to Input File> <Path to Output File>');
    writeln;
    writeln('<Commands>');
    writeln('  c - crypt the file');
    writeln('  d - decrypt the file');
    exit;
  end;

  Op := 0;  
  case LowerCase(ParamStr(1))[1] of
    'c': Op := 1;
    'd': Op := -1;
  end;

  assign(f, paramstr(2)); reset(f, 1); SetLength(buf, FileSize(f)); blockread(f, buf[1], Length(buf)); close(f);

  for i:=1 to Length(buf) do
    buf[i] := char(byte(buf[i]) + Op * byte(key[(i - 1) mod 256 + 1]));

  assign(f, paramstr(3)); rewrite(f, 1); blockwrite(f, buf[1], Length(buf)); close(f);
end.
