<?php
$extCss = "
#Welcome h1 {
  color: #003C7B;
  font-size: 2em;
  font-weight: bold;
}
#Welcome h2 {
  color: #003C7B;
  font-size: 1.3em;
  font-weight: bold;
  margin-bottom: 1em;
  margin-top: 1em;
}
#Welcome li {
  list-style-type:circle;
  margin-left: 2em;
}
p {
  margin-bottom: 1em;
  margin-top: 1em;
}
p.code {
  margin-bottom: 1em;
  margin-top: 1em;
  margin-left: 2em;
  margin-right: 2em;
  font-family: monospace;
  text-align: justify;
  text-justify: newspaper;
  max-width: 40em;
}
";
$view->includeCss($extCss);
?>
<div id='<?php echo $view->getUniqueId() ?>'>
<h1 id='<?php echo $view->getUniqueId() ?>'><?php echo $T('NethServer_Welcome_Header') ?></h1>
<p><?php echo $T('Welcome_Body') ?></p>
<h2><?php echo $T('Links') ?></h2>
<ul>
<li><a href='http://nethserver.nethesis.it'><?php echo $T('official_site') ?></a></li>
<li><a href='http://dev.nethesis.it/projects/nethserver'><?php echo $T('technical_documentation') ?></a></li>
<li><a href='http://www.nethesis.it'>Nethesis</a></li>
</ul>
<h2><?php echo $T('License') ?></h2>
<p class='code'>
  Copyright 2012 - Nethesis srl
</p><p class='code'>
  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  any later version.

</p><p class='code'>
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

</p><p class='code'>
  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
</pre>
</div>
