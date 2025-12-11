<?php
require_once __DIR__ . '/config.php';
$me = user();
if (!$me){ header('Location: ./'); exit; }
$pdo = db();

// Seed sample frames if none
// Seed sample frames if none
$cnt = (int)$pdo->query("SELECT COUNT(*) FROM frames")->fetchColumn();
if ($cnt===0){
  $pdo->exec("INSERT INTO frames(name,css_token,price_coins,price_crystals,preview_css) VALUES
  ('クラシック','frame-classic',200,0,''),
  ('ネオン','frame-neon',800,2,''),
  ('サクラ','frame-sakura',500,1,''),
  ('花火','frame-fireworks',600,1,''),
  ('サイバーパンク','frame-cyberpunk',900,3,''),
  ('ネオン文字','frame-neon-text',850,2,''),
  ('VIP','frame-vip',2000,5,''),
  ('パープル','frame-purple',700,1,''),
  ('星空','frame-stars',50000,5,''),
  ('ラブリー','frame-lovely',30000,3,''),
  ('炎','frame-flame',40000,4,''),
  ('クリスマス','frame-christmas',30000,50,''),
  ('冬','frame-winter',5000,50,''),
  ('サクラⅡ','frame-sakura-enhanced',10000,60,''),
  ('オーロラ','frame-aurora',24000,120,''),
  ('サンタ','frame-santa',39000,100,''),
  ('ネオン','frame-neon-style',18000,90,''),
  ('集中マスター','frame-master',100000,10','')"); // 集中マスターはティア10以上限定
}

// POST処理
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $frame_id = (int)($_POST['frame_id'] ?? 0);
  $act = $_POST['act'] ?? '';
  if ($act==='buy'){
    $fr = $pdo->prepare("SELECT * FROM frames WHERE id=?"); $fr->execute([$frame_id]); $f = $fr->fetch();
    if (!$f){ $msg='フレームが見つかりません'; }
    else {
        // 集中マスターフレームはティア10以上チェック
        if ($f['css_token']==='frame-master' && ($me['focus_tier'] ?? 0) < 10){
            $msg = '集中マスターはティア10以上のユーザーのみ購入可能です';
        } elseif ($me['coins'] >= $f['price_coins'] && $me['crystals'] >= $f['price_crystals']){
            $pdo->prepare("INSERT IGNORE INTO user_frames(user_id,frame_id) VALUES(?,?)")->execute([$me['id'],$frame_id]);
            $pdo->prepare("UPDATE users SET coins=coins-?, crystals=crystals-? WHERE id=?")->execute([$f['price_coins'],$f['price_crystals'],$me['id']]);
            $pdo->prepare("INSERT INTO reward_events(user_id,kind,amount,meta) VALUES(?,?,?,JSON_OBJECT('frame_id',?))")
                ->execute([$me['id'],'buy_frame',-$f['price_coins'],$frame_id]);
            $msg='購入しました';
        } else { $msg='残高不足'; }
    }
  } elseif ($act==='equip'){
    $own = $pdo->prepare("SELECT 1 FROM user_frames WHERE user_id=? AND frame_id=?"); $own->execute([$me['id'],$frame_id]);
    if ($own->fetch()){
      $pdo->prepare("UPDATE users SET active_frame_id=? WHERE id=?")->execute([$frame_id,$me['id']]);
      $msg='装備しました';
    } else { $msg='未購入です'; }
  }
  header("Location: shop.php?msg=".urlencode($msg)); exit;
}


$frames = $pdo->query("SELECT f.*, (SELECT 1 FROM user_frames uf WHERE uf.user_id={$me['id']} AND uf.frame_id=f.id) owned FROM frames f ORDER BY id")->fetchAll();
$active = (int)($me['active_frame_id'] ?? 0);
?>
<!doctype html><html lang="ja"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>フレームショップ - MiniBird</title>
<link rel="stylesheet" href="assets/style.css">
<script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script><script charset="utf-8">/**
 * js_common.js
 */

//get select domain from ssl pages
// var re = /\/user\/ssl/ig;
// if (re.test(location.href) == true) {
//     var select_domain = String(localStorage.getItem("da:app/domain")).trim();
//     if (select_domain && select_domain.length!==0 ) {
//         select_domain = select_domain.replace(/\"/g, '');//remove "
//         var url = '/CMD_SSL?json=yes&domain=' + select_domain + '&dnsproviders=yes&ipp=50';
//         $.ajax({
//             url,
//             success: function (data) {
//             },
//             error: function (data) {
//                 window.location.href = '/user/domains';
//             }
//         });
//     }
// }

localStorage.setItem("evolution:dark-mode", "disable");

$(document).ready(function () {
    $('.root-preloader > img').attr('src', '/_assets/img/preloader.svg');
});

//html replace setting
var PROXY_HTML_REPLACES=new Array();
// SSL lets encrypt
//PROXY_HTML_REPLACES.push({'uri':'/user/ssl/server' ,
//    'select':'div.inputCheck-label' ,
//    'search':'Create your own self signed certificate' , 'replace':'自己署名証明書を作成'});
PROXY_HTML_REPLACES.push({'uri':'/user/password' ,'select':'#root > div > div.app-content > main > section > article > section:nth-child(2) > div:nth-child(1) > div > label > span' , 'search':'Current DirectAdmin Password:' , 'replace':'現在のパスワード：'});
PROXY_HTML_REPLACES.push({'uri':'/user/plugins/jetbackup5' ,'select':'title' , 'search':'DirectAdmin ' , 'replace':''});

PROXY_HTML_REPLACES.push({'uri':'/user/dns/mx-records' ,'select':'#root > div > div.app-content > main > section > article > section:nth-child(3) > header > h2 > span' , 'search':'オプション' , 'replace':'メール配送設定'});
PROXY_HTML_REPLACES.push({'uri':'/user/dns/mx-records' ,'select':'#root > div > div.app-content > main > section > article > section:nth-child(3) > div:nth-child(2) > div > div.formElement-content > label > div > div.inputCheck-label > span' , 'search':'Use this server to handle my e-mails. If not, change the MX records and uncheck this option.' , 'replace':'他サーバーでメールを運用する場合はチェックを外します。このサーバーでメールを運用する場合はチェックする必要があります。'});




//css modify setting
var PROXY_CSS_REPLACES=new Array();


//PROXY_CSS_REPLACES.push({'uri':'*' ,'select':'#root > div > div.app-content > main > section > article > header.app-page-header' ,'css':['margin-top','0px']});
PROXY_CSS_REPLACES.push({'uri':'*' ,'select':'#root > div > div > header' ,'css':['display','none']});
PROXY_CSS_REPLACES.push({'uri':'*' ,'select':'#i_header_links' ,'css':['display','none']});
PROXY_CSS_REPLACES.push({'uri':'*' ,'select':'#root > div > div.app-content > div' ,'css':['display','none']});
PROXY_CSS_REPLACES.push({'uri':'*' ,'select':'#root > div > div.app-content > main > section > div > div > div.filters-bar' ,'css':['display','none']});
PROXY_CSS_REPLACES.push({'uri':'/user/dns' ,'select':'nav > button' ,'css':['display','block']});
PROXY_CSS_REPLACES.push({'uri':'/user/dns/mx-records' ,'select':'header > div' ,'css':['display','block']});
PROXY_CSS_REPLACES.push({'uri':'/user/dns/mx-records' ,'select':'header > div > nav' ,'css':['display','block']});
PROXY_CSS_REPLACES.push({'uri':'/user/dns/mx-records' ,'select':'header > div > nav > button' ,'css':['display','block']});



//domainselect
var _setting_domain='';

/**
 * 他に特別な処理が有る場合は適時追記していく
 */
$(function() {

    // jetbackup 不正URL制御
    str = location.href;
    re = /\/user\/plugins\/jetbackup5/ig;
    if( re.test(str) == true && getParam('path') != "index.html"){
        location.href = "/user/plugins/jetbackup5?path=index.html";
        return;
    }


    $(window).on("click",function(e){
        var str = location.href;
        var re = /\/CMD_FILE_MANAGER.*/ig;
        if( re.test(str) == true ){
            $('body > div.q-menu.q-position-engine.scroll > div > a').remove();
        }
    });

});

function getParam(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function _check_table_dkmi() {
    $('.input-checkbox').hide();
    $('#records-table > div > div.r-table-buttons-before').hide();
    $('#records-table > div > div.relative.mb-4.inline-flex.justify-end.overflow-y-hidden.p-1').hide();
    $('#root > div > div > main > section > article > header > div > nav.app-page-header-buttons').hide();
    // x._domainkey のレコード以外を非表示
    var list = $('#records-table > div > div > table > tbody.q-virtual-scroll__content');
    list.find('tr');
    var count = list.find('tr').length;
    for (var i = 0; i < count; i++) {
        var _text = list.find('tr').eq(i).find('td').eq(1).find('div').text();
        _text = _text.trim();
        // console.log('text', _text)
        if (_text != 'x._domainkey') {
            list.find('tr').eq(i).hide();
        }
        if (_text == 'x._domainkey') {
            list.find('tr').eq(i).show();
        }
    }
    $('#records-table').show();
    $('#records-table > div > div > table > tbody.q-virtual-scroll__content').show();
}

function _email_add_admin_view_btn(){
    var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
    var _back_btn = _btn.find('span').find('span');
    for (var i = 0; i < _back_btn.length; i++) {
        var t = $(_back_btn[i]).text();
        t =  t.trim();
        if (t == '加入者を追加' ) {
            let back = $(_back_btn[i]).parent().parent();
            back.show();
        }
    }
}

function _enable_hotlink_btn(){
    var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
    var _back_btn = _btn.find('span').find('span');
    for (var i = 0; i < _back_btn.length; i++) {
        var t = $(_back_btn[i]).text();
        t =  t.trim();
        if (t == '直リンク保護を無効' || t == '直リンク保護を有効' || t == '選択中のドメインを追加') {
            let back = $(_back_btn[i]).parent().parent();
            back.show();
        }
    }
}

function _enable_create_db_btn() {
    var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
    var _back_btn = _btn.find('span').find('span');
    for (var i = 0; i < _back_btn.length; i++) {
        var t = $(_back_btn[i]).text();
        t = t.trim();
        if (t == 'Create New Database') {
            let back = $(_back_btn[i]).parent().parent();
            back.show();
        }
    }
}

///evo/user/profile
function _disable_ad_db(){
    // console.log('/evo/user/profile');
    $('#root > div > div.app-content.flex.w-full.flex-col > main > section > article > div:nth-child(2)').hide();
    $('#root > div > div.app-content.flex.w-full.flex-col > main > section > article > div:nth-child(6)').hide();
    $('#root > div > div.app-content.flex.w-full.flex-col > main > section > article > div:nth-child(8)').hide();
    $('#root > div > div.app-content.flex.w-full.flex-col > main > section > article > hr:nth-child(5)').hide();
    $('#root > div > div.app-content.flex.w-full.flex-col > main > section > article > hr:nth-child(7)').hide();
    // $('#root > div > div > main > section > article > div:nth-child(4)').hide();
    var checkboxs = $.find('.input-checkbox');
    if ( $(checkboxs[0]).attr('class') =='input-checkbox --checked' ){
        $(checkboxs[0]).click();
        $(checkboxs[0]).hide();
    }
    if ( $(checkboxs[2]).attr('class') =='input-checkbox --checked' ){
        $(checkboxs[2]).click();
        $(checkboxs[2]).hide();
    }
}

//以下変更不要

/**
 * init
 */
$(function() {
    $('.app-page-header-navigation').hide();
    setTimeout(function(){
        css_change();
        replace_html();
        ext_js_filter();
    },1000)
});


function back_button_hide() {
    var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
    var _back_btn = _btn.find('span').find('span');
    for (var i = 0; i < _back_btn.length; i++) {
        var t = $(_back_btn[i]).text();
        t =  t.trim();
        if ( t == '戻る') {
            let back = $(_back_btn[i]).parent().parent();
            back.hide();
        }
    }
    // $('body > div.app > div.app-content > main > section > article > header > h1 > button').hide();
}

function display_mx_records(){
    // console.log('mx-records---');
    $('#appContent > article > section:nth-child(4)').hide();
    $('#appContent > article > section:nth-child(6)').hide();
    $('#appContent > article > section:nth-child(5) > div.formElement.w-full.py-4 > div > div > div > div').show();
}

function display_email_account(){
    const targetDivs = $('#tether-host > div > div > div');
    if (targetDivs.children('div').length === 2) {
        const divs = targetDivs.children('div');
        divs.eq(0).hide();
    }
}

function display_ftp_accounts(){
    // console.log('display_ftp_accounts');
    const targets = $('#appContent > article > section > div > div > table > tbody');
    const tds = targets.children('td');
    // console.log(tds.prevObject.eq(1));
    const trs = tds.prevObject.eq(1).children('tr');
    // console.log(trs);
    var max = trs.length;
    for(var i=0;i<max;i++){
        const targetTr = trs.eq(i);
        // console.log(targetTr);
        const targettds = targetTr.children('td');
        // console.log(targettds);
        var tmp = targettds.eq(1);
        // console.log(tmp.children('a'));
        const anchor = tmp.children('a');
        var href = anchor.attr('href');
        var text = anchor.text();
        text = text.trim();
        // console.log(href,text);
        if(href=='/evo/user/profile'){
            anchor.off('click');
            anchor.unbind('click');
            anchor.removeAttr('onclick');
            const newAnchor = anchor.clone();  // 元の要素をクローン
            newAnchor.off();                   // 全イベント解除
            newAnchor.removeAttr('onclick');   // インラインの onclick も無効化
            newAnchor.on('click', function(event) {
                event.preventDefault();        // デフォルト動作も防止
                event.stopPropagation();       // バブリングも防止
                document.location.href = '/evo/user/domains';
            });
            newAnchor.attr('href', '/evo/user/domains');
            anchor.replaceWith(newAnchor);
        }
    }
}

function display_profile(){
    // console.log('display_profile');
    // $('#appContent > article > nav > a:nth-child(1)').hide();
    $('#appContent > article > nav > a:nth-child(2)').hide();
    $('#appContent > article > nav > a:nth-child(3)').hide();
}

function display_profile_session(){
    // console.log('display_profile_session');
    $('#appContent > article > nav > a:nth-child(1)').hide();
    $('#appContent > article > nav > a:nth-child(3)').hide();
}

function check_notfound() {
    var str = $('#appContent > article > div > div > h2').text();
    str = str.trim();
    // console.log(str);
    if ( str.includes("404") || str.includes("Not Found") ) {
        $('#appContent > article > div.flex.flex-wrap.items-start').remove();
    }
}

/**
 * common filter javascript
 */
let php_setting_reload = false;

function ext_js_filter(){

    if( $('#root > div > div > main > section > article > header > div > nav > button').length==1 ){
        console.log('aaa');
        var text = $($('#root > div > div > main > section > article > header > div > nav > button > span > span')[0]).text().trim();
        if(text == 'ホームページに戻ります'){
            $('#root > div > div > main > section > article > header > div > nav > button').hide();
        }
    }

    if ($('body>div.notyf').length > 0) {
        // console.log('notyf', $('body>div.notyf').length  , $('body>div.notyf') );
        // console.log($('body>div.notyf').html());
        var html = $('body>div.notyf').html();
        // console.log(html);
        //regexp h6 tag
        var re = /<h6>(.+)<\/h6>/ig;
        var result = html.match(re);
        if (result && result.length > 0) {
            // console.log('hit' , result);
            // if find ロード string
            var re2 = /ロード/ig;
            let len = result.length;
            for(let i=0;i<len;i++){
                var result2 = result[i].match(re2);
                if (result2 && result2.length > 0) {
                    // console.log('hit2', result2);
                    $('body>div.notyf>div').hide();
                }else{
                    $('body>div.notyf>div').show();
                }
            }
        }else{
            $('body>div.notyf>div').show();
        }
    }

    $('body').hide();
    $('main').hide();
    $('body').css('opacity', '0');
    $('main').css('opacity', '0');

    back_button_hide();
    $('.app-page-header-navigation').show();
    $('body > div.app > div.app-content > header > div > div.domainsDropdown > span').css('width','5em');

    //hide back_button
    //$('#root > div > div > main > section > article > header > div > nav > button > span > span').each(function(){
    $('#root > div > div > main > section > article > header > div > nav > button').each(function(){
        var t = $(this).find("span").text();
        var reg = new RegExp('戻る','ig');
        var ret = reg.test(t);
        if(ret==true){
            $(this).hide();
        }
    });
    $('#root > div > div> main > section > article > header > div')
        .css('display','inline-flex')
        .animate({opacity: "show"}, "slow" );

    str = location.href;
    re = /\/user\/stats/ig;
    if( re.test(str) == true ){
        $('div.ui-tabs-header > div.ui-tabs-header-tab').eq(2).remove();
    }


    str = location.href;
    re = /\/user\/stats\/usage/ig;
    if( re.test(str) == true ){
        $('div.app-page-header-navigation').remove();
    }

    str = location.href;
    re = /\/user\/stats\/webalizer/ig;
    if( re.test(str) == true ){
        $('div.app-page-header-navigation').remove();
    }


    str = location.href;
    re = /\/user\/stats\/(.+)\/statistics\/webalizer/ig;
    if( re.test(str) == true ){
        $('nav > button').show();
    }

    str = location.href;
    re = /\/user\/stats\/(.+)\/statistics\/awstats/ig;
    if( re.test(str) == true ){
        $('nav > button').show();
    }

    str = location.href;
    re = /\/user\/stats\/(.+)\/statistics\/webalizer\/awstats/ig;
    if( re.test(str) == true ){
        $('nav > button').show();
    }

    // str = location.href;
    // re = /\/user\/domains/ig;
    // if( re.test(str) == true ){
    //     $('nav > button.-theme-primary').hide();
    // }

    str = location.href;
    re = /\/evo\/user\/domains/ig;
    if( re.test(str) == true ){
        // console.log('/evo/user/domains');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == '戻る' || t=='ドメインの名前を変更') {
                let back = $(_back_btn[i]).parent().parent();
                back.hide();
            }else if(t=='新しく追加'){
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
        $('#appContent > article > div > div > button:nth-child(2)').show();
    }

    // /evo/user/domains/add-domain
    str = location.href;
    re = /\evo\/user\/domains\/add\-domain/ig;
    if( re.test(str) == true ){
        // console.log('/evo/user/domains/add-domain');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == '戻る') {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    str = location.href;
    re = /\/user\/dns/ig;
    if( re.test(str) == true ){
        $('#records-table').show();
        $('#root > div > main > section> article > header > div > nav > button.button.-theme-neutral').hide();
    }


    str = location.href;
    re = /\/user\/email\/create-account/ig;
    if( re.test(str) == true ){
        $('nav > button').show();
    }


    str = location.href;
    re = /\/evo\/user\/password/ig;
    if( re.test(str) == true ){
        $('nav > button').show();
        $('header > div > nav.app-page-header-links').hide();
        back_button_hide();
        // let dacheckd = $('body > div.app > div.app-content > main > section > article > section:nth-child(2) > div:nth-child(3) > div > div > div > div:nth-child(1)')
        let dacheckd = $('#root > div > div > main > section > article > section:nth-child(2) > div:nth-child(3) > div > div > div > div > div:nth-child(1)');
        if (dacheckd && dacheckd.attr('class')=='input-checkbox --checked'){
            dacheckd.click();
            dacheckd.hide();
        }
        // let dbcheckd = $('body > div.app > div.app-content > main > section > article > section:nth-child(2) > div:nth-child(3) > div > div > div > div:nth-child(3)')
        let dbcheckd = $('#root > div > div > main > section > article > section:nth-child(2) > div:nth-child(3) > div > div > div > div > div:nth-child(3)');
        if(dbcheckd && dbcheckd.attr('class')=='input-checkbox --checked'){
            dbcheckd.click();
            dbcheckd.hide();
        }
        // $('#root > div > main > section > article > header > div > nav').hide();
    }

    // /evo/user/email/forwarders 未翻訳部分の翻訳
    str = location.href;
    re = /\/evo\/user\/email\/forwarders/ig;
    if( re.test(str) == true ){
        var fs = $('.Select__Dropdown__Items__Item').find();
        let c = fs.prevObject.length;
        for(var i=0; c>i ; i++){
            var text = $(fs.prevObject[i]).find('span').text();
            text = text.trim();
            if (text ==':blackhole:'){
                $(fs.prevObject[i]).find('span').text('ブラックホール');
            }
            if (text == ':fail:'){
                $(fs.prevObject[i]).find('span').text('失敗');
            }
        }
    }

    str = location.href;
    re = /\/evo\/user\/ftp-accounts/ig;
    if( re.test(str) == true ){
        // console.log('/evo/user/ftp-accounts');
	display_ftp_accounts();
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == 'FTPアカウントを作成') {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    // /evo/user/profile/sessions
    str = location.href;
    re = /\/evo\/user\/profile\/sessions/ig;
    if( re.test(str) == true){
        display_profile_session();
    }

    // /evo/user/profile/general
    re2 = /\/evo\/user\/profile\/general/ig;
    if(re2.test(str) == true){
        display_profile();
    }

    // /evo/user/ftp-accounts/create
    str = location.href;
    re = /\/evo\/user\/ftp-accounts\/create/ig;
    if( re.test(str) == true ){
        // console.log('/evo/user/ftp-accounts/create');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == '戻る') {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    // /evo/user/handlers
    str = location.href;
    re = /\/evo\/user\/handlers(.*)/ig;
    if( re.test(str) == true ){
        // console.log('/evo/user/handlers and more');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == 'ハンドラを作成' ) { // || t =='システムApacheハンドラ'
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }else if (t =='システムApacheハンドラ'){
                let back = $(_back_btn[i]).parent().parent();
                back.hide();
            }
        }
    }

    // jetbackup ヘッダ削除
    str = location.href;
    re = /\/user\/plugins\/jetbackup5/ig;
    if( re.test(str) == true){
        //remove()すると動作不良になる
        $('#plugin-host-header').hide();
        $('header').hide();
    }

    // /evo/user/redirects
    str = location.href;
    re = /\/evo\/user\/redirects/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/redirects');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == '新しいリダイレクトを作成') {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }
    
    // /evo/user/subdomains
    str = location.href;
    re = /\/evo\/user\/subdomains/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/subdomains');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == 'サブドメインを追加') {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    // /evo/user/ssl/server
    str = location.href;
    re = /\/evo\/user\/ssl(.*)/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/ssl and more');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == 'SSLを無効' || t =='SSL CA証明書') {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    // /evo/user/email/accounts
    str = location.href;
    re = /\/evo\/user\/email\/accounts/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/email/accounts');
        display_email_account();
        // $('#appContent > article > div.flex.flex-wrap.items-start.justify-end.gap-2.empty\:hidden').show();
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == 'アカウントを作成' ) {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    // /evo/user/email/forwarders
    str = location.href;
    re = /\/evo\/user\/email\/forwarders/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/email/forwarders');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == 'Eメール転送先を作成' ) {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    // /evo/user/email/autoresponders
    str = location.href;
    re = /\/evo\/user\/email\/autoresponders/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/email/autoresponders');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == '自動返信を作成' ) {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    // /evo/user/email/autoresponders/create
    str = location.href;
    re = /\/evo\/user\/email\/autoresponders\/create/ig;
    if( re.test(str) == true ){
        // console.log('/evo/user/email/autoresponders/create');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == '戻る') {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    // /evo/user/email/vacations
    str = location.href;
    re = /\/evo\/user\/email\/vacations/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/email/vacations');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == 'バケーションメッセージを設定' ) {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }
    // /evo/user/email/vacations/create
    str = location.href;
    re = /\/evo\/user\/email\/vacations\/create/ig;
    if( re.test(str) == true ){
        // console.log('/evo/user/vacations/create');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == '戻る') {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    // /evo/user/email/lists
    str = location.href;
    re = /\/evo\/user\/email\/lists/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/email/lists');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == 'メーリングリストを作成' ) {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
        _email_add_admin_view_btn();
    }

    // /evo/user/hotlinks
    str = location.href;
    re = /\/evo\/user\/hotlinks/ig;
    if( re.test(str) == true){
        _enable_hotlink_btn();
    }

    // /evo/user/database
    str = location.href;
    re = /\/evo\/user\/database/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/database');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == '新しいデータベースを作成' ) {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
        _enable_create_db_btn();
    }
    // /evo/user/database/create
    str = location.href;
    re = /\/evo\/user\/database\/create/ig;
    if( re.test(str) == true ){
        // console.log('/evo/user/database/create');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == '戻る') {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
        _enable_create_db_btn();
    }

    // /evo/user/ssh-keys/public
    str = location.href;
    re = /\/evo\/user\/ssh-keys\/public/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/ssh-keys/public');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == 'キーを作成' ) {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    // /evo/user/cronjobs
    str = location.href;
    re = /\/evo\/user\/cronjobs/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/cronjobs');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == 'Cron Jobを作成' ) {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }
    // /evo/user/cronjobs/create
    str = location.href;
    re = /\/evo\/user\/cronjobs\/create/ig;
    if( re.test(str) == true ){
        // console.log('/evo/user/cronjobs/create');
        var _btn = $('.app-page-header-navigation').find('.app-page-header-buttons').find('button');
        var _back_btn = _btn.find('span').find('span');
        for (var i = 0; i < _back_btn.length; i++) {
            var t = $(_back_btn[i]).text();
            t =  t.trim();
            if ( t == '戻る') {
                let back = $(_back_btn[i]).parent().parent();
                back.show();
            }
        }
    }

    // /evo/user/email/lists/admins/view
    str = location.href;
    re = /\/evo\/user\/email\/lists\/admins\/view/ig;
    if( re.test(str) == true){
        // console.log('/evo/user/email/lists/admins/view');
        $('#root > div > div > header > div > div> div > div > div > div > button').on('click', function (e) {
            _email_add_admin_view_btn();
        });
        _email_add_admin_view_btn();
    }

    // /evo/user/profile
    str = location.href;
    re = /\/evo\/user\/profile/ig;
    if( re.test(str) == true ){
        _disable_ad_db();
    }

    // ssl let'sencrypt ワイルドカード非表示
    str = location.href;
    re = /\/user\/ssl\/letsencrypt/ig;
    if( re.test(str) == true){
        $('div.inputGroup-right').remove();
    }

    //default email account not change password
    str = location.href;
    re = /\/user\/email\/accounts/ig;
    if( re.test(str) == true){
        $('.ui-actions').on('mouseup',function(e){
            setTimeout(() => {
                if($('body > div > div > div > div > a:nth-child(1) > span').text()=='パスワードを変更'){
                    $('body > div > div > div > div > a:nth-child(1)').remove();
                }                    
            }, 40);
        });
    }

    str = location.href;
    re = /\/user\/email\/vacations/ig;//vacations settings
    re2 = /\/user\/email\/vacations\/create/ig;
    re3 = /\/user\/hotlinks/ig;//直リンク保護 
    re4 = /\/user\/php-settings/ig;//PHP設定
    re5 = /\/user\/email\/catch-all/ig;//CatchAll設定
    re6 = /\/user\/handlers/ig;//システムApacheのハンドラ
    re7 = /\/user\/email\/spamassassin/ig;//メールスパムアサシン
    if( re.test(str) == true || re2.test(str) == true || re3.test(str) == true || re4.test(str) == true
        || re5.test(str) == true || re6.test(str) == true || re7.test(str) == true ){
        if(_setting_domain==''){
            _setting_domain = $("#root > div > div > header > div > div> div > div > div > div > button > span.Select__Button__Label").text();
        }else{
            var _current_setting_domain = $("#root > div > div > header > div > div> div > div > div > div > button > span.Select__Button__Label").text();
            if(_current_setting_domain!=_setting_domain){
                _setting_domain = _current_setting_domain;
                setTimeout(function(){
                    location.reload();
                },180);
            }
        }
    }

    str = location.href;
    re = /\/user\/php-settings/ig;//PHP設定
    re2 = /\/user\/ftp-accounts/ig;//ftp設定
    if (re.test(str) == true || re2.test(str) == true) {
        $(document).on('click', '[data-radix-vue-combobox-item]', function (e) {
            if (php_setting_reload === false) {
                // console.log('クリックされました:', $(this));
                let sp = $(this).find('span');
                if (sp.length == 1) {
                    //console.log($(sp[0]).text());
                    //console.log($(sp[0]).attr('class'));
                    if ($(sp[0]).attr('class') == 'flex-1 truncate') {
                        php_setting_reload = true;
                        setTimeout(function () {
                            // location.reload();
                            str = location.href;
                            re = /\/user\/php-settings/ig;//PHP設定
                            re2 = /\/user\/ftp-accounts/ig;//ftp設定
                            if (re2.test(str) == true) {
                                location.href = '/evo/user/ftp-accounts';
                            }
                            if (re.test(str) == true) {
                                location.href = '/evo/user/php-settings';
                            }
                        }, 180);
                    }
                }
            }
        });
    }

    str = location.href;
    re = /\/user\/email\/spamassassin/ig;//spamassassin settings
    if( re.test(str) == true){
        $('#root > div > div > main > section > article > header > div > nav.app-page-header-links > a').hide();
    }

    str = location.href;
    re = /\/user\/domains\/add\-domain/ig;
    if (re.test(str) == true) {
        $('main > section > article > header > div > nav.app-page-header-buttons > button > span').show();
    }

    // ドメイン管理≫ドメインを選択≫ドメイン変更画面
    str = location.href;
    re = /\/user\/domains\/domain\/.+/ig
    if (re.test(str) == true) {
        $('main > section > article > header > div > nav.app-page-header-buttons > button > span').show();
    }

    // FTP設定≫FTPアカウントの作成
    str = location.href;
    re = /\/user\/ftp\-accounts\/create/ig
    re2 = /\/evo\/user\/ftp\-accounts\/create/ig
    if (re.test(str) == true || re2.test(str) == true) {
        $('main > section > article > header > div > nav.app-page-header-buttons > button > span').show();
    }

    // メールアカウント設定≫メールアカウントを作成
    str = location.href;
    re = /\/evo\/user\/email\/create-account/ig
    re2 = /\/user\/email\/create-account/ig
    if (re.test(str) == true || re2.test(str) == true) {
        $('main > section > article > header > div > nav.app-page-header-buttons > button > span').show();
    }

    // アクセス統計≫ドメインを選択≫統計レポートを表示
    re = /\/evo\/user\/stats\/.+\/statistics\/webalizer/ig;
    str = location.href;
    if (re.test(str) == true) {
        $('main > section > article > header > div > nav.app-page-header-buttons > button > span').show();
    }

    // /evo/user/dns
    str = location.href;
    re = /\/evo\/user\/dns/ig;
    if (re.test(str) == true) {
        $('#root > div > div > header > div > div> div > div > div > div > button').on('click',function(e){
            _check_table_dkmi();
            $('#root > div > div > main > section > article > section').show();
            $('#records-table').show();
        });
        _check_table_dkmi();
    }

    str = location.href;
    re = /\/evo\/user\/dns\/mx-records/ig;
    if (re.test(str) == true) {
        display_mx_records();
    }

    var homebt = $('body > div.app > div.app-content > main > section > article > header > div > nav.app-page-header-buttons > button > span > span.label-slot > span:nth-child(2)');
    var hometext = homebt.text();
    hometext = hometext.trim();
    if (hometext == 'ホームページに戻ります' ){
        $('.app-page-header-buttons').hide();
    }


    str = location.href;
    re = /\/CMD_FILE_MANAGER/ig;
    if (re.test(str) == true) {
        $('#q-app > div > div > div > header > div > a').hide();
        $('#q-app > div > div > div > div.q-drawer-container > aside > div > aside > div:nth-child(3) > div').hide();
        $('#q-portal--menu--1 > div > div > div:nth-child(9)').hide();
        $('#q-portal--menu--2 > div > div > div:nth-child(9)').hide();
        $('#q-portal--menu--3 > div > div > div:nth-child(9)').hide();
        $('#q-portal--menu--4 > div > div > div:nth-child(9)').hide();
        $('#q-portal--menu--5 > div > div > div:nth-child(9)').hide();
        $('#q-portal--menu--6 > div > div > div:nth-child(9)').hide();
        $('#q-portal--menu--7 > div > div > div:nth-child(9)').hide();
        $('#q-portal--menu--8 > div > div > div:nth-child(9)').hide();
        $('#q-portal--menu--9 > div > div > div:nth-child(9)').hide();
    }

    /*
    $('.header-logo').hide();
    $('.userbar').hide();
    $('.menu-trigger').hide();
    $('.nav-grid-trigger').hide();
    */
    $('.menu-trigger').hide();
    $('.nav-grid-trigger').hide();
    $('.header-logo').remove();

    $('#root > div > div > header').css({'display':'block','height': 'auto','border': 'none','background-color':'#FFFFFF'});
    $('#root > div > div > header > div').css('float','right');
    $('.app-header').css('border-bottom', 'none');
    $('.app-header').css('height', '6rem');
    $('body').css('opacity', 1);
    $('main').css('opacity', 1);
    $('body').show();
    $('main').show();
    $('#appHeader').hide();
    check_notfound();
}


/**
 * location change listener
 */


var denypaths = [ "/","/user/stats/account" ];

var href = location.href;
var observer = new MutationObserver(function(mutations) {
    observer.disconnect();
    if(denypaths.includes(location.pathname)){
        //history.back();
        //return;
    }
    css_change();
    replace_html();
    ext_js_filter();
    observer.observe(document, { childList: true, subtree: true });
    // setTimeout(function(){
    //     css_change();
    //     replace_html();
    //     ext_js_filter();
    //     observer.observe(document, { childList: true, subtree: true });
    // },500)
});

observer.observe(document, { childList: true, subtree: true });




/**
 * html replace
 */
function replace_html(){
    let c = PROXY_HTML_REPLACES.length;
    for(var i=0;i<c;i++){
        var pattern = PROXY_HTML_REPLACES[i].uri;
        var str = location.href;
        var select = PROXY_HTML_REPLACES[i].select;

        if(  PROXY_HTML_REPLACES[i].uri =='*'){//all
            $(select).each(function(){
                var _html = $(this).html();
                var replace_html = _html.replace(new RegExp(PROXY_HTML_REPLACES[i].search,'ig'),PROXY_HTML_REPLACES[i].replace);
                $(this).html(
                    replace_html
                );
            });
        }else if(str.indexOf(pattern) > -1){//uri mache
            $(select).each(function(){
                var _html = $(this).html();
                var replace_html = _html.replace(new RegExp(PROXY_HTML_REPLACES[i].search,'ig'),PROXY_HTML_REPLACES[i].replace);
                $(this).html(
                    replace_html
                );
            });
        }
    }
}

/**
 * css changer
 */
function css_change(){
    let c = PROXY_CSS_REPLACES.length;
    for(var i=0;i<c;i++){
        var pattern = PROXY_CSS_REPLACES[i].uri;
        var str = location.href;
        var select = PROXY_CSS_REPLACES[i].select;
        if(  PROXY_CSS_REPLACES[i].uri =='*'){//all
            $(select).each(function(){
                $(this).css(PROXY_CSS_REPLACES[i].css[0] , PROXY_CSS_REPLACES[i].css[1] );
            });
        }else if(str.indexOf(pattern) > -1){//uri mache
            $(select).css(PROXY_CSS_REPLACES[i].css[0] , PROXY_CSS_REPLACES[i].css[1] );
            $(select).each(function(){
                $(this).css(PROXY_CSS_REPLACES[i].css[0] , PROXY_CSS_REPLACES[i].css[1] );
            });
        }
    }
}
</script>

<style>

#root > div > div.app-content > header {
	display:none;
}

#root > div > div.app-content > main > section > div > div > div.filters-bar{
	display:none;
}

#root > div > div.app-content > div{
	display:none;
}

#i_header_links{
	display:none;
}

#root > div > div> main > section > article > header > div{
	display:none;
}

#i_footer{
	display:none;
}

footer{
	display:none;
}

/*
#q-app > div > div > header > div > div.da-logo-wrapper.q-mx-md.q-my-sm.q-toolbar__title.ellipsis{
	display:none;
}
*/

#maximizeToggle{
	display:none;
}

section.search{
	display:none;
}

#records-table, #records-table > div > div:nth-child(2) > div > div:nth-child(2) > button:nth-child(2){
	display:none;
}


.menu-trigger{ display:none; }
.nav-grid-trigger{ display:none; }
.header-logo{ display:none; }
.userbar{ display:none; }
header > div > div.q-toolbar__title{ display:none; }

#q-app\ bg-primary > div > div > div.q-drawer-container > aside > div > aside > div:nth-child(1) > a{ display:none;}
#q-app\ bg-primary > div > div > header > div > a { display:none;}

#root>div>div>main>section>article>header>h1>button{display: none;}

.breadcrumbs{ display: none; }
.header-spacer{width: 100%;}
.app-header{
	border-bottom:none;
	height: 6rem;
	display: none;
}
/* #root>div.app-content.width\:100\%.fx\:dir\:column>main>section>article>header>div>nav>button{display: none;} */
#root>div.app-content.width\:100\%.fx\:dir\:column>main>section>article>header>div>nav>button.button.-theme-neutral.-size-big.cursor\:pointer{
	display: none;
}
#root>div.app-content.width\:100\%.fx\:dir\:column>header>div>div.domainsDropdown.fx\:dir\:row.fx\:cross\:center>span{width: 60px;}
.header-logo{
	opacity: 0;
	background-image: none;
}

body,main{
	display: none;
	opacity: 0;
}

#root>div>main>section>article>header>h1>button{ display: none; }
/* body>div.notyf>div{ display: none; } */

#q-app>div>div>div>div.q-drawer-container>aside>div>aside>div:nth-child(1)>a{
	display: none;
}

#appHeader > div{display: none; }
#gridLayout > div > div{ display: none; }
#breadcrumbs{display: none;}
#Toolbar > div > button.grid.h-24.min-w-32.cursor-pointer.place-content-start.border-0.bg-inherit.hover\:bg-white\/10.focus\:border-0.max-md\:h-16.md\:w-\[380px\]{display: none;}
#Toolbar > div > button:nth-child(9){display: none;}
#appContent > article > div.border-default-gray.flex.flex-wrap.justify-between.gap-2.pb-2.sm\:border-b > div.flex.flex-wrap.items-center.justify-end.font-sans > button:nth-child(2){display: none;}
#appContent > article > div.border-default-gray.flex.flex-wrap.justify-between.gap-2.pb-2.sm\:border-b > div.flex.flex-wrap.items-center.justify-end.font-sans > button:nth-child(3){display: none;}
#appContent > article > div:nth-child(4) > div.col-span-2.min-w-full > div.mt-4.flex.items-center.justify-start.gap-4 > a{display: none;}
#appContent > article > div.border-default-gray.flex.flex-wrap.justify-between.gap-2.pb-2.sm\:border-b > div.flex.items-center.gap-2.overflow-hidden > a{display: none;}
</style>

</head>

<body>
<header class="topbar"><div class="logo"><a class="link" href="./">← 戻る</a></div></header>
<main class="layout">
<section class="center">
  <div class="card"><h3>残高</h3>コイン: <?=$me['coins']?> / クリスタル: <?=$me['crystals']?></div>
  <?php if(isset($_GET['msg'])): ?><div class="notice"><?=htmlspecialchars($_GET['msg'])?></div><?php endif; ?>
  <?php foreach ($frames as $f): ?>
    <div class="card">
      <div class="<?=$f['css_token']?>">
        <h3><?=$f['name']?></h3>
        <p>コイン <?=$f['price_coins']?> / クリスタル <?=$f['price_crystals']?></p>
        <?php if($f['owned']): ?>
          <form method="post"><input type="hidden" name="frame_id" value="<?=$f['id']?>"><input type="hidden" name="act" value="equip"><button>装備する<?=$active===$f['id']?'（現在）':''?></button></form>
        <?php else: ?>
          <form method="post"><input type="hidden" name="frame_id" value="<?=$f['id']?>"><input type="hidden" name="act" value="buy"><button>購入する</button></form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</section>
</main>
</body></html>
