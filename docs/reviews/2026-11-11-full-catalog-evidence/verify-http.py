from pathlib import Path
import json,urllib.request,hashlib
for store in ('alpha','hyva'):
 base=Path('/opt/comtom/stores')/store/'src/var/shopping-feed-full-20261111'
 out=[]
 for v in json.loads((base/'validation.json').read_text()):
  digest=hashlib.sha256();size=0
  with urllib.request.urlopen(v['url'],timeout=120) as r:
   code=r.status;typ=r.headers.get('Content-Type')
   while b:=r.read(1024*1024):digest.update(b);size+=len(b)
  assert code==200 and digest.hexdigest()==v['sha256'] and size==v['bytes']
  out.append({'feed_id':v['feed_id'],'url':v['url'],'http_status':code,'content_type':typ,'bytes':size,'sha256':digest.hexdigest(),'matches_generated_file':True})
 (base/'http-validation.json').write_text(json.dumps(out,indent=2)+'\n')
 print(store,json.dumps(out))
