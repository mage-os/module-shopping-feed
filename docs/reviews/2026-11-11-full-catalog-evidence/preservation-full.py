import pathlib,gzip,re,json,tarfile,hashlib
base=pathlib.Path('/opt/comtom/backups/shopping-feed-20261111/full-catalog')
def rows(path):
 out={}
 for m in re.finditer(r'INSERT INTO `([^`]+)` VALUES\s*(.*?);\n',gzip.open(path,'rt').read(),re.S):
  text=m[2];table=out.setdefault(m[1],{});quoted=False;esc=False;depth=0;start=0
  for i,c in enumerate(text):
   if esc:esc=False;continue
   if c=='\\' and quoted:esc=True;continue
   if c=="'":quoted=not quoted;continue
   if quoted:continue
   if c=='(':
    if depth==0:start=i
    depth+=1
   if c==')':
    depth-=1
    if depth==0:
     row=text[start:i+1];key=row[1:].split(',',1)[0];table[key]=row
 return out
for s in ('alpha','hyva'):
 p=base/s;before=rows(p/'feed-tables-before.sql.gz');after=rows(p/'feed-tables-after.sql.gz');bad=[];n=0
 for table,rs in before.items():
  for key,value in rs.items():
   n+=1
   if after.get(table,{}).get(key)!=value:bad.append(table+':'+key)
 assert n > 0, 'No original rows parsed'
 root=pathlib.Path('/opt/comtom/stores')/s/'src';outCount=0;outBad=[]
 original_backup=base.parent/s
 archive=original_backup/'output-before.tar.gz'
 if archive.exists():
  with tarfile.open(archive) as tar:
   for member in tar:
    if not member.isfile():continue
    outCount+=1;f=root/'pub/media'/member.name
    if not f.is_file() or hashlib.sha256(tar.extractfile(member).read()).digest()!=hashlib.sha256(f.read_bytes()).digest():outBad.append(member.name)
 result={'original_database_rows_checked':n,'changed_original_rows':bad,'original_output_files_checked':outCount,'changed_original_outputs':outBad,'app_config_unchanged':(original_backup/'config.before.php').read_bytes()==(root/'app/etc/config.php').read_bytes()}
 (p/'preservation.json').write_text(json.dumps(result,indent=2)+'\n');print(s,json.dumps(result))
