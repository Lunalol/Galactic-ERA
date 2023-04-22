{OVERALL_GAME_HEADER}

<div id='ERAplayArea'>
	<div id='ERAboard'>
	</div>
</div>
</div>
<div id='ERApanels'>
	<div id='ERA-DP'></div>
</div>
<div id='ERAcontrols'>
    <span id='ERAzoomMinus'>🔍</span>
    <input id='ERAzoomLevel' type='range' style='vertical-align:middle;'/>
    <span id='ERAzoomPlus'>🔎</span>
	<span id='ERAhome' class="fa fa-home fa-lg"></span>
	<span id='ERArotateAntiClockwise'>⭯</span>
    <input id='ERArotate' type='range' min='-180' max='180' value='0' style='vertical-align:middle;'/>
    <span id='ERArotateClockwise'>⭮</span>
</div>

<script type="text/javascript">
	var ERAchoice = "<div class='ERAchoice' id='ERAchoice'></div>";
	var ERAstarPeople = "<div class='ERAstarPeople' tabindex='-1' starpeople='${starpeople}'><img style='width:100%;'/></div>";
	var ERAdominationCards = "<div class='ERAdominationCards' id='ERAdominationCards'></div>";
	var ERAdominationCard = "<div class='ERAdominationCard' id='ERAdominationCard-${domination}' tabindex='-1' domination='${domination}'><img style='height:15vh;'/></div>";
	var ERAsector = "<div class='ERAsector ERAsector-${id}' id='ERAsector-${id}' sector=${sector} style='left:${x}px;top:${y}px;transform:rotate(${angle}deg)'></div>";
	var ERAcounter = "<div class='ERAcounter ERAcounter-${color} ERAcounter-${type} ERAcounter-${subtype}' id='ERAcounter-${id}' location='${location}'></div>";
	var ERAship = "<div class='ERAship ERAship-${color}' id='ERAship-${id}' ship=${id} color='${color}' location='${location}'></div>";
	var ERAcylinder = "<div class='ERAcounter ERAcounter-cylinder ERAcounter-${color}' color='${color}' value=${value}></div>";
	var ERAcube = "<div class='ERAcounter ERAcounter-cube ERAcounter-${color}' color='${color}' technology='${technology}' level=${level}></div>";
	var ERApanel = "\
<div class='ERApanel ERApanel-${color}'>\n\
	<div style='display:flex;flex-direction:column;'>\n\
		<div style='display:flex;flex-direction:row;'>\n\
			<div class='ERAtechTrack' id='ERAtechTrack-${color}'></div>\n\
			<div class='ERApopulationTrack' id='ERApopulationTrack-${color}'></div>\n\
		</div>\n\
	</div>\n\
</div>";
</script>

{OVERALL_GAME_FOOTER}
