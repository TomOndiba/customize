<html>    <head>        <meta http-equiv="X-UA-Compatible" content="IE=8" />                <script type="text/javascript" src="http://www.theyaktrail.com/visitortarget/application/views/site/js/plugins/jquery-1.7.min.js"></script>        <script type="text/javascript">            $j(function(){                               $j('#switch_lang li a').click(function() {                   $j('#__set_lng').val($j(this).attr('rel'));                   $j('#frmLang').submit();               });               })        </script>        <style>.active_lang{font-weight:bold;}</style>    </head>    <body>    <?php require_once(dirname(__FILE__).'/../site/inc/noscript.php')?>         <p><?=anchor(fsite_url('user/login'), 'Login')?></p>        Plans:        <ul>        <?php foreach($packages as $r){?>                    <li style="float:left; width: 180px; margin-right:10px;border:2px dotted #A6A3A3; padding: 10px; list-style: none">                <b><?=$r->pkg_name?></b><br />                <br /><em><?='Price: '.def_currency($r->pkg_price)?></em>                <p><?=$r->intro?></p>                <?=anchor(fsite_url('user/register/'.$r->pkg_id), ((int)$r->free_trial ? 'Free Trial: Start Today' : 'Sign Up: Click Here'))                ?>                            </li>                <?php }?>        </ul>    </body></html>