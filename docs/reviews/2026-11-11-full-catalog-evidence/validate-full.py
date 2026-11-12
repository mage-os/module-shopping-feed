from pathlib import Path
from collections import Counter
import json,hashlib,re,xml.etree.ElementTree as ET
all_results={}
for store,host in [('alpha','latest.rocketcode.com'),('hyva','latest-hyva.rocketcode.com')]:
 root=Path('/opt/comtom/stores')/store/'src';base=root/'var/shopping-feed-full-20261111'
 plans=json.loads((base/'manifest.json').read_text());results=[]
 for plan in plans:
  fid=plan['id'];p=root/'pub/media/mageos-shopping-feed'/f'full_20261111_{fid}.xml'
  if not p.exists():
   print(store,fid,'not published');continue
  ids=Counter();values=Counter();row_count=0;missing_ids=0;missing_values=0
  empty_fields=Counter();availability=Counter();currency=Counter();invalid_prices=0;invalid_urls=Counter()
  tag='product' if plan['type']=='generic' else 'item'
  for event,el in ET.iterparse(p,events=['end']):
   if el.tag==tag:
    row_count+=1
    item_id=el.findtext('id') if tag=='product' else el.findtext('{http://base.google.com/ns/1.0}id')
    value=el.findtext('result') if tag=='product' else el.findtext('{http://base.google.com/ns/1.0}custom_label_0')
    if item_id:ids[item_id]+=1
    else:missing_ids+=1
    if value is not None:values[value]+=1
    else:missing_values+=1
    if tag=='item':
     fields={k:el.findtext('{http://base.google.com/ns/1.0}'+k,'') for k in ['id','title','description','link','image_link','price','availability','brand','mpn']}
     for k,v in fields.items():
      if not v.strip():empty_fields[k]+=1
     availability[fields['availability']]+=1
     match=re.fullmatch(r'([0-9]+(?:\.[0-9]+)?) ([A-Z]{3})',fields['price'])
     if match:currency[match[2]]+=1
     else:invalid_prices+=1
     for k in ['link','image_link']:
      if not fields[k].startswith(('https://','http://')):invalid_urls[k]+=1
    el.clear()
  status=json.loads((base/f'status-{fid}.json').read_text());start=float((base/f'start-{fid}.txt').read_text());finish=float((base/f'finish-{fid}.txt').read_text())
  result={'store':store,'feed_id':fid,'type':plan['type'],'eligible_top_level':plan['eligible'],'rows':row_count,'distinct_ids':len(ids),'duplicate_id_count':sum(v>1 for v in ids.values()),'duplicate_examples':[{'id':k,'count':v} for k,v in ids.items() if v>1][:10],'missing_ids':missing_ids,'conditional_values':dict(values),'missing_conditional_values':missing_values,'elapsed_seconds':round(finish-start,2),'bytes':p.stat().st_size,'sha256':hashlib.file_digest(p.open('rb'),'sha256').hexdigest(),'url':f'https://{host}/media/mageos-shopping-feed/{p.name}','status':status,'temporary_file_remaining':Path(str(p)+'.tmp').exists()}
  if tag=='item':result['google_field_checks']={'empty_fields':dict(empty_fields),'availability':dict(availability),'price_currencies':dict(currency),'invalid_price_syntax':invalid_prices,'invalid_url_syntax':dict(invalid_urls)}
  assert row_count>0 and not missing_ids and not missing_values
  assert not any(v>1 for v in ids.values())
  assert int(status['messages']['added'])==int(plan['eligible'])
  assert values.keys()<={'full catalog verified','review price'}
  assert status['status']==0 and not status['queued'] and not result['temporary_file_remaining']
  assert int(status['messages']['exported'])==row_count
  results.append(result);print(json.dumps(result))
 (base/'validation.json').write_text(json.dumps(results,indent=2)+'\n');all_results[store]=results
print('Validated',sum(map(len,all_results.values())),'published full feeds')
