<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2019 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

include("html_header.php");
use \app\conf\App;
?>

<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:700,300">
<link rel="stylesheet" type="text/css"
      href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

<link rel="stylesheet" href="/js/ext/examples/ux/superboxselect/superboxselect.css"/>
<link rel="stylesheet" href="/js/ext/resources/css/ext-all-notheme.css"/>
<link rel="stylesheet" href="/js/ext/resources/css/xtheme-dark.css"/>
<link rel="stylesheet" href="/js/ext/examples/ux/gridfilters/css/GridFilters.css"/>
<link rel="stylesheet" href="/js/ext/examples/ux/gridfilters/css/RangeMenu.css"/>

<link rel="stylesheet" href="/css/build/styles.min.7ac0fcf833facf8b.css">

</head>
<body>
<div id="loadscreen">
    <div class="center text">
        <div id="loadscreentext"></div>
    </div>
    <div class="center bounce">
        <div class="bouncywrap">
            <div class="dotcon dc1">
                <div class="dot"></div>
            </div>
            <div class="dotcon dc2">
                <div class="dot"></div>
            </div>
            <div class="dotcon dc3">
                <div class="dot"></div>
            </div>
        </div>

    </div>
</div>
<div style="display:none">
    <div id="map-settings"></div>
    <div id="authentication">
        HTTP Basic auth password for WMS and WFS
    </div>
    <div id="apikey">
        API key. This is used for the SQL API. You can always change the key
        <br>
        <br>
        <b><span id='apikeyholder'></span></b>
    </div>
    <div id="wfs-dialog">
        <table border="0">
            <tbody>
            <tr>
                <td>For WFS-T (editable WFS). This service supports workflow management and track changes.</td>
            </tr>
            <tr>
                <td>
                    <input class="service-url" type="text" readonly="readonly"
                           value="<?php echo \app\conf\App::$param['protocol'] ?: "http" ?>://<?php echo $_SERVER['HTTP_HOST']; ?>/wfs/<?php echo ($_SESSION['subuser'] ? $_SESSION['screen_name'] . "@" : "") . $_SESSION['parentdb']; ?>/<?php echo (\app\inc\Input::getPath()->part(3)) ? \app\inc\Input::getPath()->part(3) : "public"; ?>/<?php echo !empty(\app\conf\App::$param["epsg"]) ? \app\conf\App::$param["epsg"] : "4326" ?>"
                    />
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div id="wms-dialog">
        <table border="0">
            <tbody>
            <tr>
                <td>The OWS services includes WMS (up to 1.3) and WFS (up to 2.0).</td>
            </tr>
            <tr>
                <td>
                    <input class="service-url" type="text" readonly="readonly"
                           value="<?php echo \app\conf\App::$param['protocol'] ?: "http" ?>://<?php echo $_SERVER['HTTP_HOST']; ?>/ows/<?php echo ($_SESSION['subuser'] ? $_SESSION['screen_name'] . "@" : "") . $_SESSION['parentdb']; ?>/<?php echo (\app\inc\Input::getPath()->part(3)) ?: "public"; ?>/"/>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div id="tms-dialog">
        <table border="0">
            <tbody>
            <tr>
                <td>Tile Map Service (WMTS, TMS and Google xyz).</td>
            </tr>
            <tr>
                <td>
                    <input class="service-url" type="text" readonly="readonly"
                           value="<?php echo \app\conf\App::$param['protocol'] ?: "http" ?>://<?php echo $_SERVER['HTTP_HOST']; ?>/mapcache/<?php echo ($_SESSION['subuser'] ? $_SESSION['screen_name'] . "@" : "") . $_SESSION['parentdb']; ?>/[wms|wmts|gmaps|tms]"
                    />
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div id="sql-dialog">
        <table border="0">
            <tbody>
            <tr>
                <td>The HTTP SQL API end point.</td>
            </tr>
            <tr>
                <td>
                    <input class="service-url" type="text" readonly="readonly"
                           value="<?php echo \app\conf\App::$param['protocol'] ?: "http" ?>://<?php echo $_SERVER['HTTP_HOST']; ?>/api/v2/sql/<?php echo ($_SESSION['subuser'] ? $_SESSION['screen_name'] . "@" : "") . $_SESSION['parentdb']; ?>?q=[query]&key=[your_api_key]"
                    />
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div id="elasticsearch-dialog">
        <table border="0">
            <tbody>
            <tr>
                <td>Elasticsearch API end point.</td>
            </tr>
            <tr>
                <td>
                    <input class="service-url" type="text" readonly="readonly"
                           value="<?php echo \app\conf\App::$param['protocol'] ?: "http" ?>://<?php echo $_SERVER['HTTP_HOST']; ?>/api/v1/elasticsearch/[map|bulk|search|delete]/<?php echo $_SESSION['parentdb']; ?>/[index]/[type]"
                    />
                </td>
            </tr>
            <tr>
                <td>map: PUT, bulk: POST, search: GET, delete: DELETE</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
<div id="spinner"><span></span><i class="fa fa-cog fa-spin fa-3x fa-fw"></i></div>
<script src="/api/v1/baselayerjs"></script>
<script>
    window.__ = function (string, toolTip) {
        'use strict';
        var str;
        if (typeof gc2i18n !== 'undefined') {
            if (gc2i18n.dict[string]) {
                str = gc2i18n.dict[string];
            } else {
                str = string;
            }
            if (toolTip) {
                str = " <span class='tt' ext:qtip='" + str + "' ext>[?]</span>";
            }
        }
        return str;
    };
    document.write("<script src='/js/i18n/" + window.gc2Al + ".js'><\/script>");
</script>

<script>
    document.getElementById("loadscreentext").innerHTML = __("GC2 Admin is loading. Hang on...");
</script>

<script src="//maps.googleapis.com/maps/api/js=<?php echo App::$param["googleApiKey"]; ?>&v=3&libraries=places"></script>
<script src="/js/OpenLayers-2.12/OpenLayers.gc2.af805fef5d420a9c.js"></script>

<script src="/js/admin/build/all.min.6a13347dcd2303af.js"></script>

<script src="/api/v1/js/api.be89462101e8b88a.js"></script>
<script src="/api/v3/js/geocloud.389ece910c1089ae.js"></script>

</body>
</html>

