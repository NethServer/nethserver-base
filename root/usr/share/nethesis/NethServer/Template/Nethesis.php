<?php
/* @var $view \Nethgui\Renderer\Xhtml */ 

$filename = basename(__FILE__);
$bootstrapJs = <<<"EOJS"
/*
 * bootstrapJs in {$filename}
 */
jQuery(document).ready(function($) {
    $('script.unobstrusive').remove();
    $('#pageContent').Component();
    $('.HelpArea').HelpArea();
    $('#hiddenAllWrapperCss').remove();

    // push initial ui state
    var target = window.location.href.split(/\#!?/, 2)[1];
    if(target) {
        $('#' + target).trigger('nethguishow');
        history.replaceState({'target': target}, '', '#!' + target);
    }
});
EOJS;

$globalUseFile = new \ArrayObject();

/*
 * jQuery & jQueryUI libraries:
 */
if (defined('NETHGUI_DEBUG') && NETHGUI_DEBUG === TRUE) {
    $globalUseFile->append('js/jquery-1.7.1.js');
    $globalUseFile->append('js/jquery-ui-1.8.18.custom.js');
} else {
    // require global javascript resources:
    $globalUseFile->append('js/jquery-1.7.1.min.js');
    $globalUseFile->append('js/jquery-ui-1.8.18.custom.min.js');
}

/*
 * jQuery plugins
 */
$globalUseFile->append('js/jquery.dataTables.min.js');
$globalUseFile->append('js/jquery.qtip.min.js');

$lang = $view->getTranslator()->getLanguageCode();
if ($lang !== 'en') {
    $globalUseFile->append(sprintf('js/jquery.ui.datepicker-%s.js', $lang));
}


$view
    ->includeFile('Nethgui/Js/jquery.nethgui.loading.js')
    ->includeFile('Nethgui/Js/jquery.nethgui.helparea.js')
    ->includeJavascript($bootstrapJs)
    // CSS:
    ->useFile('css/ui/jquery-ui-1.8.16.custom.css')
    ->useFile('css/jquery.qtip.min.css')
    ->useFile('css/font-awesome.css')
    ->useFile('css/base.css')
;
// Custom colors
if (isset($view['colors']) && count($view['colors']) == 3) {
    $view->includeCss("
        #pageHeader {
            background: {$view['colors'][0]} !important;
        }
        .secondaryContent .contentWrapper {
            background: {$view['colors'][1]} !important;
        }
        .DataTable th.ui-state-default, .Navigation.Flat a.currentMenuItem, .Navigation.Flat a:hover, .header {
            color: {$view['colors'][2]} !important;
        }
        #Login .ui-widget-header {
             background: {$view['colors'][1]} !important;
        }
        #Login {
            border: 1px solid {$view['colors'][1]} !important;
        }

    ");
}
?><!DOCTYPE html>
<html lang="<?php echo $view['lang'] ?>">
    <head>
        <title><?php echo htmlspecialchars($view['company'] . " - " . $view['moduleTitle']) ?></title>
        <link rel="icon"  type="image/png"  href="<?php echo $view['favicon'] ?>" />
        <meta name="viewport" content="width=device-width" />  
        <script>document.write('<style id="hiddenAllWrapperCss" type="text/css">#allWrapper {display:none}</style>')</script><?php echo $view->literal($view['Resource']['css']) ?>
    </head>
    <body>
        <div id="allWrapper">
            <div id="pageHeader">
            <?php if ( ! $view['disableHeader']): ?>
                
                    <div id="headerMenu"><a href="<?php echo $view->getModuleUrl('/UserProfile') ?>"><?php echo $T('UserProfile_Title'); ?></a></div>
                    <h1 id="ModuleTitle"><?php echo htmlspecialchars($view['moduleTitle']) ?></h1>
                    <div id="productTitle"><img src='<?php echo $view['logo']; ?>'/></div>
            <?php endif; ?>
            </div>
            <div id="pageContent">                
                <div class="primaryContent" role="main">
                    <?php 
                         echo $view['notificationOutput'];
                         echo $view['trackerOutput'];
                         echo $view['currentModuleOutput'];
                    ?>
                </div>
                <?php if ( ! $view['disableMenu']): ?><div class="secondaryContent" role="menu"><div class="contentWrapper"><h2><?php echo htmlspecialchars($view->translate('Other modules')) ?></h2><?php echo $view['menuOutput'] . $view['logoutOutput'] ?></div></div><?php endif; ?>
            </div><?php echo $view['helpAreaOutput'] ?>
            <?php if ( ! $view['disableFooter']): ?><div id="footer"><p><?php echo htmlspecialchars($view['company'] . ' - ' . $view['address']) ?></p></div><?php endif; ?>
        </div><?php
        array_map(function ($f) use ($view) {
            printf("<script src='%s%s'></script>", $view->getPathUrl(), $f);
        }, iterator_to_array($globalUseFile));
        echo $view->literal($view['Resource']['js'])
        ?>
    </body>
</html>
