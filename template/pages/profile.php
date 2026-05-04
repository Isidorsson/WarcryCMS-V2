<?php
if (!defined('init_pages')) { header('HTTP/1.0 404 not found'); exit; }

/* Warcry Armory Profile - AzerothCore 3.3.5a */
$TPL->SetTitle('Armory Profile');
$TPL->LoadHeader();

function wa_h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function wa_mask($name){ $name=(string)$name; $l=strlen($name); if($l<=0) return '****'; if($l<=2) return str_repeat('*',$l); return substr($name,0,2).str_repeat('*', max(3,$l-2)); }
function wa_money($copper){ $c=(int)$copper; $g=floor($c/10000); $s=floor(($c%10000)/100); $co=$c%100; return array($g,$s,$co); }
function wa_playtime($sec){ $sec=(int)$sec; $d=floor($sec/86400); $h=floor(($sec%86400)/3600); return $d.'d '.$h.'h'; }
function wa_race($id){ $a=array(1=>'Human',2=>'Orc',3=>'Dwarf',4=>'Night Elf',5=>'Undead',6=>'Tauren',7=>'Gnome',8=>'Troll',10=>'Blood Elf',11=>'Draenei'); return isset($a[(int)$id])?$a[(int)$id]:'Unknown'; }
function wa_class($id){ $a=array(1=>'Warrior',2=>'Paladin',3=>'Hunter',4=>'Rogue',5=>'Priest',6=>'Death Knight',7=>'Shaman',8=>'Mage',9=>'Warlock',11=>'Druid'); return isset($a[(int)$id])?$a[(int)$id]:'Unknown'; }
function wa_faction($race){ return in_array((int)$race,array(1,3,4,7,11),true)?'Alliance':'Horde'; }
function wa_class_slug($id){ $a=array(1=>'warrior',2=>'paladin',3=>'hunter',4=>'rogue',5=>'priest',6=>'deathknight',7=>'shaman',8=>'mage',9=>'warlock',11=>'druid'); return isset($a[(int)$id])?$a[(int)$id]:'warrior'; }
function wa_race_slug($id){ $a=array(1=>'human',2=>'orc',3=>'dwarf',4=>'nightelf',5=>'undead',6=>'tauren',7=>'gnome',8=>'troll',10=>'bloodelf',11=>'draenei'); return isset($a[(int)$id])?$a[(int)$id]:'human'; }
function wa_gender_slug($id){ return ((int)$id===1)?'female':'male'; }
function wa_equipment_entries($equipment){ $out=array(); foreach($equipment as $slot=>$it){ if(!empty($it['entry'])) $out[]=(int)$it['entry']; } return implode(',', array_unique($out)); }
function wa_quality($q){ $a=array(0=>'poor',1=>'common',2=>'uncommon',3=>'rare',4=>'epic',5=>'legendary',6=>'artifact',7=>'heirloom'); return isset($a[(int)$q])?$a[(int)$q]:'common'; }
function wa_icon_cache_dir(){
    $dir=dirname(__FILE__).'/../cache/wowhead_icons';
    if(!is_dir($dir)) @mkdir($dir,0755,true);
    return $dir;
}
function wa_fetch_wowhead_icon($entry){
    $entry=(int)$entry; if($entry<=0) return '';
    $dir=wa_icon_cache_dir(); $file=$dir.'/'.$entry.'.txt';
    if(is_file($file) && (time()-filemtime($file)) < 2592000) return trim((string)@file_get_contents($file));
    $url='https://www.wowhead.com/wotlk/item='.$entry.'&xml';
    $xml='';
    if(function_exists('curl_init')){
        $ch=curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true); curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,2); curl_setopt($ch,CURLOPT_TIMEOUT,4); curl_setopt($ch,CURLOPT_USERAGENT,'WarcryCMS Armory Icon Cache');
        $xml=(string)curl_exec($ch); curl_close($ch);
    } else {
        $ctx=stream_context_create(array('http'=>array('timeout'=>4,'header'=>"User-Agent: WarcryCMS Armory Icon Cache\r\n")));
        $xml=(string)@file_get_contents($url,false,$ctx);
    }
    $icon='';
    if($xml && preg_match('/<icon[^>]*>([^<]+)<\/icon>/i',$xml,$m)) $icon=strtolower(trim($m[1]));
    if($icon!=='' && preg_match('/^[a-z0-9_]+$/',$icon)){ @file_put_contents($file,$icon); return $icon; }
    return '';
}
function wa_item_icon_guess($entry,$name,$class=0,$inv=0){
    $entry=(int)$entry; $name=strtolower((string)$name);
    $remote=wa_fetch_wowhead_icon($entry);
    if($remote!=='') return $remote;
    $known=array(
        // Starter / low level items. These are kept local so icons stay accurate even if Wowhead XML cannot be fetched by the host.
        25=>'inv_sword_04',35=>'inv_staff_08',36=>'inv_axe_04',37=>'inv_axe_04',38=>'inv_shirt_05',39=>'inv_pants_02',40=>'inv_boots_05',
        43=>'inv_boots_05',44=>'inv_pants_02',45=>'inv_shirt_05',47=>'inv_boots_05',48=>'inv_pants_02',49=>'inv_shirt_05',
        // Common custom / legendary examples.
        2092=>'inv_throwingknife_01',19019=>'inv_sword_39',32837=>'inv_weapon_glave_01',32838=>'inv_weapon_glave_01',17182=>'inv_hammer_unique_sulfuras',49623=>'inv_sword_155',50730=>'inv_axe_113',
        // Death Knight starter items - exact WotLK icons.
        34648=>'inv_boots_plate_05',34649=>'inv_gauntlets_28',34650=>'inv_chest_plate06',34651=>'inv_belt_12',34652=>'inv_helmet_125',34653=>'inv_bracer_13',
        34655=>'inv_shoulder_92',34656=>'inv_pants_cloth_27',34657=>'inv_jewelry_necklace_37',34658=>'inv_jewelry_ring_34',34659=>'inv_misc_cape_19'
    );
    if(isset($known[$entry])) return $known[$entry];

    // Strong name fallback for custom DB entries using original item names.
    $knownByName=array(
        'acherus knight\'s hood'=>'inv_helmet_125','acherus knight\'s pauldrons'=>'inv_shoulder_92','acherus knight\'s shroud'=>'inv_misc_cape_19',
        'acherus knight\'s tunic'=>'inv_chest_plate06','acherus knight\'s wristguard'=>'inv_bracer_13','acherus knight\'s gauntlets'=>'inv_gauntlets_28',
        'acherus knight\'s girdle'=>'inv_belt_12','acherus knight\'s legplates'=>'inv_pants_cloth_27','acherus knight\'s greaves'=>'inv_boots_plate_05',
        'choker of damnation'=>'inv_jewelry_necklace_37','plague band'=>'inv_jewelry_ring_34','corrupted band'=>'inv_jewelry_ring_34',
        'footpad\'s shirt'=>'inv_shirt_05','footpad\'s pants'=>'inv_pants_02','footpad\'s shoes'=>'inv_boots_05'
    );
    foreach($knownByName as $needle=>$iconName){ if(strpos($name,$needle)!==false) return $iconName; }

    if(strpos($name,'warglaive')!==false) return 'inv_weapon_glave_01';
    if(strpos($name,'shirt')!==false || (int)$inv===4) return 'inv_shirt_01';
    if(strpos($name,'pants')!==false || strpos($name,'leggings')!==false || (int)$inv===7) return 'inv_pants_02';
    if(strpos($name,'shoe')!==false || strpos($name,'boot')!==false || (int)$inv===8) return 'inv_boots_05';
    if(strpos($name,'knife')!==false || strpos($name,'thrown')!==false) return 'inv_throwingknife_01';
    if(strpos($name,'sword')!==false || (int)$class===2) return 'inv_sword_04';
    if(strpos($name,'shield')!==false) return 'inv_shield_04';
    if(strpos($name,'helm')!==false || (int)$inv===1) return 'inv_helmet_03';
    if(strpos($name,'shoulder')!==false || (int)$inv===3) return 'inv_shoulder_02';
    if(strpos($name,'chest')!==false || (int)$inv===5 || (int)$inv===20) return 'inv_chest_plate05';
    if(strpos($name,'glove')!==false || (int)$inv===10) return 'inv_gauntlets_04';
    if(strpos($name,'ring')!==false || (int)$inv===11) return 'inv_jewelry_ring_03';
    if(strpos($name,'trinket')!==false || (int)$inv===12) return 'inv_misc_gem_pearl_05';
    return 'inv_misc_questionmark';
}

function wa_faction_emblem_icon($race){ return wa_faction($race)==='Alliance' ? 'achievement_pvp_a_01' : 'achievement_pvp_h_01'; }
function wa_gender_body_class($gender){ return ((int)$gender===1) ? 'female' : 'male'; }

function wa_armory_scene_asset($race,$gender){
    $f=strtolower(wa_faction($race));
    $g=((int)$gender===1)?'female':'male';
    return './template/style/images/armory/'.$g.'_'.$f.'.png';
}
function wa_race_portrait_icon($race,$gender){
    $race=(int)$race; $gender=((int)$gender===1)?'female':'male';
    $r=array(1=>'human',2=>'orc',3=>'dwarf',4=>'nightelf',5=>'undead',6=>'tauren',7=>'gnome',8=>'troll',10=>'bloodelf',11=>'draenei');
    $slug=isset($r[$race])?$r[$race]:'human';
    return 'achievement_character_'.$slug.'_'.$gender;
}
function wa_world_db_name($db){
    static $cached=null; if($cached!==null) return $cached;
    try { $rows=$db->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN); foreach($rows as $n){ if(preg_match('/(world|acore_world|azerothcore_world)$/i',$n)){ try{ $x=$db->query('SHOW TABLES FROM `'.$n.'` LIKE "item_template"')->fetchColumn(); if($x){$cached=$n; return $cached;} }catch(Exception $e){} } } } catch(Exception $e) {}
    $cached='world'; return $cached;
}
function wa_table_exists($db,$table){ try{ $s=$db->prepare('SHOW TABLES LIKE :t'); $s->execute(array(':t'=>$table)); return (bool)$s->fetchColumn(); }catch(Exception $e){return false;} }
function wa_get_auth($CORE,$id){ try{ $a=$CORE->AuthDatabaseConnection(); $s=$a->prepare('SELECT id, username, joindate, last_login, online, expansion FROM account WHERE id=:id LIMIT 1'); $s->execute(array(':id'=>(int)$id)); return $s->fetch(PDO::FETCH_ASSOC); }catch(Exception $e){ return false; } }
function wa_get_cms($DB,$id){ try{ $s=$DB->prepare('SELECT `id`, `displayName`, `silver`, `gold`, `country`, `avatar`, `avatarType`, `rank`, `status`, `selected_realm` FROM `account_data` WHERE `id`=:id LIMIT 1'); $s->execute(array(':id'=>(int)$id)); return $s->fetch(PDO::FETCH_ASSOC); }catch(Exception $e){ return false; } }
function wa_get_avatar($user){ if(!$user) return './resources/avatars/rookie_avatar_1.jpg'; if(defined('AVATAR_TYPE_UPLOAD') && (int)$user['avatarType']===AVATAR_TYPE_UPLOAD && !empty($user['avatar'])) return $user['avatar']; try{ $g=new AvatarGallery(); $a=$g->get((int)$user['avatar']); if($a) return './resources/avatars/'.$a->string(); }catch(Exception $e){} return './resources/avatars/rookie_avatar_1.jpg'; }

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$charGuid = isset($_GET['char']) ? (int)$_GET['char'] : 0;
$realmId = isset($_GET['realm']) ? (int)$_GET['realm'] : 1;
if($uid<=0 && isset($CURUSER) && $CURUSER->isOnline()) $uid=(int)$CURUSER->get('id');
if($realmId<=0) $realmId=1;

$user=false; $auth=false; $chars=array(); $selected=false; $equipment=array(); $achievements=array(); $inventoryCount=0; $totalAch=0; $charDB=false;
if($uid>0){ $user=wa_get_cms($DB,$uid); $auth=wa_get_auth($CORE,$uid); if($user && !empty($user['selected_realm'])) $realmId=(int)$user['selected_realm']; }
if(isset($realms_config[$realmId])){ $charDB=$CORE->RealmDatabaseConnection($realmId); }
if($charDB){
    if($uid>0){
        try{ $s=$charDB->prepare('SELECT guid,name,race,class,gender,level,online,money,totaltime,totalKills,logout_time,position_x,position_y,position_z,map FROM characters WHERE account=:a ORDER BY level DESC,name ASC'); $s->execute(array(':a'=>$uid)); $chars=$s->fetchAll(PDO::FETCH_ASSOC); }catch(Exception $e){ $chars=array(); }
    }
    if($charGuid>0){ foreach($chars as $c){ if((int)$c['guid']===$charGuid){$selected=$c; break;} } }
    if(!$selected && count($chars)>0) $selected=$chars[0];
    if($selected){
        $world=wa_world_db_name($charDB);
        try{
            $sql='SELECT ci.slot, ii.itemEntry AS entry, COALESCE(it.name, CONCAT("Item #",ii.itemEntry)) AS name, COALESCE(it.Quality,0) AS Quality, COALESCE(it.ItemLevel,0) AS ItemLevel, COALESCE(it.InventoryType,0) AS InventoryType, COALESCE(it.class,0) AS itemClass, COALESCE(it.subclass,0) AS subclass, ii.durability, ii.count
                  FROM character_inventory ci
                  INNER JOIN item_instance ii ON ii.guid=ci.item
                  LEFT JOIN `'.$world.'`.item_template it ON it.entry=ii.itemEntry
                  WHERE ci.guid=:g AND ci.bag=0 AND ci.slot BETWEEN 0 AND 18
                  ORDER BY ci.slot ASC';
            $s=$charDB->prepare($sql); $s->execute(array(':g'=>(int)$selected['guid']));
            while($r=$s->fetch(PDO::FETCH_ASSOC)){ $r['icon']=wa_item_icon_guess($r['entry'],$r['name'],$r['itemClass'],$r['InventoryType']); $equipment[(int)$r['slot']]=$r; }
        }catch(Exception $e){
            try{ $s=$charDB->prepare('SELECT ci.slot, ii.itemEntry AS entry, CONCAT("Item #",ii.itemEntry) AS name, 0 AS Quality, 0 AS ItemLevel, 0 AS InventoryType, 0 AS itemClass, 0 AS subclass, ii.durability, ii.count FROM character_inventory ci INNER JOIN item_instance ii ON ii.guid=ci.item WHERE ci.guid=:g AND ci.bag=0 AND ci.slot BETWEEN 0 AND 18 ORDER BY ci.slot ASC'); $s->execute(array(':g'=>(int)$selected['guid'])); while($r=$s->fetch(PDO::FETCH_ASSOC)){ $r['icon']=wa_item_icon_guess($r['entry'],$r['name']); $equipment[(int)$r['slot']]=$r; } }catch(Exception $e2){}
        }
        try{ $s=$charDB->prepare('SELECT COUNT(*) FROM character_inventory WHERE guid=:g'); $s->execute(array(':g'=>(int)$selected['guid'])); $inventoryCount=(int)$s->fetchColumn(); }catch(Exception $e){}
        if(wa_table_exists($charDB,'character_achievement')){
            try{ $s=$charDB->prepare('SELECT achievement,date FROM character_achievement WHERE guid=:g ORDER BY date DESC LIMIT 12'); $s->execute(array(':g'=>(int)$selected['guid'])); $achievements=$s->fetchAll(PDO::FETCH_ASSOC); $s=$charDB->prepare('SELECT COUNT(*) FROM character_achievement WHERE guid=:g'); $s->execute(array(':g'=>(int)$selected['guid'])); $totalAch=(int)$s->fetchColumn(); }catch(Exception $e){}
        }
    }
}
$display = $user && $user['displayName']!=='' ? $user['displayName'] : ($auth ? $auth['username'] : 'Unknown');
$rankName='Member'; try{ if($user){$r=new UserRank((int)$user['rank']); $rankName=$r->string()?:'Member';} }catch(Exception $e){}
$avatar=wa_get_avatar($user);
list($gold,$silver,$copper)=wa_money($selected ? (int)$selected['money'] : 0);
$slots=array(0=>'Head',1=>'Neck',2=>'Shoulders',14=>'Back',4=>'Chest',3=>'Shirt',18=>'Tabard',8=>'Wrists',9=>'Hands',5=>'Waist',6=>'Legs',7=>'Feet',10=>'Ring',11=>'Ring',12=>'Trinket',13=>'Trinket',15=>'Main Hand',16=>'Off Hand',17=>'Ranged');
$leftSlots=array(0,1,2,14,4,3,18,8,9);
$rightSlots=array(5,6,7,10,11,12,13,15,16,17);
function wa_slot_html($slot,$equipment,$label){
    $it=isset($equipment[$slot])?$equipment[$slot]:false;
    if($it){
        $q=wa_quality($it['Quality']);
        $icon='https://wow.zamimg.com/images/wow/icons/large/'.wa_h($it['icon']).'.jpg';
        return '<a class="wa-slot filled q-'.$q.'" href="https://www.wowhead.com/wotlk/item='.(int)$it['entry'].'" target="_blank" rel="item='.(int)$it['entry'].'" data-wowhead="item='.(int)$it['entry'].'"><img src="'.$icon.'" onerror="this.src=\'https://wow.zamimg.com/images/wow/icons/large/inv_misc_questionmark.jpg\'"/><span><b>'.wa_h($it['name']).'</b><em>iLvl '.(int)$it['ItemLevel'].' · '.$label.'</em></span></a>';
    }
    return '<div class="wa-slot empty"><span class="wa-empty-icon"></span><span><b>'.$label.'</b><em>Empty</em></span></div>';
}
?>
<style>
.wa-wrap{padding:26px 30px 42px;text-align:left;color:#cfc4a8}.wa-card{background:linear-gradient(180deg,rgba(24,16,12,.84),rgba(4,4,4,.9));border:1px solid rgba(214,156,48,.22);border-radius:10px;box-shadow:0 24px 80px rgba(0,0,0,.55);overflow:hidden}.wa-hero{position:relative;min-height:190px;padding:26px;background:radial-gradient(circle at 50% 0,rgba(115,80,25,.28),transparent 42%),linear-gradient(90deg,rgba(0,0,0,.72),rgba(0,0,0,.25)),url('./template/style/images/headers/media.jpg');background-size:cover;background-position:center}.wa-top{display:grid;grid-template-columns:110px 1fr auto;gap:20px;align-items:center}.wa-avatar{width:96px;height:96px;border-radius:10px;background-size:cover;background-position:center;border:1px solid rgba(214,156,48,.6);box-shadow:0 0 0 4px rgba(0,0,0,.45)}.wa-name{font-size:34px;color:#f0b33f;font-family:Georgia,serif;text-shadow:0 2px 4px #000;margin:0}.wa-meta{color:#c1b28c;margin-top:5px}.wa-badges{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.wa-badge{padding:7px 10px;border:1px solid rgba(214,156,48,.25);background:rgba(0,0,0,.35);border-radius:999px;font-size:12px;color:#d7c8a6}.wa-search{background:rgba(0,0,0,.38);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:12px}.wa-search select{background:#0d0d0d;color:#d9cda9;border:1px solid rgba(214,156,48,.24);padding:10px;border-radius:6px;min-width:180px}.wa-inspect{display:grid;grid-template-columns:minmax(260px,1fr) minmax(280px,360px) minmax(260px,1fr);gap:14px;padding:22px}.wa-model{position:relative;min-height:520px;border:1px solid rgba(214,156,48,.24);border-radius:12px;background:#050505;display:flex;align-items:center;justify-content:center;overflow:hidden}.wa-armory-scene{position:absolute;inset:0;background-size:cover;background-position:center center;background-repeat:no-repeat;z-index:0}.wa-model:before{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.04),rgba(0,0,0,.18) 58%,rgba(0,0,0,.38));z-index:1;pointer-events:none}.wa-character-title{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);z-index:3;display:inline-block;padding:8px 14px;border-radius:999px;background:rgba(0,0,0,.48);border:1px solid rgba(214,156,48,.28);color:#f0b33f;text-shadow:0 2px 4px #000;font-size:17px;font-family:Georgia,serif;white-space:nowrap}.wa-model-note{display:none}.wa-slots{display:grid;gap:8px}.wa-slot{display:grid;grid-template-columns:46px 1fr;gap:10px;align-items:center;min-height:55px;padding:7px;background:rgba(0,0,0,.38);border:1px solid rgba(255,255,255,.07);border-radius:8px;text-decoration:none!important;color:#cfc4a8!important;transition:.15s}.wa-slot:hover{transform:translateY(-1px);border-color:rgba(214,156,48,.45);background:rgba(20,14,8,.72)}.wa-slot img,.wa-empty-icon{width:42px;height:42px;border-radius:6px;background:#111;border:1px solid rgba(255,255,255,.14);display:block}.wa-slot b{display:block;font-size:12px;line-height:1.2}.wa-slot em{display:block;font-style:normal;color:#817866;font-size:10px;margin-top:3px}.wa-slot.empty{opacity:.5}.wa-empty-icon{background:linear-gradient(135deg,#111,#222)}.q-poor b{color:#9d9d9d}.q-common b{color:#fff}.q-uncommon b{color:#1eff00}.q-rare b{color:#0070dd}.q-epic b{color:#a335ee}.q-legendary b{color:#ff8000}.q-artifact b{color:#e6cc80}.wa-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:0 22px 22px}.wa-stat{background:rgba(0,0,0,.36);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:14px}.wa-stat strong{display:block;color:#f0b33f;font-size:24px}.wa-stat span{font-size:12px;text-transform:uppercase;color:#8f846c}.wa-sections{display:grid;grid-template-columns:1fr 1fr;gap:18px;padding:0 22px 24px}.wa-section{background:rgba(0,0,0,.31);border:1px solid rgba(255,255,255,.07);border-radius:9px;overflow:hidden}.wa-section h3{margin:0;padding:13px 16px;border-bottom:1px solid rgba(255,255,255,.07);color:#f0b33f;font-family:Georgia,serif}.wa-section-body{padding:14px 16px}.wa-ach{display:grid;grid-template-columns:1fr auto;gap:8px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.05)}.wa-muted{color:#8d816d}.wa-char-row{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.05)}.wa-online{color:#4ad16f}.wa-offline{color:#b86b5e}@media(max-width:1050px){.wa-inspect{grid-template-columns:1fr}.wa-model{min-height:390px}.wa-stats,.wa-sections{grid-template-columns:1fr 1fr}.wa-top{grid-template-columns:1fr;text-align:center}.wa-avatar{margin:auto}}@media(max-width:650px){.wa-stats,.wa-sections{grid-template-columns:1fr}}
</style>
<div class="content_holder"><div class="sub-page-title"><div id="title"><h1>Armory Profile<p></p><span></span></h1></div></div><div class="container_2 account" align="center"><div class="cont-image"><div class="container_3 account_sub_header"><div class="grad"><div class="page-title">Character Inspection</div><a href="<?php echo wa_h($config['BaseURL']); ?>/index.php?page=armory">Back to Armory</a></div></div>
<?php if(!$user && !$auth): ?><div class="container_3 account-wide" style="padding:30px;text-align:center"><h2 style="color:#d4af37">Profile not found</h2><p>This account does not exist.</p></div><?php else: ?>
<div class="wa-wrap"><div class="wa-card"><div class="wa-hero"><div class="wa-top"><div class="wa-avatar" style="background-image:url('<?php echo wa_h($avatar); ?>')"></div><div><h2 class="wa-name"><?php echo wa_h($display); ?></h2><div class="wa-meta"><?php echo $selected ? wa_h($selected['name'].' · Level '.$selected['level'].' '.wa_race($selected['race']).' '.wa_class($selected['class'])) : 'No character selected'; ?></div><div class="wa-badges"><span class="wa-badge">Account <?php echo wa_h(wa_mask($auth ? $auth['username'] : $display)); ?></span><span class="wa-badge"><?php echo wa_h($rankName); ?></span><span class="wa-badge"><?php echo $selected && (int)$selected['online']===1 ? 'Online' : 'Offline'; ?></span><span class="wa-badge"><?php echo count($chars); ?> Characters</span></div></div><form class="wa-search" method="get"><input type="hidden" name="page" value="profile"><input type="hidden" name="uid" value="<?php echo (int)$uid; ?>"><select name="char" onchange="this.form.submit()"><?php foreach($chars as $c): ?><option value="<?php echo (int)$c['guid']; ?>" <?php echo $selected && (int)$selected['guid']===(int)$c['guid']?'selected':''; ?>><?php echo wa_h($c['name'].' - Lv '.$c['level']); ?></option><?php endforeach; ?></select></form></div></div>
<?php if($selected): ?><div class="wa-inspect"><div class="wa-slots"><?php foreach($leftSlots as $sl) echo wa_slot_html($sl,$equipment,$slots[$sl]); ?></div><?php $sceneAsset=wa_armory_scene_asset($selected['race'],$selected['gender']); ?><div class="wa-model"><div class="wa-armory-scene" style="background-image:url('<?php echo wa_h($sceneAsset); ?>')"></div><div class="wa-character-title"><?php echo wa_h(wa_race($selected['race']).' '.wa_class($selected['class'])); ?></div></div><div class="wa-slots"><?php foreach($rightSlots as $sl) echo wa_slot_html($sl,$equipment,$slots[$sl]); ?></div></div>
<div class="wa-stats"><div class="wa-stat"><strong><?php echo (int)$selected['level']; ?></strong><span>Level</span></div><div class="wa-stat"><strong><?php echo (int)$totalAch; ?></strong><span>Achievements</span></div><div class="wa-stat"><strong><?php echo wa_h($gold.'g '.$silver.'s '.$copper.'c'); ?></strong><span>Character Gold</span></div><div class="wa-stat"><strong><?php echo wa_h(wa_playtime($selected['totaltime'])); ?></strong><span>Played Time</span></div><div class="wa-stat"><strong><?php echo (int)$selected['totalKills']; ?></strong><span>Total Kills</span></div><div class="wa-stat"><strong><?php echo (int)$inventoryCount; ?></strong><span>Items Owned</span></div><div class="wa-stat"><strong><?php echo wa_h(wa_faction($selected['race'])); ?></strong><span>Faction</span></div><div class="wa-stat"><strong><?php echo (int)$selected['map']; ?></strong><span>Map ID</span></div></div>
<div class="wa-sections"><div class="wa-section"><h3>Characters</h3><div class="wa-section-body"><?php foreach($chars as $c): ?><div class="wa-char-row"><a href="<?php echo wa_h($config['BaseURL']); ?>/index.php?page=profile&uid=<?php echo (int)$uid; ?>&char=<?php echo (int)$c['guid']; ?>" class="<?php echo wa_class_slug($c['class']); ?>"><?php echo wa_h($c['name']); ?></a><span>Lv <?php echo (int)$c['level']; ?> · <?php echo wa_h(wa_race($c['race']).' '.wa_class($c['class'])); ?> · <b class="<?php echo (int)$c['online']===1?'wa-online':'wa-offline'; ?>"><?php echo (int)$c['online']===1?'Online':'Offline'; ?></b></span></div><?php endforeach; ?></div></div><div class="wa-section"><h3>Recent Achievements</h3><div class="wa-section-body"><?php if(!$achievements): ?><div class="wa-muted">No achievements found yet.</div><?php else: foreach($achievements as $a): ?><div class="wa-ach"><span>Achievement #<?php echo (int)$a['achievement']; ?></span><em><?php echo !empty($a['date'])?date('d M Y',(int)$a['date']):'Unknown date'; ?></em></div><?php endforeach; endif; ?></div></div></div><?php else: ?><div class="wa-section" style="margin:24px"><h3>No characters</h3><div class="wa-section-body wa-muted">This account has no characters on the selected realm.</div></div><?php endif; ?></div></div><?php endif; ?></div></div></div>
<script>
var whTooltips = {colorLinks: false, iconizeLinks: false, renameLinks: false};
(function(){var s=document.createElement('script');s.src='https://wow.zamimg.com/js/tooltips.js';s.async=true;document.head.appendChild(s);})();
(function(){
  var exact={
    34648:'inv_boots_plate_05',34649:'inv_gauntlets_28',34650:'inv_chest_plate06',34651:'inv_belt_12',34652:'inv_helmet_125',34653:'inv_bracer_13',
    34655:'inv_shoulder_92',34656:'inv_pants_cloth_27',34657:'inv_jewelry_necklace_37',34658:'inv_jewelry_ring_34',34659:'inv_misc_cape_19',
    38:'inv_shirt_05',45:'inv_shirt_05',49:'inv_shirt_05',39:'inv_pants_02',44:'inv_pants_02',48:'inv_pants_02',40:'inv_boots_05',43:'inv_boots_05',47:'inv_boots_05',
    2092:'inv_throwingknife_01',19019:'inv_sword_39',32837:'inv_weapon_glave_01',32838:'inv_weapon_glave_01'
  };
  document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('.wa-slot[href*="item="]').forEach(function(a){
      var m=a.href.match(/item=(\d+)/); if(!m) return;
      var id=parseInt(m[1],10), img=a.querySelector('img');
      if(img && exact[id]) img.src='https://wow.zamimg.com/images/wow/icons/large/'+exact[id]+'.jpg';
    });
  });
})();
</script>
<?php $TPL->LoadFooter(); ?>